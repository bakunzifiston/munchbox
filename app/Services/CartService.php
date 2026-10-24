<?php

namespace App\Services;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart';

    public const MAX_QUANTITY = 20;

    public function __construct(private readonly MenuPricing $pricing) {}

    public function items(): Collection
    {
        return collect(Session::get(self::SESSION_KEY, []));
    }

    public function count(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    public function add(Product $product, int $quantity = 1, array $optionIds = [], ?string $notes = null): void
    {
        $notes = filled($notes) ? trim($notes) : null;
        $line = $this->buildLine($product, $optionIds, $notes);

        $items = $this->items();
        $existingKey = $items->search(fn (array $item) => ($item['signature'] ?? '') === $line['signature']);

        if ($existingKey !== false) {
            $item = $items[$existingKey];
            $item['quantity'] = min(self::MAX_QUANTITY, $item['quantity'] + $quantity);
            $item['line_total'] = Money::from($item['unit_with_options'] * $item['quantity']);
            $items[$existingKey] = $item;
        } else {
            $items->push(array_merge($line, [
                'key' => (string) Str::uuid(),
                'quantity' => min(self::MAX_QUANTITY, $quantity),
                'line_total' => Money::from($line['unit_with_options'] * min(self::MAX_QUANTITY, $quantity)),
            ]));
        }

        Session::put(self::SESSION_KEY, $items->values()->all());
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($key);

            return;
        }

        $quantity = min(self::MAX_QUANTITY, $quantity);

        $items = $this->items()->map(function (array $item) use ($key, $quantity) {
            if ($item['key'] !== $key) {
                return $item;
            }

            $item['quantity'] = $quantity;
            $item['line_total'] = Money::from($item['unit_with_options'] * $quantity);

            return $item;
        });

        Session::put(self::SESSION_KEY, $items->values()->all());
    }

    public function remove(string $key): void
    {
        Session::put(
            self::SESSION_KEY,
            $this->items()->reject(fn (array $item) => $item['key'] === $key)->values()->all()
        );
    }

    /**
     * Re-price every line from current product and option data, dropping lines
     * whose product or choices are no longer available.
     *
     * @return list<string> Customer-facing notes about anything that changed.
     */
    public function refresh(): array
    {
        $items = $this->items();

        if ($items->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->with('optionGroups.activeOptions')
            ->whereIn('id', $items->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $notices = [];
        $fresh = [];

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (! $product || ! $product->is_active) {
                $notices[] = "{$item['name']} is no longer available and was removed from your cart.";

                continue;
            }

            $optionIds = collect($item['options'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();

            try {
                $line = $this->buildLine($product, $optionIds, $item['notes'] ?? null);
            } catch (ValidationException) {
                $notices[] = "Some choices for {$item['name']} are no longer available. Please customize it again.";

                continue;
            }

            if (Money::from($line['unit_with_options']) !== Money::from($item['unit_with_options'] ?? 0)) {
                $notices[] = "The price of {$product->name} is now ".Money::format($line['unit_with_options']).' each.';
            }

            $quantity = max(1, min(self::MAX_QUANTITY, (int) $item['quantity']));

            $fresh[] = array_merge($line, [
                'key' => $item['key'],
                'quantity' => $quantity,
                'line_total' => Money::from($line['unit_with_options'] * $quantity),
            ]);
        }

        Session::put(self::SESSION_KEY, $fresh);

        return $notices;
    }

    public function subtotal(): float
    {
        return Money::from($this->items()->sum('line_total'));
    }

    /**
     * @return array{items: Collection, subtotal: float, delivery_fee: float, total: float, count: int}
     */
    public function summary(?float $deliveryFee = null): array
    {
        $subtotal = $this->subtotal();
        $fee = Money::from($deliveryFee ?? 0);

        return [
            'items' => $this->items(),
            'subtotal' => $subtotal,
            'delivery_fee' => $fee,
            'total' => Money::from($subtotal + $fee),
            'count' => $this->count(),
        ];
    }

    /**
     * A priced order line outside the session cart (used for orders staff enter by hand).
     *
     * @param  list<int>  $optionIds
     *
     * @throws ValidationException
     */
    public function makeLine(Product $product, array $optionIds, ?string $notes, int $quantity): array
    {
        $quantity = max(1, min(self::MAX_QUANTITY, $quantity));
        $line = $this->buildLine($product, $optionIds, filled($notes) ? trim($notes) : null);

        return array_merge($line, [
            'key' => (string) Str::uuid(),
            'quantity' => $quantity,
            'line_total' => Money::from($line['unit_with_options'] * $quantity),
        ]);
    }

    /**
     * @param  list<int>  $optionIds
     *
     * @throws ValidationException
     */
    private function buildLine(Product $product, array $optionIds, ?string $notes): array
    {
        $optionIds = $product->is_customizable ? array_values(array_unique($optionIds)) : [];

        $this->pricing->validateSelection($product, $optionIds);
        $quote = $this->pricing->quote($product, $optionIds);

        return [
            'signature' => $product->id.'|'.collect($quote['options'])->pluck('id')->sort()->implode(',').'|'.($notes ?? ''),
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->image,
            'unit_price' => $quote['unit_price'],
            'options_total' => $quote['options_total'],
            'unit_with_options' => Money::from($quote['unit_price'] + $quote['options_total']),
            'options' => $quote['options'],
            'notes' => $notes,
        ];
    }
}
