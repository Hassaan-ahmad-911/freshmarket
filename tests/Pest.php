<?php

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

/** A staff member working for the given store. */
function supplierStaff(Supplier $supplier): User
{
    $staff = User::factory()->supplierStaff()->create();
    $staff->forceFill(['supplier_id' => $supplier->id])->save();

    return $staff;
}
