<?php

namespace App\View\Composers;

use App\Services\CartService;
use Illuminate\View\View;

class CartComposer
{
    public function __construct(private readonly CartService $cart) {}

    public function compose(View $view): void
    {
        $view->with('cartCount', $this->cart->count());
    }
}
