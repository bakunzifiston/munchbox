<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOption;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    private const SESSION_KEY = 'cart';

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
        $options = ProductOption::query()
            ->with('optionGroup')
            ->whereIn('id', $optionIds)
            ->where('is_active', true)
            ->get();

        $optionsTotal = (float) $options->sum('price_adjustment');
        $unitPrice = Money::from($product->base_price);
        $lineUnit = $unitPrice + $optionsTotal;

        $normalizedOptions = $options->map(fn (ProductOption $option) => [
            'id' => $option->id,
            'name' => $option->name,
            'group' => $option->optionGroup->name,
            'type' => $option->optionGroup->type->value,
            'price_adjustment' => Money::from($option->price_adjustment),
        ])->values()->all();

        $signature = $product->id.'|'.collect($normalizedOptions)->pluck('id')->sort()->implode(',').'|'.($notes ?? '');

        $items = $this->items();
        $existingKey = $items->search(fn (array $item) => ($item['signature'] ?? '') === $signature);

        if ($existingKey !== false) {
            $item = $items[$existingKey];
            $item['quantity'] += $quantity;
            $item['line_total'] = Money::from($item['unit_with_options'] * $item['quantity']);
            $items[$existingKey] = $item;
        } else {
            $items->push([
                'key' => (string) Str::uuid(),
                'signature' => $signature,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->image,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'options_total' => $optionsTotal,
                'unit_with_options' => $lineUnit,
                'line_total' => Money::from($lineUnit * $quantity),
                'options' => $normalizedOptions,
                'notes' => $notes,
            ]);
        }

        Session::put(self::SESSION_KEY, $items->values()->all());
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($key);

            return;
        }

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
}
