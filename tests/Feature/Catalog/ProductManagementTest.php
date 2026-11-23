<?php

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->supplier = Supplier::factory()->create();
    $this->owner = supplierOwner($this->supplier);
    $this->category = Category::factory()->create();
});

function productFormData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sourdough Loaf',
        'category_id' => test()->category->id,
        'description' => 'Slow-fermented for 36 hours.',
        'price' => '5.5',
        'unit' => 'each',
        'stock' => 20,
        'low_stock_threshold' => 4,
        'is_active' => '1',
    ], $overrides);
}

it('creates a product with images and stores the price in cents', function () {
    $this->actingAs($this->owner)
        ->post(route('supplier.products.store'), productFormData([
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $product = Product::sole();

    expect($product->supplier_id)->toBe($this->supplier->id)
        ->and($product->getRawOriginal('price'))->toBe(550)   // "5.5" → 550 cents in the database
        ->and($product->price->format())->toBe('$5.50')
        ->and($product->images)->toHaveCount(2)
        ->and($product->slug)->toStartWith('sourdough-loaf-');

    Storage::disk('public')->assertExists($product->images->pluck('path')->all());
});

it('lets staff create products too', function () {
    $this->actingAs(supplierStaff($this->supplier))
        ->post(route('supplier.products.store'), productFormData())
        ->assertSessionHasNoErrors();

    expect(Product::sole()->supplier_id)->toBe($this->supplier->id);
});

it('validates product input', function (array $data, string $field) {
    $this->actingAs($this->owner)
        ->post(route('supplier.products.store'), productFormData($data))
        ->assertSessionHasErrors($field);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'zero price' => [['price' => '0.00'], 'price'],
    'negative price' => [['price' => '-1'], 'price'],
    'three decimals' => [['price' => '1.999'], 'price'],
    'negative stock' => [['stock' => -1], 'stock'],
    'unknown category' => [['category_id' => 9999], 'category_id'],
    'not an image' => [['images' => [UploadedFile::fake()->create('virus.exe', 10)]], 'images.0'],
]);

it('refuses more than the maximum number of images', function () {
    $product = Product::factory()->for($this->supplier)->hasImages(Product::MAX_IMAGES)->create();

    $this->actingAs($this->owner)
        ->put(route('supplier.products.update', $product), productFormData(['images' => [UploadedFile::fake()->image('extra.jpg')]]))
        ->assertSessionHasErrors('images');
});

it('updates a product and can toggle it off', function () {
    $product = Product::factory()->for($this->supplier)->create(['category_id' => $this->category->id]);

    $this->actingAs($this->owner)
        ->put(route('supplier.products.update', $product), productFormData(['name' => 'Rye Loaf', 'price' => '6']))
        ->assertSessionHasNoErrors();

    expect($product->refresh()->name)->toBe('Rye Loaf')->and($product->price->amount)->toBe(600);

    $this->actingAs($this->owner)->patch(route('supplier.products.toggle', $product));
    expect($product->refresh()->is_active)->toBeFalse();
});

it('soft-deletes products so order history keeps working', function () {
    $product = Product::factory()->for($this->supplier)->create();

    $this->actingAs($this->owner)->delete(route('supplier.products.destroy', $product))->assertRedirect();

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
});

it('deletes a single image and its file', function () {
    $product = Product::factory()->for($this->supplier)->create();
    $path = UploadedFile::fake()->image('x.jpg')->store("products/{$this->supplier->id}", 'public');
    $image = $product->images()->create(['path' => $path, 'sort_order' => 1]);

    $this->actingAs($this->owner)->delete(route('supplier.products.images.destroy', [$product, $image]))->assertRedirect();

    expect($product->images()->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});
