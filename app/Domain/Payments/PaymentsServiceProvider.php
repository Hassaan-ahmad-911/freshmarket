<?php

namespace App\Domain\Payments;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\FakeGateway;
use App\Domain\Payments\Gateways\StripeConnectGateway;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Webhooks\Handlers\PaymentIntentSucceeded;
use App\Domain\Payments\Webhooks\StripeSignatureVerifier;
use App\Domain\Payments\Webhooks\WebhookHandlerRegistry;
use App\Domain\Shared\ModuleServiceProvider;
use Stripe\StripeClient;

/**
 * Payments module: Charging the customer: payment gateway, payments, refunds, webhooks.
 */
class PaymentsServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        // Real Stripe when a secret key is configured, otherwise the offline fake.
        // A singleton, so the whole request (and each test) shares one instance.
        $this->app->singleton(PaymentGateway::class, function () {
            $secret = config('services.stripe.secret');

            return $secret
                ? new StripeConnectGateway(new StripeClient([
                    'api_key' => $secret,
                    'max_network_retries' => 2, // the SDK retries safely with idempotency keys
                ]))
                : new FakeGateway;
        });

        $this->app->singleton(WebhookHandlerRegistry::class);

        $this->app->bind(StripeSignatureVerifier::class, fn () => new StripeSignatureVerifier(config('services.stripe.webhook_secret')));
    }

    protected function bootModule(): void
    {
        $this->app->make(WebhookHandlerRegistry::class)
            ->register('payment_intent.succeeded', PaymentIntentSucceeded::class);

        // Attach $order->payment without the Orders module depending on Payments.
        Order::resolveRelationUsing('payment', fn (Order $order) => $order->hasOne(Payment::class));
    }
}
