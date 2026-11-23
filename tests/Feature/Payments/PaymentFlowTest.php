<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Notifications\NewSupplierOrder;
use App\Domain\Orders\Notifications\OrderConfirmed;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

/*
| Separate charges and transfers, end to end (with the FakeGateway):
| one payment from the customer → one transfer per supplier.
*/

beforeEach(function () {
    config(['freshmarket.default_delivery_fee' => 499, 'freshmarket.free_delivery_threshold' => 0, 'freshmarket.default_commission_bps' => 1000]);

    $this->farm = connectedStore();          // 10%
    $this->bakery = connectedStore(1200);    // 12%
    $this->tomatoes = Product::factory()->for($this->farm)->create(['price' => 450]);
    $this->bread = Product::factory()->for($this->bakery)->create(['price' => 650]);
});

it('charges the customer once and creates one transfer per supplier', function () {
    $order = paidOrder([$this->tomatoes->id => 2, $this->bread->id => 1]);

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->payment->status)->toBe(PaymentStatus::Succeeded)
        ->and($order->payment->amount->amount)->toBe($order->total->amount);

    // Farm: 9.00 + 4.99 = 13.99, commission 0.90 → 13.09
    // Bakery: 6.50 + 4.99 = 11.49, commission 0.78 → 10.71
    $farmTransfer = $order->supplierOrders->firstWhere('supplier_id', $this->farm->id)->transfer;
    $bakeryTransfer = $order->supplierOrders->firstWhere('supplier_id', $this->bakery->id)->transfer;

    expect($farmTransfer->status)->toBe(TransferStatus::Paid)
        ->and($farmTransfer->amount->amount)->toBe(1309)
        ->and($farmTransfer->destination_account)->toBe($this->farm->stripe_account_id)
        ->and($bakeryTransfer->amount->amount)->toBe(1071)
        ->and($bakeryTransfer->destination_account)->toBe($this->bakery->stripe_account_id);

    // What was actually sent to the gateway:
    $sent = collect(fakeGateway()->transfers);
    expect($sent)->toHaveCount(2)
        ->and($sent->pluck('transfer_group')->unique()->all())->toBe(["order_{$order->id}"])
        ->and($sent->pluck('source_transaction')->unique()->all())->toBe(["ch_test_{$order->id}"]);

    // Customer paid = suppliers received + platform commission. Nothing lost, nothing invented.
    expect($farmTransfer->amount->amount + $bakeryTransfer->amount->amount + $order->commission_total->amount)
        ->toBe($order->total->amount);
});

it('sends the confirmation emails once paid', function () {
    Notification::fake();
    $customer = User::factory()->customer()->create();

    paidOrder([$this->tomatoes->id => 1, $this->bread->id => 1], $customer);

    Notification::assertSentTo($customer, OrderConfirmed::class);
    Notification::assertSentTo($this->farm->owner, NewSupplierOrder::class);
    Notification::assertSentTo($this->bakery->owner, NewSupplierOrder::class);
});

it('goes from basket to paid order through the checkout page', function () {
    $customer = User::factory()->customer()->create();
    $this->actingAs($customer)->post(route('cart.store', $this->tomatoes), ['quantity' => 1]);
    $this->actingAs($customer)->post(route('cart.store', $this->bread), ['quantity' => 1]);

    $response = $this->actingAs($customer)->post(route('checkout.store'), [
        'delivery_name' => 'Casey', 'delivery_phone' => '555', 'delivery_address' => '1 High St', 'delivery_area' => 'Camden',
    ]);

    $order = Order::sole();
    $response->assertRedirect(route('fake-checkout.show', $order->payment));
    expect($order->status)->toBe(OrderStatus::PendingPayment); // not paid just by reaching the payment page

    $this->actingAs($customer)->post(route('fake-checkout.pay', $order->payment))
        ->assertRedirect(route('orders.show', ['order' => $order, 'checkout' => 'success']));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    $this->actingAs($customer)->get(route('orders.show', $order))->assertSee('Paid');
});

it('reuses the open checkout session when "Pay now" is clicked again', function () {
    $order = placeOrder([$this->tomatoes->id => 1]);
    $customer = $order->customer;

    $first = $this->actingAs($customer)->post(route('orders.pay', $order))->headers->get('Location');
    $second = $this->actingAs($customer)->post(route('orders.pay', $order))->headers->get('Location');

    expect($first)->toBe($second)->and(fakeGateway()->checkouts)->toHaveCount(1);
});

it('does not let another customer pay or view someone else\'s order', function () {
    $order = placeOrder([$this->tomatoes->id => 1]);

    $this->actingAs(User::factory()->customer()->create())
        ->post(route('orders.pay', $order))
        ->assertForbidden();
});

it('holds a transfer when the supplier has not finished Stripe onboarding, and still pays the other', function () {
    $this->bakery->update(['stripe_transfers_enabled' => false]);

    $order = paidOrder([$this->tomatoes->id => 1, $this->bread->id => 1]);

    expect($order->supplierOrders->firstWhere('supplier_id', $this->farm->id)->transfer->status)->toBe(TransferStatus::Paid)
        ->and($order->supplierOrders->firstWhere('supplier_id', $this->bakery->id)->transfer->status)->toBe(TransferStatus::Blocked)
        ->and(fakeGateway()->transfers)->toHaveCount(1);
});

it('isolates a failed transfer to one supplier and retries it without paying twice', function () {
    fakeGateway()->failingDestinations = [$this->bakery->stripe_account_id];

    $order = paidOrder([$this->tomatoes->id => 1, $this->bread->id => 1]);
    $bakeryOrder = $order->supplierOrders->firstWhere('supplier_id', $this->bakery->id);

    expect($bakeryOrder->transfer->status)->toBe(TransferStatus::Failed)
        ->and(fakeGateway()->transfers)->toHaveCount(1);

    fakeGateway()->failingDestinations = [];
    $this->artisan('payouts:retry')->assertSuccessful();
    $this->artisan('payouts:retry')->assertSuccessful(); // running it again changes nothing

    expect($bakeryOrder->transfer->fresh()->status)->toBe(TransferStatus::Paid)
        ->and(fakeGateway()->transfers)->toHaveCount(2)
        ->and(fakeGateway()->totalTransferredTo($this->bakery->stripe_account_id))->toBe($bakeryOrder->payout_amount->amount);
});
