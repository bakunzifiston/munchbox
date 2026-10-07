<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->active()
            ->with(['products' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $activeSlug = $request->string('category')->toString();

        $extras = Product::query()
            ->active()
            ->whereHas('category', fn ($q) => $q->where('slug', 'sides-combos'))
            ->orderBy('sort_order')
            ->get();

        return view('customer.menu.index', compact('categories', 'activeSlug', 'extras'));
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

        $defaultNames = config('menu.defaults.'.$product->slug, []);
        $defaultOptionIds = [];

        if ($defaultNames) {
            foreach ($product->optionGroups as $group) {
                foreach ($group->activeOptions as $option) {
                    if (in_array($option->name, $defaultNames, true)) {
                        $defaultOptionIds[] = $option->id;
                    }
                }
            }
        } else {
            foreach ($product->optionGroups as $group) {
                foreach ($group->activeOptions as $option) {
                    if ($option->is_default) {
                        $defaultOptionIds[] = $option->id;
                    }
                }
            }
        }

        $related = Product::query()
            ->active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        return view('customer.menu.show', compact('product', 'related', 'defaultOptionIds'));
    }
}
