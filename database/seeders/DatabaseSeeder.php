<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Phase 2 demo data (password for every account: "password").
     * Phase 7 replaces this with the full marketplace: 4 suppliers with images, orders, refunds.
     */
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, CategorySeeder::class]);

        User::factory()->admin()->create(['name' => 'Platform Admin', 'email' => 'admin@freshmarket.test']);
        User::factory()->customer()->create(['name' => 'Casey Customer', 'email' => 'customer@freshmarket.test']);

        // An approved store with an owner and one staff member.
        $owner = User::factory()->supplierOwner()->create(['name' => 'Sam Supplier', 'email' => 'owner@freshmarket.test']);
        $farm = Supplier::factory()
            ->for($owner, 'owner')
            ->deliveringTo(['Camden', 'Islington', 'Hackney'])
            ->create([
                'name' => 'Green Valley Farm',
                'slug' => 'green-valley-farm',
                'description' => 'Family farm growing seasonal vegetables and fruit since 1987.',
                'min_order_amount' => 1500,
            ]);
        User::factory()->supplierStaff()->create(['name' => 'Sky Staff', 'email' => 'staff@freshmarket.test'])
            ->forceFill(['supplier_id' => $farm->id])->save();

        // A store that is still waiting for approval (try approving it as the admin).
        Supplier::factory()->pending()
            ->for(User::factory()->supplierOwner()->create(['name' => 'Penny Pending', 'email' => 'pending@freshmarket.test']), 'owner')
            ->create(['name' => 'Sunrise Bakery', 'slug' => 'sunrise-bakery', 'description' => 'Sourdough and pastries, baked before sunrise.']);

        $categories = Category::pluck('id', 'slug');
        $products = [
            ['Heirloom Tomatoes', 'fruit-veg', 450, 'kg', 40],
            ['Rainbow Carrots', 'fruit-veg', 275, 'bunch', 25],
            ['Honeycrisp Apples', 'fruit-veg', 380, 'kg', 3],          // low stock
            ['Free-Range Eggs', 'dairy-eggs', 420, 'dozen', 30],
            ['Wildflower Honey', 'pantry', 899, '340 g jar', 12],
            ['Cold-Pressed Apple Juice', 'drinks', 525, '1 L', 0],     // out of stock
        ];

        foreach ($products as [$name, $category, $price, $unit, $stock]) {
            Product::factory()->for($farm)->create([
                'name' => $name,
                'category_id' => $categories[$category],
                'price' => $price,
                'unit' => $unit,
                'stock' => $stock,
            ]);
        }
    }
}
