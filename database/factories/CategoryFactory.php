<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'name' => fake()->sentence(2),
            'position' => fake()->numberBetween(1, 6),
        ];
    }
}