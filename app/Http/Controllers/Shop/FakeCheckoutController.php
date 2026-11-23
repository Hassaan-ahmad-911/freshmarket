<?php

namespace App\Http\Controllers\Shop;

use App\Domain\Payments\Actions\MarkPaymentSucceeded;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\FakeGateway;
use App\Domain\Payments\Models\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Stands in for Stripe's hosted payment page when no Stripe keys are configured.
 * "Paying" here calls exactly the same code the payment_intent.succeeded webhook calls.
 */
class FakeCheckoutController extends Controller
{
    public function __construct(PaymentGateway $gateway)
    {
        abort_unless($gateway instanceof FakeGateway, 404);
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment->order);

        return view('shop.checkout.fake', ['payment' => $payment->load('order')]);
    }

    public function pay(Payment $payment, MarkPaymentSucceeded $markPaid): RedirectResponse
    {
        Gate::authorize('view', $payment->order);

        $markPaid->handle(
            orderId: $payment->order_id,
            paymentIntentId: 'pi_fake_'.Str::random(14),
            chargeId: 'ch_fake_'.Str::random(14),
            amountReceived: $payment->amount->amount,
        );

        return redirect()->route('orders.show', ['order' => $payment->order_id, 'checkout' => 'success']);
    }
}
