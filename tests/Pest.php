<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot Laravel and get a fresh in-memory SQLite database
| (with roles seeded — see tests/TestCase.php) for every test.
|
| Unit tests boot Laravel too (so helpers like config() work) but never
| touch the database.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');
