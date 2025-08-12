<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Team;
use App\Models\Category;
use App\Models\Clue;
use App\Models\LightningQuestion;
use App\Models\GameLog;
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
        $teams = [
            ['name' => 'Team Eloquent', 'color_hex' => '#3B82F6', 'buzzer_pin' => 1],
            ['name' => 'Team Blade', 'color_hex' => '#10B981', 'buzzer_pin' => 2],
            ['name' => 'Team Artisan', 'color_hex' => '#EAB308', 'buzzer_pin' => 3],
            ['name' => 'Team Forge', 'color_hex' => '#FFFFFF', 'buzzer_pin' => 4],
            ['name' => 'Team Cloud', 'color_hex' => '#EF4444', 'buzzer_pin' => 5],
        ];

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
        foreach ($categoriesData as $categoryName => $clues) {
            $category = $game->categories()->create([
                'name' => $categoryName,
                'position' => $position++,
            ]);

            foreach ($clues as $value => $clueData) {
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
        return [
            'Laravel Basics' => [
                100 => [
                    'question' => 'This artisan command creates a new Laravel project',
                    'answer' => 'What is laravel new or composer create-project?'
                ],
                200 => [
                    'question' => 'This file in the root directory contains all of your application routes',
                    'answer' => 'What is routes/web.php?'
                ],
                300 => [
                    'question' => 'This configuration file stores your database connection details',
                    'answer' => 'What is .env or config/database.php?'
                ],
                400 => [
                    'question' => 'This directory contains all of your Blade template files',
                    'answer' => 'What is resources/views?'
                ],
            ],
            'Eloquent ORM' => [
                100 => [
                    'question' => 'This method retrieves all records from a database table',
                    'answer' => 'What is all() or get()?'
                ],
                200 => [
                    'question' => 'This relationship type represents a one-to-many connection between models',
                    'answer' => 'What is hasMany()?'
                ],
                300 => [
                    'question' => 'This Eloquent feature automatically manages created_at and updated_at columns',
                    'answer' => 'What are timestamps?'
                ],
                400 => [
                    'question' => 'This method creates query constraints that are always applied to a model',
                    'answer' => 'What are global scopes?'
                ],
            ],
            'Blade Templates' => [
                100 => [
                    'question' => 'This directive is used to display escaped data in Blade templates',
                    'answer' => 'What is {{ }} or double curly braces?'
                ],
                200 => [
                    'question' => 'This Blade directive includes another Blade view within the current view',
                    'answer' => 'What is @include?'
                ],
                300 => [
                    'question' => 'This directive defines a section that can be overridden by child views',
                    'answer' => 'What is @yield or @section?'
                ],
                400 => [
                    'question' => 'This feature compiles Blade components into PHP classes for better performance',
                    'answer' => 'What are class-based components?'
                ],
            ],
            'Artisan Commands' => [
                100 => [
                    'question' => 'This command displays all available routes in your application',
                    'answer' => 'What is route:list?'
                ],
                200 => [
                    'question' => 'This artisan command creates a new database migration file',
                    'answer' => 'What is make:migration?'
                ],
                300 => [
                    'question' => 'This command clears all cached configuration files',
                    'answer' => 'What is config:clear or cache:clear?'
                ],
                400 => [
                    'question' => 'This command runs your application\'s database seeders',
                    'answer' => 'What is db:seed?'
                ],
            ],
            'Package Dev' => [
                100 => [
                    'question' => 'This file defines a package\'s dependencies and metadata',
                    'answer' => 'What is composer.json?'
                ],
                200 => [
                    'question' => 'This class registers package services with the Laravel container',
                    'answer' => 'What is a Service Provider?'
                ],
                300 => [
                    'question' => 'This artisan command publishes package configuration files to the application',
                    'answer' => 'What is vendor:publish?'
                ],
                400 => [
                    'question' => 'This method in a service provider registers package views with Laravel',
                    'answer' => 'What is loadViewsFrom()?'
                ],
            ],
            'Laravel History' => [
                100 => [
                    'question' => 'This person created the Laravel framework',
                    'answer' => 'Who is Taylor Otwell?'
                ],
                200 => [
                    'question' => 'Laravel was first released in this year',
                    'answer' => 'What is 2011?'
                ],
                300 => [
                    'question' => 'This PHP framework heavily influenced Laravel\'s design',
                    'answer' => 'What is Ruby on Rails (or Symfony)?'
                ],
                400 => [
                    'question' => 'This Laravel version introduced Laravel Sanctum for API authentication',
                    'answer' => 'What is Laravel 7?'
                ],
            ],
        ];
    }

    public function placeDailyDouble(int $gameId): void
    {
        $game = Game::with('categories.clues')->findOrFail($gameId);
        
        $eligibleClues = $game->categories->flatMap(function ($category) {
            return $category->clues->where('value', '>=', 200);
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

            $questions = [
                ['question' => 'What method adds a where clause to an Eloquent query?', 'answer' => 'where()'],
                ['question' => 'What Blade directive creates a CSRF token field?', 'answer' => '@csrf'],
                ['question' => 'What artisan command rolls back the last migration?', 'answer' => 'migrate:rollback'],
                ['question' => 'What facade provides access to the cache?', 'answer' => 'Cache'],
                ['question' => 'What middleware verifies CSRF tokens?', 'answer' => 'VerifyCsrfToken'],
            ];

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