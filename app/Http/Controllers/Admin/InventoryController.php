<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(): View
    {
        $items = InventoryItem::query()
            ->with('ingredient')
            ->join('ingredients', 'ingredients.id', '=', 'inventory_items.ingredient_id')
            ->orderBy('ingredients.name')
            ->select('inventory_items.*')
            ->paginate(20);

        $lowStock = InventoryItem::query()->with('ingredient')->lowStock()->get();
        $totalItems = InventoryItem::query()->count();
        $stockValue = (float) InventoryItem::query()
            ->selectRaw('COALESCE(SUM(quantity_on_hand * unit_cost), 0) as value')
            ->value('value');

        $kpis = [
            ['label' => 'Tracked items', 'value' => $totalItems, 'hint' => 'Inventory records'],
            ['label' => 'Low stock', 'value' => $lowStock->count(), 'hint' => 'Need restock', 'tone' => $lowStock->isNotEmpty() ? 'warn' : 'default'],
            ['label' => 'Healthy', 'value' => max(0, $totalItems - $lowStock->count()), 'hint' => 'Above threshold', 'tone' => 'success'],
            ['label' => 'Stock value', 'value' => number_format($stockValue, 0).' RWF', 'hint' => 'Qty × unit cost'],
        ];

        return view('admin.inventory.index', compact('items', 'lowStock', 'kpis'));
    }

    public function movements(InventoryItem $item): View
    {
        $item->load('ingredient');
        $movements = $item->movements()->with('user')->paginate(25);

        $kpis = [
            ['label' => 'On hand', 'value' => rtrim(rtrim(number_format((float) $item->quantity_on_hand, 2), '0'), '.').' '.$item->ingredient->unit, 'hint' => 'Current stock'],
            ['label' => 'Threshold', 'value' => rtrim(rtrim(number_format((float) $item->low_stock_threshold, 2), '0'), '.'), 'hint' => 'Low-stock alert'],
            ['label' => 'Movements', 'value' => $item->movements()->count(), 'hint' => 'All time'],
            ['label' => 'Status', 'value' => $item->isLowStock() ? 'Low' : 'OK', 'hint' => 'Stock health', 'tone' => $item->isLowStock() ? 'warn' : 'success'],
        ];

        return view('admin.inventory.movements', compact('item', 'movements', 'kpis'));
    }

    public function adjust(Request $request, InventoryItem $item): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:receive,use,waste,adjustment'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
            'signed_quantity' => ['nullable', 'numeric'],
        ]);

        try {
            match ($data['action']) {
                'receive' => $this->inventory->receive($item, (float) $data['quantity'], $request->user(), $data['notes'] ?? null),
                'use' => $this->inventory->use($item, (float) $data['quantity'], $request->user(), $data['notes'] ?? null),
                'waste' => $this->inventory->waste($item, (float) $data['quantity'], $request->user(), $data['notes'] ?? null),
                'adjustment' => $this->inventory->manualAdjustment(
                    $item,
                    (float) ($data['signed_quantity'] ?? $data['quantity']),
                    $request->user(),
                    $data['notes'] ?? null
                ),
            };
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Inventory updated.');
    }
}
