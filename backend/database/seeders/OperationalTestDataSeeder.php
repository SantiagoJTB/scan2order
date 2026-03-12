<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

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

        $client = User::whereHas('role', function ($query) {
            $query->where('name', 'cliente');
        })->first();

        if (!$client) {
            $client = User::create([
                'name' => 'Cliente Demo',
                'email' => 'cliente.demo@scan2order.local',
                'password' => bcrypt('cliente123'),
                'phone' => '600000000',
                'role_id' => \App\Models\Role::where('name', 'cliente')->value('id'),
                'status' => 'active',
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
    }
}
