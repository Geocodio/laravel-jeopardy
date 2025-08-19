<?php

namespace Database\Seeders;

use App\Models\Clue;
use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // Create a new game
        $game = Game::create([
            'status' => 'setup',
            'daily_double_used' => false,
        ]);

        // Create teams from config
        $teams = config('jeopardy.teams');

        foreach ($teams as $teamData) {
            $game->teams()->create($teamData);
        }

        // Create categories and clues from config
        $categoriesData = config('jeopardy.categories');
        $position = 1;

        foreach ($categoriesData as $categoryName => $categoryData) {
            $category = $game->categories()->create([
                'name' => $categoryName,
                'position' => $position++,
            ]);

            foreach ($categoryData['clues'] as $value => $clueData) {
                $category->clues()->create([
                    'question_text' => $clueData['question'],
                    'answer_text' => $clueData['answer'],
                    'value' => $value,
                    'is_daily_double' => false,
                    'is_revealed' => false,
                    'is_answered' => false,
                ]);
            }
        }

        // Place one Daily Double randomly
        $eligibleClues = Clue::whereHas('category', function ($query) use ($game) {
            $query->where('game_id', $game->id);
        })->where('value', '>=', config('jeopardy.game_settings.daily_double_min_value', 300))->get();

        if ($eligibleClues->isNotEmpty()) {
            $eligibleClues->random()->update(['is_daily_double' => true]);
        }

        // Create Lightning Round questions from config
        $lightningQuestions = config('jeopardy.lightning_questions');

        foreach ($lightningQuestions as $index => $questionData) {
            $game->lightningQuestions()->create([
                'question_text' => $questionData['question'],
                'answer_text' => $questionData['answer'],
                'order_position' => $index + 1,
                'is_current' => false,
                'is_answered' => false,
            ]);
        }

        $this->command->info('Game seeded successfully with ID: '.$game->id);
    }
}
