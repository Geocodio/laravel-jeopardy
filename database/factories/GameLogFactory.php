<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameLogFactory extends Factory
{
    protected $model = GameLog::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'action' => fake()->randomElement(['game_created', 'teams_created', 'board_generated', 'clue_selected', 'answer_submitted']),
            'details' => [],
        ];
    }
}
