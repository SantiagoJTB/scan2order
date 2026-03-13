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
        $staff = Role::firstOrCreate(['name' => 'staff'], ['is_global' => true]);
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

            // Staff permissions (caja + cocina + ocultar productos)
            'manage_payments' => 'Gestionar pagos',
            'manage_orders' => 'Gestionar órdenes',
            'view_orders' => 'Ver órdenes',
            'hide_products_from_menu' => 'Ocultar/mostrar productos de la carta',

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

        // Staff (combines caja + cocina + menu management)
        $staffPerms = Permission::whereIn('name', [
            'manage_payments',
            'manage_orders',
            'view_orders',
            'hide_products_from_menu',
        ])->get();
        $staff->permissions()->sync($staffPerms);

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
