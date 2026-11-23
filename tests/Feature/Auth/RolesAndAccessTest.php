<?php

use App\Domain\Shared\Enums\Permission;
use App\Domain\Shared\Enums\Role;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;

it('seeds every role and permission defined in the enums', function () {
    foreach (Role::cases() as $role) {
        $this->assertDatabaseHas('roles', ['name' => $role->value]);
    }

    foreach (Permission::cases() as $permission) {
        $this->assertDatabaseHas('permissions', ['name' => $permission->value]);
    }
});

it('gives supplier staff fewer permissions than the owner', function () {
    $owner = User::factory()->supplierOwner()->create();
    $staff = User::factory()->supplierStaff()->create();

    expect($owner->can(Permission::ManageProducts->value))->toBeTrue()
        ->and($staff->can(Permission::ManageProducts->value))->toBeTrue()
        ->and($owner->can(Permission::ManageStaff->value))->toBeTrue()
        ->and($staff->can(Permission::ManageStaff->value))->toBeFalse()
        ->and($staff->can(Permission::ViewEarnings->value))->toBeFalse();
});

it('makes everyone who signs up through the public form a customer', function () {
    $this->post('/register', [
        'name' => 'New Shopper',
        'email' => 'shopper@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'shopper@example.com')->firstOrFail();

    expect($user->isCustomer())->toBeTrue()
        ->and($user->isSupplierUser())->toBeFalse()
        ->and($user->isPlatformAdmin())->toBeFalse();
});

it('sends each role to its own home after login', function (string $state, string $expectedRoute) {
    $user = User::factory()->{$state}()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $expectedRoute === 'dashboard'
        ? $response->assertOk()->assertSee('My Account')
        : $response->assertRedirect(route($expectedRoute));
})->with([
    'admin' => ['admin', 'admin.home'],
    'supplier owner' => ['supplierOwner', 'supplier.dashboard'],
    'supplier staff' => ['supplierStaff', 'supplier.dashboard'],
    'customer' => ['customer', 'dashboard'],
]);

it('only lets supplier users into the supplier area', function (Closure $makeUser, int $status) {
    $this->actingAs($makeUser())
        ->get(route('supplier.dashboard'))
        ->assertStatus($status);
})->with([
    'owner allowed' => [fn () => supplierOwner(), 200],
    'staff allowed' => [fn () => supplierStaff(Supplier::factory()->create()), 200],
    'customer blocked' => [fn () => User::factory()->customer()->create(), 403],
    'admin blocked' => [fn () => User::factory()->admin()->create(), 403],
    'supplier role without a store blocked' => [fn () => User::factory()->supplierOwner()->create(), 403],
]);

it('only lets platform admins into the admin area', function (string $state, int $status) {
    $this->actingAs(User::factory()->{$state}()->create())
        ->get(route('admin.home'))
        ->assertStatus($status);
})->with([
    'admin allowed' => ['admin', 200],
    'owner blocked' => ['supplierOwner', 403],
    'customer blocked' => ['customer', 403],
]);

it('redirects guests to the login page', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['dashboard', 'supplier.dashboard', 'admin.home']);
