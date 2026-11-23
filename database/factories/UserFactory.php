<?php

namespace Database\Factories;

use App\Domain\Shared\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /** User::factory()->withRole(Role::Customer)->create() */
    public function withRole(Role $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }

    public function admin(): static
    {
        return $this->withRole(Role::PlatformAdmin);
    }

    public function supplierOwner(): static
    {
        return $this->withRole(Role::SupplierOwner);
    }

    public function supplierStaff(): static
    {
        return $this->withRole(Role::SupplierStaff);
    }

    public function customer(): static
    {
        return $this->withRole(Role::Customer);
    }
}
