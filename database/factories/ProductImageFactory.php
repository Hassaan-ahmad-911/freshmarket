<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductImage> */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'path' => 'products/demo/'.fake()->uuid().'.jpg',
            'sort_order' => 1,
        ];
    }
}
