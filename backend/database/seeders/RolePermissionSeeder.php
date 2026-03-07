<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $superadmin = Role::firstOrCreate(['name' => 'superadmin'], ['is_global' => true]);
        $admin = Role::firstOrCreate(['name' => 'admin'], ['is_global' => true]);
        $caja = Role::firstOrCreate(['name' => 'caja'], ['is_global' => true]);
        $cocina = Role::firstOrCreate(['name' => 'cocina'], ['is_global' => true]);
        $cliente = Role::firstOrCreate(['name' => 'cliente'], ['is_global' => false]);

        // Create permissions
        $permissions = [
            // Admin permissions
            'create_restaurant' => 'Crear restaurante',
            'edit_restaurant' => 'Editar restaurante',
            'delete_restaurant' => 'Eliminar restaurante',
            'manage_users' => 'Gestionar usuarios',
            'manage_products' => 'Gestionar productos',
            'manage_categories' => 'Gestionar categorías',
            'manage_tables' => 'Gestionar mesas',
            'view_reports' => 'Ver reportes',

            // Caja permissions
            'manage_payments' => 'Gestionar pagos',
            'view_orders' => 'Ver órdenes',

            // Cocina permissions
            'manage_orders' => 'Gestionar órdenes',

            // Cliente permissions
            'place_order' => 'Realizar orden',
            'view_own_orders' => 'Ver mis órdenes',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name]);
        }

        // Assign permissions to roles
        // Superadmin - all permissions
        $allPermissions = Permission::all();
        $superadmin->permissions()->sync($allPermissions);

        // Admin
        $adminPerms = Permission::whereIn('name', [
            'manage_users',
            'manage_products',
            'manage_categories',
            'manage_tables',
            'view_orders',
            'view_reports',
        ])->get();
        $admin->permissions()->sync($adminPerms);

        // Caja
        $cajaPerms = Permission::whereIn('name', [
            'manage_payments',
            'view_orders',
        ])->get();
        $caja->permissions()->sync($cajaPerms);

        // Cocina
        $cocinaPerms = Permission::whereIn('name', [
            'manage_orders',
            'view_orders',
        ])->get();
        $cocina->permissions()->sync($cocinaPerms);

        // Cliente
        $clientePerms = Permission::whereIn('name', [
            'place_order',
            'view_own_orders',
        ])->get();
        $cliente->permissions()->sync($clientePerms);

        // Create superadmin user
        User::updateOrCreate(
            ['email' => 'superadmin@scan2order.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'phone' => '000000000',
                'role_id' => $superadmin->id,
                'status' => 'active',
            ]
        );
    }
}
