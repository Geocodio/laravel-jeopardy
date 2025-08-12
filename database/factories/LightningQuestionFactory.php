<?php

namespace Database\Factories;

use App\Models\LightningQuestion;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

class LightningQuestionFactory extends Factory
{
    protected $model = LightningQuestion::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'question_text' => fake()->sentence() . '?',
            'answer_text' => fake()->word(),
            'order_position' => fake()->numberBetween(1, 10),
            'is_current' => false,
            'is_answered' => false,
        ];
    }
}