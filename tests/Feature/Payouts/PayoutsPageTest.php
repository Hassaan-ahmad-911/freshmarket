<?php

use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;

it('shows the owner their earnings and transfers', function () {
    $store = connectedStore();
    $order = paidOrder([Product::factory()->for($store)->create(['price' => 1000])->id => 1]);
    $transfer = $order->supplierOrders->first()->transfer;

    $this->actingAs(supplierOwner($store))
        ->get(route('supplier.payouts.index'))
        ->assertOk()
        ->assertSee('Connected and ready')
        ->assertSee($transfer->stripe_transfer_id)
        ->assertSee($transfer->amount->format());
});

it('keeps staff out of earnings and payout settings', function () {
    $store = connectedStore();
    $staff = supplierStaff($store);

    $this->actingAs($staff)->get(route('supplier.payouts.index'))->assertForbidden();
    $this->actingAs($staff)->post(route('supplier.payouts.connect'))->assertForbidden();
});

it('runs Stripe Connect onboarding for a new supplier', function () {
    $store = Supplier::factory()->create();
    $owner = supplierOwner($store);

    // FakeGateway: account is created, and the "onboarding link" goes straight to our return URL.
    $this->actingAs($owner)->post(route('supplier.payouts.connect'))
        ->assertRedirect(route('supplier.payouts.return'));

    expect($store->refresh()->stripe_account_id)->toStartWith('acct_fake_');

    $this->actingAs($owner)->get(route('supplier.payouts.return'))->assertRedirect(route('supplier.payouts.index'));

    expect($store->refresh()->canReceiveTransfers())->toBeTrue();
});
