<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Seed roles and permissions for tests.
     */
    protected function seedRolesAndPermissions()
    {
        // simple role creation
        \App\Models\Role::insert([
            ['name' => 'superadmin', 'is_global' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'is_global' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cocina', 'is_global' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'caja', 'is_global' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cliente', 'is_global' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // permissions can be seeded similarly if needed
    }
}
