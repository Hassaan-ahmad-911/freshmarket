<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public const CATEGORIES = [
        ['Fruit & Veg', '🥕', 'Seasonal fruit and vegetables, picked fresh.'],
        ['Bakery', '🍞', 'Bread, pastries and cakes baked daily.'],
        ['Dairy & Eggs', '🧀', 'Milk, cheese, yoghurt and free-range eggs.'],
        ['Meat & Fish', '🥩', 'Responsibly raised meat and fresh fish.'],
        ['Pantry', '🫙', 'Honey, preserves, oils, grains and more.'],
        ['Drinks', '🧃', 'Juices, coffee, tea and soft drinks.'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $position => [$name, $icon, $description]) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon, 'description' => $description, 'sort_order' => $position, 'is_active' => true],
            );
        }
    }
}
