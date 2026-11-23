<?php

namespace Database\Factories;

use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Supplier::factory()->create()            → approved store + its owner
 * Supplier::factory()->pending()->create() → store waiting for approval
 *
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' '.fake()->randomElement(['Farm', 'Bakery', 'Dairy', 'Grocers', 'Market']);

        return [
            'owner_id' => User::factory()->supplierOwner(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraph(),
            'status' => SupplierStatus::Approved,
            'min_order_amount' => 0,
            'approved_at' => now(),
        ];
    }

    public function configure(): static
    {
        // Link the owner back to their store (users.supplier_id).
        return $this->afterCreating(function (Supplier $supplier) {
            $supplier->owner->forceFill(['supplier_id' => $supplier->id])->save();
        });
    }

    public function pending(): static
    {
        return $this->state(['status' => SupplierStatus::Pending, 'approved_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => SupplierStatus::Suspended, 'suspended_at' => now()]);
    }

    /** @param  list<string>  $areas */
    public function deliveringTo(array $areas): static
    {
        return $this->afterCreating(function (Supplier $supplier) use ($areas) {
            foreach ($areas as $area) {
                $supplier->deliveryAreas()->create(['name' => $area, 'normalized' => mb_strtolower($area)]);
            }
        });
    }
}
