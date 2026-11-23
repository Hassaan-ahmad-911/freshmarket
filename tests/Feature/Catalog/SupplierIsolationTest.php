<?php

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;

/*
| The most important security rule of a marketplace: supplier A must never
| see or change supplier B's data — not even by typing a URL by hand.
*/

beforeEach(function () {
    $this->storeA = Supplier::factory()->create();
    $this->storeB = Supplier::factory()->create();
    $this->ownerA = supplierOwner($this->storeA);
    $this->productOfB = Product::factory()->for($this->storeB)->create(['name' => 'Secret Product of B']);
});

it('only lists the supplier\'s own products', function () {
    Product::factory()->for($this->storeA)->create(['name' => 'My Own Product']);

    $this->actingAs($this->ownerA)
        ->get(route('supplier.products.index'))
        ->assertOk()
        ->assertSee('My Own Product')
        ->assertDontSee('Secret Product of B');
});

it('blocks every write action on another supplier\'s product', function (string $method, string $route, array $data) {
    $this->actingAs($this->ownerA)
        ->{$method}(route($route, $this->productOfB), $data)
        ->assertForbidden();

    expect($this->productOfB->fresh())
        ->not->toBeNull()
        ->name->toBe('Secret Product of B')
        ->is_active->toBeTrue();
})->with([
    'view edit form' => ['get', 'supplier.products.edit', []],
    'update' => ['put', 'supplier.products.update', ['name' => 'Hacked', 'category_id' => 1, 'price' => '1', 'stock' => 1, 'low_stock_threshold' => 1]],
    'toggle' => ['patch', 'supplier.products.toggle', []],
    'delete' => ['delete', 'supplier.products.destroy', []],
]);

it('blocks staff of one store from another store\'s products', function () {
    $staffA = supplierStaff($this->storeA);

    $this->actingAs($staffA)->delete(route('supplier.products.destroy', $this->productOfB))->assertForbidden();

    expect($this->productOfB->fresh())->not->toBeNull();
});

it('cannot delete another store\'s image through its own product URL', function () {
    $myProduct = Product::factory()->for($this->storeA)->create();
    $imageOfB = $this->productOfB->images()->create(['path' => 'products/b.jpg', 'sort_order' => 1]);

    // Scoped bindings: image #X does not belong to my product → 404, nothing deleted.
    $this->actingAs($this->ownerA)
        ->delete(route('supplier.products.images.destroy', [$myProduct, $imageOfB]))
        ->assertNotFound();

    expect($imageOfB->fresh())->not->toBeNull();
});

it('creates products only in the supplier\'s own store, ignoring a forged supplier_id', function () {
    $this->actingAs($this->ownerA)->post(route('supplier.products.store'), [
        'supplier_id' => $this->storeB->id, // attacker tries to plant a product in store B
        'name' => 'Planted',
        'category_id' => Category::factory()->create()->id,
        'price' => '1.00',
        'stock' => 1,
        'low_stock_threshold' => 0,
    ]);

    expect(Product::where('name', 'Planted')->sole()->supplier_id)->toBe($this->storeA->id);
});
