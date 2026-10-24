<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddToCartRequest;
use App\Models\BusinessSetting;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): View
    {
        $notices = $this->cart->refresh();
        $summary = $this->cart->summary();
        $inCartIds = collect($summary['items'])->pluck('product_id')->unique()->all();

        $suggestions = Product::query()
            ->active()
            ->when($inCartIds !== [], fn ($q) => $q->whereNotIn('id', $inCartIds))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        return view('customer.cart.index', array_merge($summary, [
            'suggestions' => $suggestions,
            'notices' => $notices,
            'deliveryFee' => (float) BusinessSetting::getValue('delivery_fee', 0),
            'minOrder' => (float) BusinessSetting::getValue('min_order_amount', 0),
        ]));
    }

    public function store(AddToCartRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $optionIds = collect($request->validated('options', []))->map(fn ($id) => (int) $id)->all();

        $this->cart->add(
            $product,
            (int) $request->validated('quantity'),
            $optionIds,
            $request->validated('notes')
        );

        return redirect()
            ->route('cart.index')
            ->with('success', "{$product->name} added to your order.");
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_QUANTITY]]);
        $this->cart->updateQuantity($key, (int) $request->integer('quantity'));

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return back()->with('success', 'Item removed from cart.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('cart.index')->with('success', 'Your cart is now empty.');
    }
}
