<?php

use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Domain\Suppliers\Models\Supplier;
use App\Domain\Suppliers\Notifications\NewSupplierAwaitingApproval;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function supplierSignUpData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Olive Owner',
        'email' => 'olive@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'store_name' => 'Olive Grove Deli',
        'description' => 'Italian deli goods.',
    ], $overrides);
}

it('creates a pending store and an owner account', function () {
    $this->post(route('supplier.register'), supplierSignUpData())
        ->assertRedirect(route('supplier.dashboard'));

    $owner = User::where('email', 'olive@example.com')->firstOrFail();
    $supplier = Supplier::where('owner_id', $owner->id)->firstOrFail();

    expect($supplier->name)->toBe('Olive Grove Deli')
        ->and($supplier->slug)->toBe('olive-grove-deli')
        ->and($supplier->status)->toBe(SupplierStatus::Pending)
        ->and($owner->isSupplierOwner())->toBeTrue()
        ->and($owner->supplier_id)->toBe($supplier->id);

    $this->assertAuthenticatedAs($owner);
});

it('notifies every admin that a store is waiting for approval', function () {
    Notification::fake();
    $admins = User::factory()->admin()->count(2)->create();

    $this->post(route('supplier.register'), supplierSignUpData());

    Notification::assertSentTo($admins, NewSupplierAwaitingApproval::class);
});

it('gives each store a unique slug', function () {
    Supplier::factory()->create(['slug' => 'olive-grove-deli']);

    $this->post(route('supplier.register'), supplierSignUpData());

    expect(Supplier::latest('id')->first()->slug)->toBe('olive-grove-deli-2');
});

it('validates the sign-up form', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('supplier.register'), supplierSignUpData(['email' => 'taken@example.com', 'store_name' => '']))
        ->assertSessionHasErrors(['email', 'store_name']);

    expect(Supplier::count())->toBe(0);
});

it('lets a pending store set up products, but hides them from customers', function () {
    $supplier = Supplier::factory()->pending()->create();

    $this->actingAs(supplierOwner($supplier))
        ->get(route('supplier.dashboard'))
        ->assertOk()
        ->assertSee('waiting for approval');

    $this->get(route('stores.show', $supplier))->assertNotFound();
});
