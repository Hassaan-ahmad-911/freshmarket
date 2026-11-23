<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test that uses RefreshDatabase starts with the roles and
     * permissions already created, exactly like a freshly deployed app.
     */
    protected bool $seed = true;

    protected string $seeder = RolesAndPermissionsSeeder::class;
}
