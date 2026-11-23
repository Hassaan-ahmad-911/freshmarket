<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Cart\Models\CartItem;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index(Request $request): View
    {
        return view('shop.cart.index', ['summary' => $this->cart->summary($request->user())]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartService::MAX_QUANTITY]]);

        $this->cart->add($request->user(), $product, (int) ($data['quantity'] ?? 1));

        return back()->with('status', "Added {$product->name} to your basket.");
    }

    public function update(Request $request, CartItem $item): RedirectResponse
    {
        Gate::authorize('update', $item);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartService::MAX_QUANTITY]]);

        $this->cart->setQuantity($item, (int) $data['quantity']);

        return back();
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);
        $item->delete();

        return back()->with('status', 'Item removed.');
    }
}
