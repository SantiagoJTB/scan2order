<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => $this->faker->word,
        ];
    }
}
