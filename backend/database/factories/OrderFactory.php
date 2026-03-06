<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'user_id' => null,
            'table_id' => null,
            'type' => 'local',
            'status' => 'pending',
            'total' => 0,
            'notes' => null,
        ];
    }
}
