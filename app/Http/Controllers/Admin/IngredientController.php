<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\InventoryItem;
use App\Models\ProductOption;
use App\Services\SalesMetrics;
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

        $total = Ingredient::query()->count();
        $active = Ingredient::query()->active()->count();
        $lowStock = app(SalesMetrics::class)->stockAlertCount();

        $kpis = [
            ['label' => 'Ingredients', 'value' => $total, 'hint' => 'In catalog'],
            ['label' => 'Active', 'value' => $active, 'hint' => 'Usable on menu', 'tone' => 'success'],
            ['label' => 'Low stock', 'value' => $lowStock, 'hint' => 'At or below minimum', 'tone' => $lowStock > 0 ? 'warn' : 'default'],
            ['label' => 'Inactive', 'value' => $total - $active, 'hint' => 'Hidden from options'],
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
        // Keep the stock history: ingredients that have moved stock can only be deactivated.
        if ($ingredient->inventoryItem?->movements()->exists()) {
            $ingredient->update(['is_active' => false]);

            return back()->with('success', "{$ingredient->name} has stock history, so it was deactivated instead of deleted.");
        }

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
