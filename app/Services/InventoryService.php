<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryService
{
    public function receive(InventoryItem $item, float $quantity, ?User $user = null, ?string $notes = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Received quantity must be positive.');
        }

        return $this->adjust($item, $quantity, InventoryMovementType::Received, $user, $notes);
    }

    public function use(InventoryItem $item, float $quantity, ?User $user = null, ?string $notes = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Used quantity must be positive.');
        }

        return $this->adjust($item, -$quantity, InventoryMovementType::Used, $user, $notes);
    }

    public function waste(InventoryItem $item, float $quantity, ?User $user = null, ?string $notes = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Waste quantity must be positive.');
        }

        return $this->adjust($item, -$quantity, InventoryMovementType::Waste, $user, $notes);
    }

    public function manualAdjustment(InventoryItem $item, float $signedQuantity, ?User $user = null, ?string $notes = null): InventoryMovement
    {
        return $this->adjust($item, $signedQuantity, InventoryMovementType::Adjustment, $user, $notes);
    }

    public function deductForOrder(Order $order, ?User $user = null): void
    {
        if ($order->inventory_deducted) {
            return;
        }

        DB::transaction(function () use ($order, $user) {
            $order->load('items.options');

            $requirements = $this->requirementsFor($order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'option_ids' => $item->options->pluck('product_option_id')->filter()->all(),
                'quantity' => $item->quantity,
            ])->all());

            foreach ($requirements as $ingredientId => $quantity) {
                $inventoryItem = InventoryItem::query()
                    ->where('ingredient_id', $ingredientId)
                    ->lockForUpdate()
                    ->first();

                if (! $inventoryItem) {
                    continue;
                }

                $this->adjust(
                    $inventoryItem,
                    -abs($quantity),
                    InventoryMovementType::OrderDeduction,
                    $user,
                    "Order {$order->order_number}",
                    Order::class,
                    $order->id
                );
            }

            $order->update(['inventory_deducted' => true]);
        });
    }

    /**
     * Put back stock deducted for an order that will no longer be made. The
     * original deductions stay in the history; reversals are added next to them.
     */
    public function restoreForOrder(Order $order, ?User $user = null): void
    {
        if (! $order->inventory_deducted) {
            return;
        }

        DB::transaction(function () use ($order, $user) {
            $deducted = InventoryMovement::query()
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->whereIn('type', [InventoryMovementType::OrderDeduction, InventoryMovementType::OrderReversal])
                ->selectRaw('inventory_item_id, SUM(quantity) as net')
                ->groupBy('inventory_item_id')
                ->get();

            foreach ($deducted as $row) {
                if ((float) $row->net >= 0) {
                    continue;
                }

                $this->adjust(
                    InventoryItem::query()->findOrFail($row->inventory_item_id),
                    abs((float) $row->net),
                    InventoryMovementType::OrderReversal,
                    $user,
                    "Order {$order->order_number} cancelled",
                    Order::class,
                    $order->id
                );
            }

            $order->update(['inventory_deducted' => false]);
        });
    }

    /**
     * Ingredients that don't have enough stock on hand for these cart lines.
     *
     * @param  iterable<array{product_id: int, options?: array, quantity: int}>  $cartItems
     * @return list<string> Ingredient names that are short.
     */
    public function shortagesForCart(iterable $cartItems): array
    {
        $lines = collect($cartItems)->map(fn (array $item) => [
            'product_id' => $item['product_id'],
            'option_ids' => collect($item['options'] ?? [])->pluck('id')->all(),
            'quantity' => $item['quantity'],
        ])->all();

        $requirements = $this->requirementsFor($lines);

        if ($requirements === []) {
            return [];
        }

        return InventoryItem::query()
            ->with('ingredient')
            ->whereIn('ingredient_id', array_keys($requirements))
            ->get()
            ->filter(fn (InventoryItem $item) => (float) $item->quantity_on_hand < $requirements[$item->ingredient_id])
            ->map(fn (InventoryItem $item) => $item->ingredient?->name ?? 'an ingredient')
            ->values()
            ->all();
    }

    /**
     * Total ingredient quantities needed, keyed by ingredient id.
     *
     * @param  iterable<array{product_id: ?int, option_ids: list<int>, quantity: int}>  $lines
     * @return array<int, float>
     */
    private function requirementsFor(iterable $lines): array
    {
        $lines = collect($lines);

        $products = Product::query()
            ->with('ingredients')
            ->whereIn('id', $lines->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $options = ProductOption::query()
            ->with('ingredients')
            ->whereIn('id', $lines->pluck('option_ids')->flatten()->unique())
            ->get()
            ->keyBy('id');

        $requirements = [];

        foreach ($lines as $line) {
            $quantity = (int) $line['quantity'];

            foreach ($products->get($line['product_id'])?->ingredients ?? [] as $ingredient) {
                $requirements[$ingredient->id] = ($requirements[$ingredient->id] ?? 0) + (float) $ingredient->pivot->quantity_required * $quantity;
            }

            foreach ($line['option_ids'] as $optionId) {
                $option = $options->get($optionId);

                if (! $option) {
                    continue;
                }

                foreach ($option->ingredients as $ingredient) {
                    $requirements[$ingredient->id] = ($requirements[$ingredient->id] ?? 0) + (float) $ingredient->pivot->quantity_required * $quantity;
                }

                if ($option->ingredient_id) {
                    $requirements[$option->ingredient_id] = ($requirements[$option->ingredient_id] ?? 0) + $quantity;
                }
            }
        }

        return $requirements;
    }

    private function adjust(
        InventoryItem $item,
        float $signedQuantity,
        InventoryMovementType $type,
        ?User $user = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($item, $signedQuantity, $type, $user, $notes, $referenceType, $referenceId) {
            $locked = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $newBalance = (float) $locked->quantity_on_hand + $signedQuantity;

            if ($newBalance < 0) {
                throw new RuntimeException("Insufficient stock for {$locked->ingredient?->name}.");
            }

            $locked->update(['quantity_on_hand' => $newBalance]);

            return InventoryMovement::query()->create([
                'inventory_item_id' => $locked->id,
                'type' => $type,
                'quantity' => $signedQuantity,
                'balance_after' => $newBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'user_id' => $user?->id,
            ]);
        });
    }
}
