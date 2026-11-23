<?php

use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Notifications\SupplierOrderStatusUpdated;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->farm = camdenStore();
    $this->bakery = camdenStore();
    $this->customer = User::factory()->customer()->create();

    $this->order = placeOrder([
        Product::factory()->for($this->farm)->create(['name' => 'Farm Tomatoes'])->id => 1,
        Product::factory()->for($this->bakery)->create(['name' => 'Bakery Bread'])->id => 1,
    ], $this->customer);
    $this->order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

    $this->farmOrder = $this->order->supplierOrders->firstWhere('supplier_id', $this->farm->id);
    $this->bakeryOrder = $this->order->supplierOrders->firstWhere('supplier_id', $this->bakery->id);
});

it('shows each supplier only its own sub-order', function () {
    $this->actingAs(supplierOwner($this->farm))
        ->get(route('supplier.orders.index'))
        ->assertOk()
        ->assertSee($this->farmOrder->number)
        ->assertDontSee($this->bakeryOrder->number);

    $this->actingAs(supplierOwner($this->farm))
        ->get(route('supplier.orders.show', $this->farmOrder))
        ->assertOk()
        ->assertSee('Farm Tomatoes')
        ->assertDontSee('Bakery Bread');
});

it('blocks a supplier from another supplier\'s sub-order', function () {
    $farmOwner = supplierOwner($this->farm);

    $this->actingAs($farmOwner)->get(route('supplier.orders.show', $this->bakeryOrder))->assertForbidden();
    $this->actingAs($farmOwner)
        ->patch(route('supplier.orders.status', $this->bakeryOrder), ['status' => 'accepted'])
        ->assertForbidden();

    expect($this->bakeryOrder->refresh()->status)->toBe(SupplierOrderStatus::Pending);
});

it('hides unpaid orders from suppliers', function () {
    $unpaid = placeOrder([Product::factory()->for($this->farm)->create()->id => 1]);

    $this->actingAs(supplierOwner($this->farm))
        ->get(route('supplier.orders.index'))
        ->assertDontSee($unpaid->supplierOrders->first()->number);
});

it('moves a sub-order through its statuses and emails the customer', function () {
    Notification::fake();
    $staff = supplierStaff($this->farm);

    foreach (['accepted', 'preparing', 'dispatched', 'delivered'] as $status) {
        $this->actingAs($staff)
            ->patch(route('supplier.orders.status', $this->farmOrder), ['status' => $status])
            ->assertSessionHasNoErrors();
    }

    expect($this->farmOrder->refresh()->status)->toBe(SupplierOrderStatus::Delivered)
        ->and($this->bakeryOrder->refresh()->status)->toBe(SupplierOrderStatus::Pending); // untouched

    Notification::assertSentToTimes($this->customer, SupplierOrderStatusUpdated::class, 4);
});

it('refuses to skip steps', function () {
    $this->actingAs(supplierOwner($this->farm))
        ->patch(route('supplier.orders.status', $this->farmOrder), ['status' => 'delivered'])
        ->assertSessionHasErrors('status');
});

it('lets customers see only their own orders', function () {
    $this->actingAs($this->customer)->get(route('orders.show', $this->order))
        ->assertOk()
        ->assertSee($this->farm->name)
        ->assertSee($this->bakery->name);

    $this->actingAs(User::factory()->customer()->create())
        ->get(route('orders.show', $this->order))
        ->assertForbidden();
});

it('keeps suppliers out of the buying flow', function () {
    $this->actingAs(supplierOwner($this->farm))->get(route('cart.index'))->assertForbidden();
});

it('releases stock of orders that were never paid', function () {
    $product = Product::factory()->for($this->farm)->create(['stock' => 5]);
    $unpaid = placeOrder([$product->id => 2]);
    expect($product->refresh()->stock)->toBe(3);

    $this->travel(1)->hours();
    $this->artisan('orders:release-unpaid')->assertSuccessful();

    expect($unpaid->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($product->refresh()->stock)->toBe(5)
        ->and($this->order->refresh()->status)->toBe(OrderStatus::Paid); // paid order untouched
});

it('does not let another customer change my basket', function () {
    $mine = User::factory()->customer()->create();
    $item = app(CartService::class)->add($mine, Product::factory()->for($this->farm)->create());

    $this->actingAs($this->customer)->delete(route('cart.destroy', $item))->assertForbidden();
    expect($item->fresh())->not->toBeNull();
});
