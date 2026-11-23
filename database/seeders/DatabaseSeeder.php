<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Payouts\Services\DemoConnectedAccounts;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Throwable;

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

        $this->connectStripeAccounts([$farm, $bakery]);

        $pendingOwner = User::factory()->supplierOwner()->create(['name' => 'Penny Pending', 'email' => 'pending@freshmarket.test']);
        $dairy = Supplier::factory()->pending()->for($pendingOwner, 'owner')
            ->deliveringTo(['Camden', 'Islington'])
            ->create(['name' => 'Hilltop Dairy', 'slug' => 'hilltop-dairy', 'description' => 'Small-batch cheese, yoghurt and milk from our own herd.']);

        // Photos: free-licence images from Wikimedia Commons (see docs/IMAGE_CREDITS.md).
        DemoImages::categories();
        foreach ([$farm, $bakery, $dairy] as $supplier) {
            DemoImages::supplier($supplier);
        }

        $this->products($farm, [
            ['Heirloom Tomatoes', 'fruit-veg', 450, 'kg', 40, 'heirloom-tomatoes', 'Sweet, juicy heritage varieties in every colour, picked ripe this morning.'],
            ['Rainbow Carrots', 'fruit-veg', 275, 'bunch', 25, 'rainbow-carrots', 'Crunchy carrots with their tops on, grown in our sandy loam.'],
            ['Honeycrisp Apples', 'fruit-veg', 380, 'kg', 3, 'honeycrisp-apples', 'Crisp, explosively juicy apples with a honeyed finish.'],          // low stock
            ['Free-Range Eggs', 'dairy-eggs', 420, 'dozen', 30, 'free-range-eggs', 'From hens that roam our orchard all day. Rich, golden yolks.'],
            ['Wildflower Honey', 'pantry', 899, '340 g jar', 12, 'wildflower-honey', 'Raw honey from our hives at the edge of the wildflower meadow.'],
            ['Cold-Pressed Apple Juice', 'drinks', 525, '1 L', 0, 'apple-juice', 'Pressed from a blend of our own apples. Nothing added.'],     // out of stock
        ]);

        $this->products($bakery, [
            ['Country Sourdough', 'bakery', 650, 'loaf', 20, 'country-sourdough', 'Naturally leavened for 36 hours, with a blistered, crackling crust.'],
            ['Butter Croissant', 'bakery', 325, 'each', 40, 'butter-croissant', 'Laminated by hand with cultured butter. Shatteringly flaky.'],
            ['Cinnamon Buns', 'bakery', 1100, 'box of 4', 15, 'cinnamon-buns', 'Soft, swirled buns with a vanilla glaze. Best warm.'],
            ['Seeded Rye', 'bakery', 575, 'loaf', 10, 'seeded-rye', 'Dense, tangy rye packed with sunflower and pumpkin seeds.'],
        ]);

        // Hilltop Dairy is still pending: its products appear once an admin approves the store.
        $this->products($dairy, [
            ['Aged Cheddar', 'dairy-eggs', 790, '250 g', 18, 'aged-cheddar', 'Cave-aged for 18 months: crumbly, nutty and sharp.'],
            ['Greek Yoghurt', 'dairy-eggs', 450, '500 g', 24, 'greek-yoghurt', 'Thick, strained yoghurt made with whole milk.'],
            ['Whole Milk', 'dairy-eggs', 180, '1 L', 30, 'whole-milk', 'Gently pasteurised, never homogenised. Shake before pouring.'],
        ]);
    }

    private function store(string $email, string $ownerName, array $attributes, array $areas): Supplier
    {
        $owner = User::factory()->supplierOwner()->create(['name' => $ownerName, 'email' => $email]);

        return Supplier::factory()->for($owner, 'owner')->deliveringTo($areas)->create($attributes);
    }

    /**
     * With STRIPE_SECRET: real Stripe TEST connected accounts (reused across re-seeds).
     * Without: fake account ids for the offline FakeGateway.
     */
    private function connectStripeAccounts(array $suppliers): void
    {
        $accounts = DemoConnectedAccounts::make();

        foreach ($suppliers as $supplier) {
            try {
                $accounts->provision($supplier, fn (string $line) => $this->command?->info("  Stripe → {$line}"));
            } catch (Throwable $e) {
                $this->command?->warn("  Could not create a Stripe account for {$supplier->name}: {$e->getMessage()}");
                $this->command?->warn('  Fix the problem above, then run: php artisan stripe:demo-accounts');
            }
        }
    }

    private function products(Supplier $supplier, array $rows): void
    {
        $categories = Category::pluck('id', 'slug');

        foreach ($rows as [$name, $category, $price, $unit, $stock, $image, $description]) {
            $product = Product::factory()->for($supplier)->create([
                'name' => $name,
                'category_id' => $categories[$category],
                'price' => $price,
                'unit' => $unit,
                'stock' => $stock,
                'description' => $description,
            ]);

            DemoImages::product($product, $image);
        }
    }
}
