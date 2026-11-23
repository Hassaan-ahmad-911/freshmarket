<?php

use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->supplier = Supplier::factory()->create();
    $this->owner = supplierOwner($this->supplier);
});

it('lets the owner update the store profile', function () {
    $this->actingAs($this->owner)
        ->put(route('supplier.store.update'), [
            'name' => 'Renamed Farm',
            'description' => 'Now with goats.',
            'min_order_amount' => '15.50',
            'delivery_areas' => "Camden, Islington\nHackney, camden",
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->supplier->refresh();

    expect($this->supplier->name)->toBe('Renamed Farm')
        ->and($this->supplier->min_order_amount->amount)->toBe(1550)           // "15.50" → 1550 cents
        ->and($this->supplier->deliveryAreas->pluck('name')->all())->toBe(['Camden', 'Hackney', 'Islington']) // duplicates removed
        ->and($this->supplier->deliversTo('  HACKNEY '))->toBeTrue();

    Storage::disk('public')->assertExists($this->supplier->logo_path);
});

it('replaces the old logo file and removes delivery areas that were deleted', function () {
    $this->actingAs($this->owner)->put(route('supplier.store.update'), [
        'name' => 'Farm', 'delivery_areas' => 'Camden, Soho',
        'logo' => UploadedFile::fake()->image('one.png'),
    ]);
    $oldLogo = $this->supplier->refresh()->logo_path;

    $this->actingAs($this->owner)->put(route('supplier.store.update'), [
        'name' => 'Farm', 'delivery_areas' => 'Soho',
        'logo' => UploadedFile::fake()->image('two.png'),
    ]);

    Storage::disk('public')->assertMissing($oldLogo);
    expect($this->supplier->refresh()->deliveryAreas->pluck('name')->all())->toBe(['Soho']);
});

it('rejects an invalid minimum order amount', function (string $amount) {
    $this->actingAs($this->owner)
        ->put(route('supplier.store.update'), ['name' => 'Farm', 'min_order_amount' => $amount])
        ->assertSessionHasErrors('min_order_amount');
})->with(['-5', '12.345', 'ten', '1,000']);

it('does not let staff edit the store profile', function () {
    $staff = supplierStaff($this->supplier);

    $this->actingAs($staff)->get(route('supplier.store.edit'))->assertForbidden();
    $this->actingAs($staff)->put(route('supplier.store.update'), ['name' => 'Hijacked'])->assertForbidden();

    expect($this->supplier->refresh()->name)->not->toBe('Hijacked');
});
