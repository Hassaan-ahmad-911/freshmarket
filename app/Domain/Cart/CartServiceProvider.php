<?php

namespace App\Domain\Cart;

use App\Domain\Cart\Models\CartItem;
use App\Domain\Cart\Policies\CartItemPolicy;
use App\Domain\Cart\Services\CartService;
use App\Domain\Shared\ModuleServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * Cart module: The customer's shopping cart (items from many suppliers).
 */
class CartServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        CartItem::class => CartItemPolicy::class,
    ];

    protected function bootModule(): void
    {
        // Every shop page shows the basket count in the header.
        View::composer('components.shop-layout', function ($view) {
            $user = Auth::user();
            $view->with('cartCount', $user?->isCustomer() ? app(CartService::class)->count($user) : 0);
        });
    }
}
