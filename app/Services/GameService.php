<?php

namespace App\Services;

use App\Models\Game;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function createGame(): Game
    {
        return DB::transaction(function () {
            $game = Game::create([
                'status' => 'setup',
                'daily_double_used' => false,
            ]);

            $this->logAction($game, 'game_created', ['game_id' => $game->id]);

            return $game;
        });
    }

    public function setupTeams(Game $game): void
    {
        $teams = config('jeopardy.teams');

        foreach ($teams as $teamData) {
            $game->teams()->create($teamData);
        }

        $this->logAction($game, 'teams_created', ['count' => count($teams)]);
    }

    public function generateBoard(int $gameId): void
    {
        $game = Game::findOrFail($gameId);

        $categoriesData = $this->getLaravelJeopardyData();

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

        $this->placeDailyDouble($gameId);
        $this->logAction($game, 'board_generated', ['categories' => count($categoriesData)]);
    }

    private function getLaravelJeopardyData(): array
    {
        return config('jeopardy.categories');
    }

    public function placeDailyDouble(int $gameId): void
    {
        $game = Game::with('categories.clues')->findOrFail($gameId);

        $eligibleClues = $game->categories->flatMap(function ($category) {
            return $category->clues->where('value', '>=', config('jeopardy.game_settings.daily_double_min_value', 300));
        });

        if ($eligibleClues->isNotEmpty()) {
            $randomClue = $eligibleClues->random();
            $randomClue->update(['is_daily_double' => true]);

            $this->logAction($game, 'daily_double_placed', [
                'clue_id' => $randomClue->id,
                'category' => $randomClue->category->name,
                'value' => $randomClue->value,
            ]);
        }
    }

    public function transitionToLightningRound(int $gameId): void
    {
        $game = Game::findOrFail($gameId);

        DB::transaction(function () use ($game) {
            $game->update(['status' => 'lightning_round']);

            $allQuestions = config('jeopardy.lightning_questions', []);

            // Select a random subset of questions or take first few if needed
            $questions = collect($allQuestions)->shuffle()->take(5)->toArray();

            foreach ($questions as $index => $questionData) {
                $game->lightningQuestions()->create([
                    'question_text' => $questionData['question'],
                    'answer_text' => $questionData['answer'],
                    'order_position' => $index + 1,
                    'is_current' => $index === 0,
                    'is_answered' => false,
                ]);
            }

            $this->logAction($game, 'lightning_round_started', [
                'questions_count' => count($questions),
            ]);
        });
    }

    public function calculateFinalScores(int $gameId): array
    {
        $game = Game::with('teams')->findOrFail($gameId);

        $scores = $game->teams->map(function ($team) {
            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'score' => $team->score,
            ];
        })->sortByDesc('score')->values()->all();

        $this->logAction($game, 'final_scores_calculated', ['scores' => $scores]);

        return $scores;
    }

    public function saveGameState(int $gameId): void
    {
        $game = Game::with([
            'teams',
            'categories.clues',
            'lightningQuestions',
        ])->findOrFail($gameId);

        $state = [
            'game' => $game->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];

        $this->logAction($game, 'game_state_saved', ['timestamp' => $state['timestamp']]);
    }

    public function restoreGame(int $gameId): Game
    {
        $game = Game::with([
            'teams',
            'categories.clues',
            'lightningQuestions',
            'gameLogs' => function ($query) {
                $query->latest()->limit(1);
            },
        ])->findOrFail($gameId);

        $this->logAction($game, 'game_restored', ['game_id' => $gameId]);

        return $game;
    }

    private function logAction(Game $game, string $action, array $details = []): void
    {
        $game->gameLogs()->create([
            'action' => $action,
            'details' => $details,
        ]);
    }
}
