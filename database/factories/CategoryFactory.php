<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon' => fake()->randomElement(['🥕', '🍞', '🧀', '🥩', '🍎', '🥛']),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
