<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Domain\Suppliers\Models\Supplier;
use App\Domain\Suppliers\Notifications\SupplierStatusChanged;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
});

it('lets an admin approve a pending store and tells the owner', function () {
    $supplier = Supplier::factory()->pending()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.suppliers.approve', $supplier))
        ->assertRedirect();

    expect($supplier->refresh()->status)->toBe(SupplierStatus::Approved)
        ->and($supplier->approved_at)->not->toBeNull();

    Notification::assertSentTo($supplier->owner, SupplierStatusChanged::class);
});

it('does not email the owner twice when approving an already approved store', function () {
    $supplier = Supplier::factory()->create();

    $this->actingAs($this->admin)->post(route('admin.suppliers.approve', $supplier));

    Notification::assertNothingSent();
});

it('hides a suspended store and its products from customers', function () {
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->for($supplier)->create();

    $this->get(route('products.show', $product))->assertOk();

    $this->actingAs($this->admin)
        ->post(route('admin.suppliers.suspend', $supplier), ['reason' => 'Food safety review'])
        ->assertRedirect();

    expect($supplier->refresh()->status)->toBe(SupplierStatus::Suspended);
    Notification::assertSentTo($supplier->owner, SupplierStatusChanged::class, fn ($n) => $n->reason === 'Food safety review');

    auth()->logout();
    $this->get(route('products.show', $product))->assertNotFound();
    $this->get(route('stores.show', $supplier))->assertNotFound();
    $this->get(route('products.index'))->assertDontSee($product->name);
});

it('does not let suppliers or customers approve stores', function (Closure $makeUser) {
    $supplier = Supplier::factory()->pending()->create();

    $this->actingAs($makeUser())
        ->post(route('admin.suppliers.approve', $supplier))
        ->assertForbidden();

    expect($supplier->refresh()->status)->toBe(SupplierStatus::Pending);
})->with([
    'supplier owner' => [fn () => supplierOwner()],
    'customer' => [fn () => User::factory()->customer()->create()],
]);
