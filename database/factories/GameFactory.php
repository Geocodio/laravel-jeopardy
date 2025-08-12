<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(['setup', 'main_game', 'lightning_round', 'finished']),
            'current_clue_id' => null,
            'daily_double_used' => false,
        ];
    }
}