<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Cart\Services\CartService;
use App\Domain\Orders\Actions\PlaceOrder;
use App\Domain\Payments\Actions\StartCheckout;
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

    /**
     * 1. Split the basket into one order + one sub-order per supplier (stock reserved).
     * 2. Empty the basket.
     * 3. Send the customer to the payment page (Stripe Checkout, or the fake page offline).
     */
    public function store(CheckoutRequest $request, PlaceOrder $placeOrder, StartCheckout $startCheckout): RedirectResponse
    {
        $summary = $this->cart->summary($request->user());

        $order = $placeOrder->handle($request->user(), $summary->lines(), $request->deliveryDetails());

        $this->cart->clear($request->user());

        return redirect()->away($startCheckout->handle($order));
    }
}
