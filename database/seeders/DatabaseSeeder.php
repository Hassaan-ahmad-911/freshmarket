<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Phase 1: roles + one demo user per role (password: "password").
     * Phase 7 replaces the users below with the full demo marketplace.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        User::factory()->admin()->create(['name' => 'Platform Admin', 'email' => 'admin@freshmarket.test']);
        User::factory()->supplierOwner()->create(['name' => 'Sam Supplier', 'email' => 'owner@freshmarket.test']);
        User::factory()->supplierStaff()->create(['name' => 'Sky Staff', 'email' => 'staff@freshmarket.test']);
        User::factory()->customer()->create(['name' => 'Casey Customer', 'email' => 'customer@freshmarket.test']);
    }
}
