<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Actions\StartCheckout;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Payouts\Models\Transfer;

beforeEach(function () {
    $this->farm = connectedStore();
    $this->bakery = connectedStore();
    $this->order = placeOrder([
        Product::factory()->for($this->farm)->create()->id => 1,
        Product::factory()->for($this->bakery)->create()->id => 2,
    ]);
    app(StartCheckout::class)->handle($this->order);

    $this->succeeded = stripeEvent('payment_intent.succeeded', [
        'id' => 'pi_123',
        'object' => 'payment_intent',
        'amount' => $this->order->total->amount,
        'amount_received' => $this->order->total->amount,
        'latest_charge' => 'ch_123',
        'transfer_group' => "order_{$this->order->id}",
        'metadata' => ['order_id' => (string) $this->order->id],
    ], 'evt_paid_1');
});

it('marks the order paid and pays suppliers on payment_intent.succeeded', function () {
    postStripeWebhook($this->succeeded)->assertOk()->assertJson(['status' => 'processed']);

    $this->order->refresh();
    expect($this->order->status)->toBe(OrderStatus::Paid)
        ->and($this->order->payment->payment_intent_id)->toBe('pi_123')
        ->and($this->order->payment->charge_id)->toBe('ch_123')
        ->and(Transfer::where('status', TransferStatus::Paid)->count())->toBe(2);
});

it('is idempotent: the same event delivered twice is processed once', function () {
    postStripeWebhook($this->succeeded)->assertOk();
    postStripeWebhook($this->succeeded)->assertOk();
    postStripeWebhook($this->succeeded)->assertOk();

    expect(WebhookEvent::count())->toBe(1)
        ->and(WebhookEvent::sole()->attempts)->toBe(1)
        ->and(Transfer::count())->toBe(2)
        ->and(fakeGateway()->transfers)->toHaveCount(2);
});

it('is idempotent across different events about the same payment', function () {
    postStripeWebhook($this->succeeded)->assertOk();
    postStripeWebhook(array_merge($this->succeeded, ['id' => 'evt_paid_2']))->assertOk();

    expect(WebhookEvent::count())->toBe(2)
        ->and(fakeGateway()->transfers)->toHaveCount(2); // still one transfer per supplier
});

it('rejects a webhook with a bad signature', function () {
    postStripeWebhook($this->succeeded, secret: 'whsec_wrong')->assertStatus(400);

    expect(WebhookEvent::count())->toBe(0)
        ->and($this->order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('rejects a webhook without a signature', function () {
    $this->postJson('/stripe/webhook', $this->succeeded)->assertStatus(400);
});

it('records but ignores event types it does not handle', function () {
    postStripeWebhook(stripeEvent('customer.created', ['id' => 'cus_1']))->assertOk()->assertJson(['status' => 'ignored']);
});

it('ignores payments that do not belong to FreshMarket', function () {
    postStripeWebhook(stripeEvent('payment_intent.succeeded', ['id' => 'pi_other', 'amount' => 100, 'metadata' => []]))->assertOk();

    expect($this->order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('enables a supplier and sends its waiting transfer on account.updated', function () {
    $this->bakery->update(['stripe_transfers_enabled' => false]);
    postStripeWebhook($this->succeeded);

    $waiting = Transfer::where('supplier_id', $this->bakery->id)->sole();
    expect($waiting->status)->toBe(TransferStatus::Blocked);

    postStripeWebhook(stripeEvent('account.updated', [
        'id' => $this->bakery->stripe_account_id,
        'object' => 'account',
        'details_submitted' => true,
        'payouts_enabled' => true,
        'capabilities' => ['transfers' => 'active'],
    ]))->assertOk();

    expect($this->bakery->refresh()->stripe_transfers_enabled)->toBeTrue()
        ->and($waiting->refresh()->status)->toBe(TransferStatus::Paid);
});

it('records a transfer from transfer.created if our own save was lost', function () {
    $this->bakery->update(['stripe_transfers_enabled' => false]);
    postStripeWebhook($this->succeeded);
    $transfer = Transfer::where('supplier_id', $this->bakery->id)->sole();

    postStripeWebhook(stripeEvent('transfer.created', [
        'id' => 'tr_from_webhook',
        'object' => 'transfer',
        'amount' => $transfer->amount->amount,
        'destination' => $this->bakery->stripe_account_id,
        'metadata' => ['transfer_id' => (string) $transfer->id],
    ]))->assertOk();

    expect($transfer->refresh()->status)->toBe(TransferStatus::Paid)
        ->and($transfer->stripe_transfer_id)->toBe('tr_from_webhook');
});

it('lets Stripe retry when processing fails', function () {
    $broken = stripeEvent('payment_intent.succeeded', [
        'id' => 'pi_x', 'amount' => 100, 'metadata' => ['order_id' => '999999'], // no payment for this order
    ]);

    postStripeWebhook($broken)->assertStatus(500);

    expect(WebhookEvent::sole()->status)->toBe(WebhookEvent::FAILED);
});
