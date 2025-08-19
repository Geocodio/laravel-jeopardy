<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Clue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClueFactory extends Factory
{
    protected $model = Clue::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'question_text' => fake()->sentence().'?',
            'answer_text' => 'What is '.fake()->word().'?',
            'value' => fake()->randomElement([100, 200, 300, 400, 500]),
            'is_daily_double' => false,
            'is_revealed' => false,
            'is_answered' => false,
        ];
    }
}
