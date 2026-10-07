<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddToCartRequest;
use App\Models\OptionGroup;
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
        return view('customer.cart.index', $this->cart->summary());
    }

    public function store(AddToCartRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $optionIds = collect($request->validated('options', []))->map(fn ($id) => (int) $id)->all();
        $this->validateOptionSelections($product, $optionIds);

        $this->cart->add(
            $product,
            (int) $request->validated('quantity'),
            $optionIds,
            $request->validated('notes')
        );

        return redirect()
            ->route('cart.index')
            ->with('success', "{$product->name} added to your cart.");
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:20']]);
        $this->cart->updateQuantity($key, (int) $request->integer('quantity'));

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return back()->with('success', 'Item removed from cart.');
    }

    /**
     * @param  list<int>  $optionIds
     */
    private function validateOptionSelections(Product $product, array $optionIds): void
    {
        $product->load(['optionGroups.activeOptions']);

        foreach ($product->optionGroups as $group) {
            /** @var OptionGroup $group */
            $selected = $group->activeOptions->whereIn('id', $optionIds)->count();

            if ($group->is_required && $selected < max(1, $group->min_selections)) {
                abort(422, "Please choose {$group->name}.");
            }

            if ($selected > $group->max_selections) {
                abort(422, "Too many selections for {$group->name}.");
            }
        }
    }
}
