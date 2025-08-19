<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'name' => fake()->company(),
            'color_hex' => fake()->hexColor(),
            'score' => 0,
            'buzzer_pin' => fake()->unique()->numberBetween(1, 10),
        ];
    }
}
