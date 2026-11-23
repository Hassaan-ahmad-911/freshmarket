<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo marketplace (password for every account: "password").
 *
 *  - Green Valley Farm  (owner@freshmarket.test, staff@freshmarket.test) — approved, 10% commission
 *  - Sunrise Bakery     (baker@freshmarket.test)                         — approved, 12% commission
 *  - Hilltop Dairy      (pending@freshmarket.test)                       — waiting for approval
 *
 * Both approved stores deliver to Camden, so one basket can hold items from both.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesAndPermissionsSeeder::class, CategorySeeder::class]);

        User::factory()->admin()->create(['name' => 'Platform Admin', 'email' => 'admin@freshmarket.test']);
        User::factory()->customer()->create(['name' => 'Casey Customer', 'email' => 'customer@freshmarket.test']);

        $farm = $this->store('owner@freshmarket.test', 'Sam Farmer', [
            'name' => 'Green Valley Farm',
            'slug' => 'green-valley-farm',
            'description' => 'Family farm growing seasonal vegetables and fruit since 1987.',
            'min_order_amount' => 1000,
            'commission_bps' => null, // platform default (10%)
        ], ['Camden', 'Islington', 'Hackney']);

        User::factory()->supplierStaff()->create(['name' => 'Sky Staff', 'email' => 'staff@freshmarket.test'])
            ->forceFill(['supplier_id' => $farm->id])->save();

        $bakery = $this->store('baker@freshmarket.test', 'Bea Baker', [
            'name' => 'Sunrise Bakery',
            'slug' => 'sunrise-bakery',
            'description' => 'Sourdough and pastries, baked before sunrise.',
            'min_order_amount' => 0,
            'commission_bps' => 1200, // negotiated 12%
        ], ['Camden', 'Soho']);

        $pendingOwner = User::factory()->supplierOwner()->create(['name' => 'Penny Pending', 'email' => 'pending@freshmarket.test']);
        Supplier::factory()->pending()->for($pendingOwner, 'owner')
            ->create(['name' => 'Hilltop Dairy', 'slug' => 'hilltop-dairy', 'description' => 'Small-batch cheese and yoghurt.']);

        $this->products($farm, [
            ['Heirloom Tomatoes', 'fruit-veg', 450, 'kg', 40],
            ['Rainbow Carrots', 'fruit-veg', 275, 'bunch', 25],
            ['Honeycrisp Apples', 'fruit-veg', 380, 'kg', 3],          // low stock
            ['Free-Range Eggs', 'dairy-eggs', 420, 'dozen', 30],
            ['Wildflower Honey', 'pantry', 899, '340 g jar', 12],
            ['Cold-Pressed Apple Juice', 'drinks', 525, '1 L', 0],     // out of stock
        ]);

        $this->products($bakery, [
            ['Country Sourdough', 'bakery', 650, 'loaf', 20],
            ['Butter Croissant', 'bakery', 325, 'each', 40],
            ['Cinnamon Buns', 'bakery', 1100, 'box of 4', 15],
            ['Seeded Rye', 'bakery', 575, 'loaf', 10],
        ]);
    }

    private function store(string $email, string $ownerName, array $attributes, array $areas): Supplier
    {
        $owner = User::factory()->supplierOwner()->create(['name' => $ownerName, 'email' => $email]);

        return Supplier::factory()->for($owner, 'owner')->deliveringTo($areas)->create($attributes);
    }

    private function products(Supplier $supplier, array $rows): void
    {
        $categories = Category::pluck('id', 'slug');

        foreach ($rows as [$name, $category, $price, $unit, $stock]) {
            Product::factory()->for($supplier)->create([
                'name' => $name,
                'category_id' => $categories[$category],
                'price' => $price,
                'unit' => $unit,
                'stock' => $stock,
            ]);
        }
    }
}
