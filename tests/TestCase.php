<?php

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Every test starts with the four roles in place; nothing else is seeded. */
    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;
}
