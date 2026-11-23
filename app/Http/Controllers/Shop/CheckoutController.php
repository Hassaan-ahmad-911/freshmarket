<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Cart\Services\CartService;
use App\Domain\Orders\Actions\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function create(Request $request): View|RedirectResponse
    {
        $summary = $this->cart->summary($request->user());

        if (! $summary->canCheckout()) {
            return redirect()->route('cart.index');
        }

        return view('shop.checkout.create', [
            'summary' => $summary,
            'user' => $request->user(),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $summary = $this->cart->summary($request->user());

        // Split the basket into one order + one sub-order per supplier, reserving stock.
        $order = $placeOrder->handle($request->user(), $summary->lines(), $request->deliveryDetails());

        $this->cart->clear($request->user());

        return redirect()->route('orders.show', $order);
    }
}
