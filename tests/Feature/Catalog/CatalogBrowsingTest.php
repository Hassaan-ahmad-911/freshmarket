<?php

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;

beforeEach(function () {
    $this->fruit = Category::factory()->create(['name' => 'Fruit', 'slug' => 'fruit']);
    $this->bakery = Category::factory()->create(['name' => 'Bakery', 'slug' => 'bakery']);

    $this->farm = Supplier::factory()->deliveringTo(['Camden'])->create(['name' => 'Farm']);
    $this->baker = Supplier::factory()->deliveringTo(['Soho'])->create(['name' => 'Baker']);
});

it('shows only active products of approved stores', function () {
    Product::factory()->for($this->farm)->create(['name' => 'Visible Apple']);
    Product::factory()->for($this->farm)->inactive()->create(['name' => 'Hidden Pear']);
    Product::factory()->for(Supplier::factory()->pending())->create(['name' => 'Pending Plum']);
    Product::factory()->for(Supplier::factory()->suspended())->create(['name' => 'Suspended Peach']);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Visible Apple')
        ->assertDontSee('Hidden Pear')
        ->assertDontSee('Pending Plum')
        ->assertDontSee('Suspended Peach');
});

it('searches across all suppliers', function () {
    Product::factory()->for($this->farm)->create(['name' => 'Green Apple']);
    Product::factory()->for($this->baker)->create(['name' => 'Apple Pie']);
    Product::factory()->for($this->baker)->create(['name' => 'Croissant']);

    $this->get(route('products.index', ['q' => 'apple']))
        ->assertSee('Green Apple')
        ->assertSee('Apple Pie')
        ->assertDontSee('Croissant');
});

it('treats % in a search as a normal character', function () {
    Product::factory()->for($this->farm)->create(['name' => '100% Orange Juice']);
    Product::factory()->for($this->farm)->create(['name' => 'Lemonade']);

    $this->get(route('products.index', ['q' => '%']))
        ->assertSee('100% Orange Juice')
        ->assertDontSee('Lemonade');
});

it('filters by category, price range and delivery area', function () {
    Product::factory()->for($this->farm)->create(['name' => 'Cheap Apple', 'category_id' => $this->fruit->id, 'price' => 150]);
    Product::factory()->for($this->farm)->create(['name' => 'Fancy Mango', 'category_id' => $this->fruit->id, 'price' => 900]);
    Product::factory()->for($this->baker)->create(['name' => 'Baguette', 'category_id' => $this->bakery->id, 'price' => 300]);

    $this->get(route('products.index', ['category' => 'fruit']))
        ->assertSee('Cheap Apple')->assertSee('Fancy Mango')->assertDontSee('Baguette');

    $this->get(route('products.index', ['min_price' => '2', 'max_price' => '5']))
        ->assertSee('Baguette')->assertDontSee('Cheap Apple')->assertDontSee('Fancy Mango');

    $this->get(route('products.index', ['area' => 'soho']))
        ->assertSee('Baguette')->assertDontSee('Cheap Apple');
});

it('sorts by price', function () {
    Product::factory()->for($this->farm)->create(['name' => 'Expensive', 'price' => 999]);
    Product::factory()->for($this->farm)->create(['name' => 'Cheap', 'price' => 100]);

    $this->get(route('products.index', ['sort' => 'price_asc']))->assertSeeInOrder(['Cheap', 'Expensive']);
    $this->get(route('products.index', ['sort' => 'price_desc']))->assertSeeInOrder(['Expensive', 'Cheap']);
});

it('shows a product page, and 404s for hidden products', function () {
    $visible = Product::factory()->for($this->farm)->create(['name' => 'Plums', 'price' => 325]);
    $hidden = Product::factory()->for($this->farm)->inactive()->create();

    $this->get(route('products.show', $visible))->assertOk()->assertSee('Plums')->assertSee('$3.25');
    $this->get(route('products.show', $hidden))->assertNotFound();
});

it('shows a store page with only that store\'s products', function () {
    Product::factory()->for($this->farm)->create(['name' => 'Farm Eggs']);
    Product::factory()->for($this->baker)->create(['name' => 'Bakery Bread']);

    $this->get(route('stores.show', $this->farm))
        ->assertOk()
        ->assertSee('Farm Eggs')
        ->assertDontSee('Bakery Bread');
});

it('lists only approved stores', function () {
    Supplier::factory()->pending()->create(['name' => 'Not Yet Approved Store']);

    $this->get(route('stores.index'))
        ->assertOk()
        ->assertSee('Farm')
        ->assertDontSee('Not Yet Approved Store');
});

it('rejects a malformed price filter', function () {
    $this->get(route('products.index', ['min_price' => 'abc']))->assertSessionHasErrors('min_price');
});

it('renders the home page', function () {
    Product::factory()->for($this->farm)->create(['name' => 'Fresh Figs']);

    $this->get(route('home'))->assertOk()->assertSee('Fresh Figs')->assertSee('Fruit');
});
