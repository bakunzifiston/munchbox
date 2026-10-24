<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OptionType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\MenuPricing;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function __construct(private readonly MenuPricing $pricing) {}

    public function index(): View
    {
        $categories = Category::query()
            ->active()
            ->with(['products' => fn ($q) => $q->active()->orderBy('sort_order')->with('optionGroups')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (Category $category) => $category->products->isNotEmpty())
            ->values();

        return view('customer.menu.index', [
            'categories' => $categories,
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::query()
            ->with([
                'category',
                'optionGroups' => fn ($q) => $q->active()->with(['activeOptions']),
            ])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        $includedIds = $this->pricing->includedOptionIds($product);

        $charges = $product->optionGroups
            ->flatMap(fn ($group) => $group->activeOptions)
            ->mapWithKeys(fn ($option) => [$option->id => $this->pricing->chargeFor($option, $includedIds)]);

        $selectedIds = collect(old('options', []))->flatten()->map(fn ($id) => (int) $id)->all() ?: $includedIds;

        $related = Product::query()
            ->active()
            ->where('id', '!=', $product->id)
            ->orderByRaw('category_id = ? desc', [$product->category_id])
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        return view('customer.menu.show', [
            'product' => $product,
            'related' => $related,
            'includedIds' => $includedIds,
            'selectedIds' => $selectedIds,
            'charges' => $charges,
            'servedFreshOrToasted' => $product->optionGroups->contains(fn ($group) => $group->type === OptionType::Style),
        ]);
    }
}
