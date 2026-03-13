<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperationalTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::with(['products' => function ($query) {
            $query->where('active', true)->orderBy('id');
        }])->get();

        if ($restaurants->isEmpty()) {
            $this->command->warn('No restaurants found. Run TestDataSeeder first.');
            return;
        }

        $superadmin = User::where('email', 'superadmin@scan2order.local')->first();
        $adminRoleId = Role::where('name', 'admin')->value('id');
        $staffRoleId = Role::where('name', 'staff')->value('id');
        $clienteRoleId = Role::where('name', 'cliente')->value('id');

        if (!$adminRoleId || !$staffRoleId || !$clienteRoleId) {
            $this->command->warn('Missing roles. Run RolePermissionSeeder first.');
            return;
        }

        $adminUser = User::updateOrCreate(
            ['email' => 'admin.demo@scan2order.local'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('admin123'),
                'phone' => '611111111',
                'role_id' => $adminRoleId,
                'status' => 'active',
                'created_by' => $superadmin?->id,
            ]
        );

        $staffUser = User::updateOrCreate(
            ['email' => 'staff.demo@scan2order.local'],
            [
                'name' => 'Staff Demo',
                'password' => Hash::make('staff123'),
                'phone' => '622222222',
                'role_id' => $staffRoleId,
                'status' => 'active',
                'created_by' => $superadmin?->id,
            ]
        );

        $client = User::updateOrCreate(
            ['email' => 'cliente.demo@scan2order.local'],
            [
                'name' => 'Cliente Demo',
                'password' => Hash::make('cliente123'),
                'phone' => '633333333',
                'role_id' => $clienteRoleId,
                'status' => 'active',
                'created_by' => $superadmin?->id,
            ]
        );

        $primaryRestaurant = $restaurants->first();
        if ($primaryRestaurant) {
            $adminUser->restaurants()->syncWithoutDetaching([
                $primaryRestaurant->id => ['role_id' => $adminRoleId],
            ]);

            $staffUser->restaurants()->syncWithoutDetaching([
                $primaryRestaurant->id => ['role_id' => $staffRoleId],
            ]);
        }

        $now = Carbon::now();

        $statusTemplates = [
            ['order_status' => 'pending', 'payment_status' => null, 'payment_method' => null, 'hours_ago' => 1],
            ['order_status' => 'processing', 'payment_status' => 'pending', 'payment_method' => 'cash', 'hours_ago' => 2],
            ['order_status' => 'completed', 'payment_status' => 'pending', 'payment_method' => 'cash', 'hours_ago' => 3],
            ['order_status' => 'paid', 'payment_status' => 'succeeded', 'payment_method' => 'cash', 'hours_ago' => 4],
            ['order_status' => 'processing', 'payment_status' => 'succeeded', 'payment_method' => 'stripe', 'hours_ago' => 5],
            ['order_status' => 'completed', 'payment_status' => 'succeeded', 'payment_method' => 'stripe', 'hours_ago' => 6],
            ['order_status' => 'cancelled', 'payment_status' => null, 'payment_method' => null, 'hours_ago' => 7],
        ];

        $createdOrders = 0;
        $createdPayments = 0;
        $createdItems = 0;

        foreach ($restaurants as $restaurantIndex => $restaurant) {
            $products = $restaurant->products;
            if ($products->count() < 3) {
                continue;
            }

            foreach ($statusTemplates as $templateIndex => $template) {
                $baseDate = $now->copy()->subDays(($restaurantIndex + $templateIndex) % 7)->subHours($template['hours_ago']);

                $selectedProducts = $products->shuffle()->take(3)->values();

                $order = Order::create([
                    'restaurant_id' => $restaurant->id,
                    'user_id' => $client->id,
                    'table_id' => null,
                    'type' => $templateIndex % 2 === 0 ? 'local' : 'delivery',
                    'status' => $template['order_status'],
                    'total' => 0,
                    'notes' => 'Pedido de prueba generado automáticamente',
                    'created_at' => $baseDate,
                    'updated_at' => $baseDate,
                ]);

                $total = 0;

                foreach ($selectedProducts as $itemIndex => $product) {
                    $quantity = ($itemIndex % 3) + 1;
                    $unitPrice = (float) $product->price;
                    $subtotal = $quantity * $unitPrice;
                    $total += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'created_at' => $baseDate,
                        'updated_at' => $baseDate,
                    ]);

                    $createdItems++;
                }

                $order->update([
                    'total' => round($total, 2),
                    'updated_at' => $baseDate,
                ]);

                if ($template['payment_status']) {
                    Payment::create([
                        'order_id' => $order->id,
                        'method' => $template['payment_method'],
                        'provider' => $template['payment_method'] === 'stripe' ? 'stripe' : 'none',
                        'provider_payment_id' => $template['payment_method'] === 'stripe' ? 'seed_' . uniqid() : null,
                        'amount' => round($total, 2),
                        'currency' => 'eur',
                        'status' => $template['payment_status'],
                        'paid_at' => $template['payment_status'] === 'succeeded' ? $baseDate->copy()->addMinutes(10) : null,
                        'created_at' => $baseDate,
                        'updated_at' => $baseDate,
                    ]);

                    $createdPayments++;
                }

                $createdOrders++;
            }
        }

        $this->command->info('Operational test data created successfully!');
        $this->command->info("- {$createdOrders} orders created");
        $this->command->info("- {$createdItems} order items created");
        $this->command->info("- {$createdPayments} payments created");
        $this->command->info('Demo login users ready:');
        $this->command->info('- Superadmin: superadmin@scan2order.local / superadmin123');
        $this->command->info('- Admin: admin.demo@scan2order.local / admin123');
        $this->command->info('- Staff: staff.demo@scan2order.local / staff123');
        $this->command->info('- Cliente: cliente.demo@scan2order.local / cliente123');
        if ($primaryRestaurant) {
            $this->command->info("- Admin/Staff assigned restaurant: {$primaryRestaurant->name} (#{$primaryRestaurant->id})");
        }
    }
}
