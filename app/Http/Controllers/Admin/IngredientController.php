<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IngredientController extends Controller
{
    public function index(): View
    {
        $ingredients = Ingredient::query()
            ->with('inventoryItem')
            ->orderBy('name')
            ->paginate(20);

        $all = Ingredient::query()->with('inventoryItem')->get();
        $lowStock = $all->filter(fn (Ingredient $ingredient) => $ingredient->inventoryItem?->isLowStock())->count();

        $kpis = [
            ['label' => 'Ingredients', 'value' => $all->count(), 'hint' => 'In catalog'],
            ['label' => 'Active', 'value' => $all->where('is_active', true)->count(), 'hint' => 'Usable on menu', 'tone' => 'success'],
            ['label' => 'Low stock', 'value' => $lowStock, 'hint' => 'At or below threshold', 'tone' => $lowStock > 0 ? 'warn' : 'default'],
            ['label' => 'Inactive', 'value' => $all->where('is_active', false)->count(), 'hint' => 'Hidden from options'],
        ];

        return view('admin.ingredients.index', compact('ingredients', 'kpis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:ingredients,sku'],
            'unit' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $ingredient = Ingredient::query()->create([
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'unit' => $data['unit'],
                'description' => $data['description'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            InventoryItem::query()->create([
                'ingredient_id' => $ingredient->id,
                'quantity_on_hand' => $data['quantity_on_hand'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 10,
                'unit_cost' => $data['unit_cost'] ?? 0,
            ]);
        });

        return back()->with('success', 'Ingredient added.');
    }

    public function update(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', 'unique:ingredients,sku,'.$ingredient->id],
            'unit' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $ingredient->update([
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'unit' => $data['unit'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $ingredient->inventoryItem?->update([
            'low_stock_threshold' => $data['low_stock_threshold'] ?? $ingredient->inventoryItem->low_stock_threshold,
            'unit_cost' => $data['unit_cost'] ?? $ingredient->inventoryItem->unit_cost,
        ]);

        return back()->with('success', 'Ingredient updated.');
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        DB::transaction(function () use ($ingredient) {
            $ingredient->products()->detach();
            $ingredient->productOptions()->detach();
            ProductOption::query()
                ->where('ingredient_id', $ingredient->id)
                ->update(['ingredient_id' => null]);
            $ingredient->inventoryItem?->delete();
            $ingredient->delete();
        });

        return back()->with('success', 'Ingredient deleted.');
    }
}
