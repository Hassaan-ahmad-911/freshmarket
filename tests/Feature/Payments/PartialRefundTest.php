<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Payments\Actions\IssueRefund;
use App\Domain\Payments\Enums\RefundStatus;
use App\Domain\Payments\Models\Refund;
use App\Domain\Payments\Notifications\RefundProcessed;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/*
| The demo scenario: one order, two suppliers. Refunding ONE item from ONE
| supplier must refund the customer, reverse only that supplier's transfer,
| and leave the other supplier completely untouched.
*/

beforeEach(function () {
    config(['freshmarket.default_delivery_fee' => 499, 'freshmarket.free_delivery_threshold' => 0, 'freshmarket.default_commission_bps' => 1000]);

    $this->farm = connectedStore();          // 10%
    $this->bakery = connectedStore(1200);    // 12%
    $this->tomatoes = Product::factory()->for($this->farm)->create(['name' => 'Tomatoes', 'price' => 450, 'stock' => 10]);
    $this->carrots = Product::factory()->for($this->farm)->create(['name' => 'Carrots', 'price' => 275, 'stock' => 10]);
    $this->bread = Product::factory()->for($this->bakery)->create(['name' => 'Bread', 'price' => 650, 'stock' => 10]);

    $this->customer = User::factory()->customer()->create();
    $this->order = paidOrder([$this->tomatoes->id => 2, $this->carrots->id => 2, $this->bread->id => 1], $this->customer);

    $this->farmOrder = $this->order->supplierOrders->firstWhere('supplier_id', $this->farm->id);
    $this->bakeryOrder = $this->order->supplierOrders->firstWhere('supplier_id', $this->bakery->id);
    $this->carrotsLine = $this->farmOrder->items->firstWhere('product_name', 'Carrots');

    $this->farmOwner = supplierOwner($this->farm);
});

it('refunds ONE item of one supplier and reverses only that supplier\'s transfer', function () {
    Notification::fake();

    // Farm sub-order before: items 9.00 + 5.50 = 14.50, delivery 4.99, total 19.49,
    // commission 0.90 + 0.55 = 1.45, payout 18.04 (already transferred).
    expect($this->farmOrder->transfer->amount->amount)->toBe(1804);

    $this->actingAs($this->farmOwner)
        ->post(route('supplier.orders.items.refund', [$this->farmOrder, $this->carrotsLine]), ['quantity' => 1, 'reason' => 'Out of carrots'])
        ->assertSessionHasNoErrors();

    $refund = Refund::sole();
    // Customer gets 2.75 back; platform returns its 10% (0.28 → half of 0.55, rounded); supplier gives back 2.47.
    expect($refund->status)->toBe(RefundStatus::Succeeded)
        ->and($refund->amount->amount)->toBe(275)
        ->and($refund->commission_amount->amount)->toBe(28)
        ->and($refund->reversal_amount->amount)->toBe(247)
        ->and($refund->reversal_status)->toBe('succeeded');

    // Updated farm sub-order totals.
    $farm = $this->farmOrder->fresh();
    expect($farm->refunded_amount->amount)->toBe(275)
        ->and($farm->netTotal()->amount)->toBe(1949 - 275)
        ->and($farm->netCommission()->amount)->toBe(145 - 28)
        ->and($farm->payoutDue()->amount)->toBe(1804 - 247)
        ->and($farm->status)->toBe(SupplierOrderStatus::Pending); // still being fulfilled

    // The farm's transfer was reversed by exactly its share…
    $farmTransfer = $farm->transfer;
    expect($farmTransfer->reversed_amount->amount)->toBe(247)
        ->and($farmTransfer->netAmount()->amount)->toBe($farm->payoutDue()->amount);

    // …and the bakery was not touched at all.
    $bakery = $this->bakeryOrder->fresh();
    expect($bakery->refunded_amount->amount)->toBe(0)
        ->and($bakery->transfer->reversed_amount->amount)->toBe(0)
        ->and($bakery->transfer->status)->toBe(TransferStatus::Paid);

    // What was sent to the payment provider:
    expect(fakeGateway()->refunds)->toHaveCount(1)
        ->and(collect(fakeGateway()->refunds)->first()['payment_intent'])->toBe("pi_test_{$this->order->id}")
        ->and(fakeGateway()->reversals)->toHaveCount(1)
        ->and(collect(fakeGateway()->reversals)->first()['transfer'])->toBe($farmTransfer->stripe_transfer_id);

    // Parent order, stock, email.
    expect($this->order->fresh()->status)->toBe(OrderStatus::PartiallyRefunded)
        ->and($this->order->fresh()->refunded_total->amount)->toBe(275)
        ->and($this->carrots->fresh()->stock)->toBe(8 + 1);
    Notification::assertSentTo($this->customer, RefundProcessed::class);
});

it('keeps the books balanced: refund = commission returned + supplier reversal', function () {
    $refund = app(IssueRefund::class)->item($this->carrotsLine, 1);

    expect($refund->amount->amount)->toBe($refund->commission_amount->amount + $refund->reversal_amount->amount);
});

it('splits a line\'s commission exactly across several partial refunds', function () {
    $store = connectedStore(1250); // 12.5%
    $p = Product::factory()->for($store)->create(['price' => 333, 'stock' => 5]);
    $line = paidOrder([$p->id => 3])->supplierOrders->sole()->items->sole(); // 9.99 → commission 1.25 (124.875 rounded)

    $shares = collect([1, 1, 1])->map(fn ($q) => app(IssueRefund::class)->item($line->fresh(), $q)->commission_amount->amount);

    expect($shares->all())->toBe([42, 42, 41])
        ->and($shares->sum())->toBe(125);
});

it('refuses to refund more than what is left', function () {
    $this->actingAs($this->farmOwner)
        ->post(route('supplier.orders.items.refund', [$this->farmOrder, $this->carrotsLine]), ['quantity' => 3])
        ->assertSessionHasErrors('quantity');

    app(IssueRefund::class)->item($this->carrotsLine, 2);

    $this->actingAs($this->farmOwner)
        ->post(route('supplier.orders.items.refund', [$this->farmOrder, $this->carrotsLine]), ['quantity' => 1])
        ->assertSessionHasErrors('quantity');

    expect(Refund::count())->toBe(1);
});

it('does not let one supplier refund another supplier\'s items', function () {
    $breadLine = $this->bakeryOrder->items->sole();

    // Farm owner, bakery's sub-order → forbidden.
    $this->actingAs($this->farmOwner)
        ->post(route('supplier.orders.items.refund', [$this->bakeryOrder, $breadLine]), ['quantity' => 1])
        ->assertForbidden();

    // Farm owner, OWN sub-order but the bakery's item in the URL → not found.
    $this->actingAs($this->farmOwner)
        ->post(route('supplier.orders.items.refund', [$this->farmOrder, $breadLine]), ['quantity' => 1])
        ->assertNotFound();

    $this->actingAs($this->farmOwner)->post(route('supplier.orders.cancel', $this->bakeryOrder))->assertForbidden();

    expect(Refund::count())->toBe(0);
});

it('cancels one supplier order: refunds what is left plus delivery, other supplier untouched', function () {
    app(IssueRefund::class)->item($this->carrotsLine, 1); // 2.75 already refunded

    $this->actingAs($this->farmOwner)
        ->post(route('supplier.orders.cancel', $this->farmOrder), ['reason' => 'Flooded field'])
        ->assertSessionHasNoErrors();

    $farm = $this->farmOrder->fresh();
    $cancelRefund = Refund::where('type', Refund::TYPE_SUPPLIER_ORDER)->sole();

    expect($farm->status)->toBe(SupplierOrderStatus::Cancelled)
        ->and($cancelRefund->amount->amount)->toBe(1949 - 275)          // everything left, incl. 4.99 delivery
        ->and($cancelRefund->delivery_amount->amount)->toBe(499)
        ->and($farm->refunded_amount->amount)->toBe(1949)
        ->and($farm->payoutDue()->amount)->toBe(0)
        ->and($farm->transfer->netAmount()->amount)->toBe(0)             // supplier keeps nothing
        ->and($farm->transfer->reversed_amount->amount)->toBe(1804);

    expect($this->bakeryOrder->fresh()->transfer->reversed_amount->amount)->toBe(0)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::PartiallyRefunded);
});

it('marks the order fully refunded when every supplier order is cancelled', function () {
    app(IssueRefund::class)->supplierOrder($this->farmOrder);
    app(IssueRefund::class)->supplierOrder($this->bakeryOrder);

    $order = $this->order->fresh();
    expect($order->status)->toBe(OrderStatus::Refunded)
        ->and($order->refunded_total->amount)->toBe($order->total->amount);
});

it('cannot cancel twice or after dispatch', function () {
    app(IssueRefund::class)->supplierOrder($this->farmOrder);

    expect(fn () => app(IssueRefund::class)->supplierOrder($this->farmOrder->fresh()))
        ->toThrow(ValidationException::class);

    $this->bakeryOrder->update(['status' => SupplierOrderStatus::Dispatched]);
    expect(fn () => app(IssueRefund::class)->supplierOrder($this->bakeryOrder->fresh()))
        ->toThrow(ValidationException::class, 'can no longer be cancelled');
});

it('restores every total if Stripe declines the refund', function () {
    fakeGateway()->failRefunds = true;

    expect(fn () => app(IssueRefund::class)->item($this->carrotsLine, 1))
        ->toThrow(ValidationException::class);

    expect(Refund::sole()->status)->toBe(RefundStatus::Failed)
        ->and($this->carrotsLine->fresh()->refunded_quantity)->toBe(0)
        ->and($this->farmOrder->fresh()->refunded_amount->amount)->toBe(0)
        ->and($this->order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($this->farmOrder->fresh()->transfer->reversed_amount->amount)->toBe(0);
});

it('needs no reversal when the transfer was not sent yet — it is sent smaller instead', function () {
    $store = connectedStore();
    $store->update(['stripe_transfers_enabled' => false]);
    $p = Product::factory()->for($store)->create(['price' => 1000, 'stock' => 5]);
    $so = paidOrder([$p->id => 2])->supplierOrders->sole();       // 20.00 + 4.99, commission 2.00 → payout 22.99
    expect($so->transfer->status)->toBe(TransferStatus::Blocked);

    $refund = app(IssueRefund::class)->item($so->items->sole(), 1); // refund 10.00 (commission 1.00, supplier share 9.00)
    expect($refund->reversal_status)->toBe('not_needed')->and(fakeGateway()->reversals)->toBe([]);

    $store->update(['stripe_transfers_enabled' => true]);
    $this->artisan('payouts:retry')->assertSuccessful();

    expect($so->transfer->fresh()->amount->amount)->toBe(2299 - 900)
        ->and($so->transfer->fresh()->status)->toBe(TransferStatus::Paid);
});

it('confirms pending refunds from the charge.refunded webhook', function () {
    $refund = app(IssueRefund::class)->item($this->carrotsLine, 1);
    $refund->update(['status' => RefundStatus::Pending]); // as if Stripe answered "pending"

    postStripeWebhook(stripeEvent('charge.refunded', [
        'id' => "ch_test_{$this->order->id}",
        'object' => 'charge',
        'payment_intent' => "pi_test_{$this->order->id}",
        'amount_refunded' => 275,
    ]))->assertOk();

    expect($refund->fresh()->status)->toBe(RefundStatus::Succeeded);
});

it('prints the Stripe Dashboard links for an order (demo helper)', function () {
    $refund = app(IssueRefund::class)->item($this->carrotsLine, 1);

    $this->artisan('orders:stripe-links', ['number' => $this->order->number])
        ->expectsOutputToContain("payments/pi_test_{$this->order->id}")
        ->expectsOutputToContain($refund->stripe_reversal_id)
        ->assertSuccessful();
});

it('shows the updated totals to the supplier and the customer', function () {
    app(IssueRefund::class)->item($this->carrotsLine, 1);

    $this->actingAs($this->farmOwner)->get(route('supplier.orders.show', $this->farmOrder))
        ->assertOk()
        ->assertSee('Refunded 1 of 2')
        ->assertSee('After refunds')
        ->assertSee('$15.57');  // net payout 18.04 − 2.47

    $this->actingAs($this->customer)->get(route('orders.show', $this->order))
        ->assertOk()
        ->assertSee('1 of 2 refunded')
        ->assertSee('Refunded to your card');
});
