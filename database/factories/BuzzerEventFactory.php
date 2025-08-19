<?php

namespace Database\Factories;

use App\Models\BuzzerEvent;
use App\Models\Clue;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuzzerEventFactory extends Factory
{
    protected $model = BuzzerEvent::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'clue_id' => Clue::factory(),
            'lightning_question_id' => null,
            'buzzed_at' => now(),
            'is_first' => false,
        ];
    }
}
