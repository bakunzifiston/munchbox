<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featured = Product::query()
            ->with('category')
            ->active()
            ->featured()
            ->orderBy('sort_order')
            ->limit(4)
            ->get();

        $categories = Category::query()
            ->active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        return view('customer.home', [
            'featured' => $featured,
            'categories' => $categories,
            'businessName' => BusinessSetting::getValue('business_name', 'MunchBox'),
            'tagline' => BusinessSetting::getValue('tagline', config('menu.tagline')),
        ]);
    }
}
