<?php

use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Models\Order;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/*
| The core rule: ONE basket with items from many suppliers becomes
| ONE parent order with ONE supplier order per supplier.
*/

beforeEach(function () {
    config(['freshmarket.default_delivery_fee' => 499, 'freshmarket.free_delivery_threshold' => 5000]);

    $this->farm = camdenStore();
    $this->bakery = camdenStore();
    $this->tomatoes = Product::factory()->for($this->farm)->create(['price' => 450, 'stock' => 10]);
    $this->carrots = Product::factory()->for($this->farm)->create(['price' => 275, 'stock' => 10]);
    $this->bread = Product::factory()->for($this->bakery)->create(['price' => 650, 'stock' => 10]);
});

it('splits one basket into one order with a sub-order per supplier', function () {
    $order = placeOrder([$this->tomatoes->id => 2, $this->carrots->id => 1, $this->bread->id => 3]);

    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->number)->toBe('FM-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT))
        ->and($order->supplierOrders)->toHaveCount(2);

    $farmOrder = $order->supplierOrders->firstWhere('supplier_id', $this->farm->id);
    $bakeryOrder = $order->supplierOrders->firstWhere('supplier_id', $this->bakery->id);

    // Farm: 2 × 4.50 + 1 × 2.75 = 11.75, plus 4.99 delivery
    expect($farmOrder->items)->toHaveCount(2)
        ->and($farmOrder->subtotal->amount)->toBe(1175)
        ->and($farmOrder->delivery_fee->amount)->toBe(499)
        ->and($farmOrder->total->amount)->toBe(1674)
        ->and($farmOrder->status)->toBe(SupplierOrderStatus::Pending)
        ->and($farmOrder->number)->toBe("{$order->number}-1");

    // Bakery: 3 × 6.50 = 19.50, plus 4.99 delivery
    expect($bakeryOrder->items)->toHaveCount(1)
        ->and($bakeryOrder->subtotal->amount)->toBe(1950)
        ->and($bakeryOrder->total->amount)->toBe(2449);

    // The parent order is the sum of its parts.
    expect($order->subtotal->amount)->toBe(1175 + 1950)
        ->and($order->delivery_total->amount)->toBe(998)
        ->and($order->total->amount)->toBe(1674 + 2449);
});

it('copies product names and prices onto the order', function () {
    $order = placeOrder([$this->bread->id => 1]);

    $this->bread->update(['name' => 'Renamed', 'price' => 9999]);

    $item = $order->items()->sole();
    expect($item->product_name)->not->toBe('Renamed')
        ->and($item->unit_price->amount)->toBe(650);
});

it('reserves stock at checkout', function () {
    placeOrder([$this->tomatoes->id => 4, $this->bread->id => 10]);

    expect($this->tomatoes->refresh()->stock)->toBe(6)
        ->and($this->bread->refresh()->stock)->toBe(0);
});

it('saves nothing if one product does not have enough stock', function () {
    expect(fn () => placeOrder([$this->tomatoes->id => 2, $this->bread->id => 11]))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0)
        ->and($this->tomatoes->refresh()->stock)->toBe(10); // rolled back
});

it('gives free delivery above the threshold, per supplier', function () {
    $order = placeOrder([$this->bread->id => 8, $this->carrots->id => 1]); // bakery 52.00, farm 2.75

    expect($order->supplierOrders->firstWhere('supplier_id', $this->bakery->id)->delivery_fee->amount)->toBe(0)
        ->and($order->supplierOrders->firstWhere('supplier_id', $this->farm->id)->delivery_fee->amount)->toBe(499);
});

it('enforces each store\'s minimum order', function () {
    $this->farm->update(['min_order_amount' => 2000]);

    expect(fn () => placeOrder([$this->carrots->id => 1, $this->bread->id => 1]))
        ->toThrow(ValidationException::class, 'minimum order');
});

it('refuses areas a store does not deliver to', function () {
    expect(fn () => placeOrder([$this->bread->id => 1], area: 'Brighton'))
        ->toThrow(ValidationException::class, 'does not deliver to Brighton');
});

it('refuses products of a suspended store', function () {
    $this->bakery->update(['status' => 'suspended']);

    expect(fn () => placeOrder([$this->bread->id => 1]))->toThrow(ValidationException::class);
});

it('runs the whole basket-to-order flow over HTTP', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->post(route('cart.store', $this->tomatoes), ['quantity' => 2])->assertSessionHasNoErrors();
    $this->actingAs($customer)->post(route('cart.store', $this->bread), ['quantity' => 1]);

    $this->actingAs($customer)->get(route('cart.index'))
        ->assertOk()
        ->assertSee($this->farm->name)
        ->assertSee($this->bakery->name);

    $response = $this->actingAs($customer)->post(route('checkout.store'), [
        'delivery_name' => 'Casey', 'delivery_phone' => '555', 'delivery_address' => '1 High St', 'delivery_area' => 'camden',
    ]);

    $order = Order::sole();
    expect($order->supplierOrders)->toHaveCount(2)
        ->and($order->user_id)->toBe($customer->id);
    expect(app(CartService::class)->count($customer))->toBe(0); // basket emptied

    $response->assertRedirect();
});
