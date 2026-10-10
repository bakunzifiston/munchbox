<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->withCount('products')->orderBy('sort_order')->get();

        $kpis = [
            ['label' => 'Categories', 'value' => $categories->count(), 'hint' => 'Menu sections'],
            ['label' => 'Active', 'value' => $categories->where('is_active', true)->count(), 'hint' => 'Shown on menu', 'tone' => 'success'],
            ['label' => 'With products', 'value' => $categories->where('products_count', '>', 0)->count(), 'hint' => 'Non-empty categories'],
            ['label' => 'Products', 'value' => $categories->sum('products_count'), 'hint' => 'Linked menu items'],
        ];

        return view('admin.categories.index', compact('categories', 'kpis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::query()->create([
            ...$data,
            'slug' => Str::slug($data['name']),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            ...$data,
            'slug' => Str::slug($data['name']),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete a category with products.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
