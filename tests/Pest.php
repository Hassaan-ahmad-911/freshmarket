<?php

use App\Domain\Orders\Actions\PlaceOrder;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\DeliveryDetails;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot Laravel and get a fresh in-memory SQLite database
| (with roles seeded — see tests/TestCase.php) for every test.
|
| Unit tests boot Laravel too (so helpers like config() work) but never
| touch the database.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** The owner of a brand-new (approved, unless told otherwise) store. */
function supplierOwner(?Supplier $supplier = null): User
{
    return ($supplier ?? Supplier::factory()->create())->owner->refresh();
}

/**
 * Place an order directly through the PlaceOrder action.
 *
 * @param  array<int, int>  $lines  product_id => quantity
 */
function placeOrder(array $lines, ?User $customer = null, string $area = 'Camden'): Order
{
    return app(PlaceOrder::class)->handle(
        $customer ?? User::factory()->customer()->create(),
        $lines,
        new DeliveryDetails('Casey Customer', '555-0100', '1 High Street', $area),
    );
}

/** A store that delivers to Camden with the given commission (basis points). */
function camdenStore(?int $commissionBps = null, int $minOrder = 0): Supplier
{
    return Supplier::factory()->deliveringTo(['Camden'])->create([
        'commission_bps' => $commissionBps,
        'min_order_amount' => $minOrder,
    ]);
}

/** A staff member working for the given store. */
function supplierStaff(Supplier $supplier): User
{
    $staff = User::factory()->supplierStaff()->create();
    $staff->forceFill(['supplier_id' => $supplier->id])->save();

    return $staff;
}
