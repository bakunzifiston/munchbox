<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
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
            $order->load(['items.options.productOption.ingredients', 'items.product.ingredients']);

            $requirements = [];

            foreach ($order->items as $item) {
                if ($item->product) {
                    foreach ($item->product->ingredients as $ingredient) {
                        $qty = (float) $ingredient->pivot->quantity_required * $item->quantity;
                        $requirements[$ingredient->id] = ($requirements[$ingredient->id] ?? 0) + $qty;
                    }
                }

                foreach ($item->options as $option) {
                    $productOption = $option->productOption;
                    if (! $productOption) {
                        continue;
                    }

                    $productOption->loadMissing('ingredients');

                    foreach ($productOption->ingredients as $ingredient) {
                        $qty = (float) $ingredient->pivot->quantity_required * $item->quantity;
                        $requirements[$ingredient->id] = ($requirements[$ingredient->id] ?? 0) + $qty;
                    }

                    if ($productOption->ingredient_id) {
                        $requirements[$productOption->ingredient_id] = ($requirements[$productOption->ingredient_id] ?? 0) + (1 * $item->quantity);
                    }
                }
            }

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
