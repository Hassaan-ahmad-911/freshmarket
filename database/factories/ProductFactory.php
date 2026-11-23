<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = Str::title(fake()->words(2, true));

        return [
            'supplier_id' => Supplier::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => fake()->sentence(12),
            'price' => fake()->numberBetween(99, 2999), // cents
            'unit' => fake()->randomElement(['each', 'kg', '500 g', 'bunch', 'dozen']),
            'stock' => fake()->numberBetween(20, 100),
            'low_stock_threshold' => 5,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(['stock' => 0]);
    }
}
