<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Team;
use App\Models\Category;
use App\Models\Clue;
use App\Models\LightningQuestion;
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

        // Categories and clues
        $categoriesData = [
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
                    'answer' => 'What are class-based components or anonymous components?'
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
            'Package Development' => [
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
                    'question' => 'This was Laravel\'s original codename during development',
                    'answer' => 'What is (there was no codename)?'
                ],
                400 => [
                    'question' => 'This Laravel version introduced Laravel Sanctum for API authentication',
                    'answer' => 'What is Laravel 7?'
                ],
            ],
        ];

        foreach ($categoriesData as $categoryName => $clues) {
            static $position = 1;
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

        // Place one Daily Double randomly
        $eligibleClues = Clue::whereHas('category', function ($query) use ($game) {
            $query->where('game_id', $game->id);
        })->where('value', '>=', config('jeopardy.game_settings.daily_double_min_value', 200))->get();

        if ($eligibleClues->isNotEmpty()) {
            $eligibleClues->random()->update(['is_daily_double' => true]);
        }

        // Create Lightning Round questions
        $lightningQuestions = [
            [
                'question' => 'What method adds a where clause to an Eloquent query?',
                'answer' => 'where()'
            ],
            [
                'question' => 'What Blade directive creates a CSRF token field?',
                'answer' => '@csrf'
            ],
            [
                'question' => 'What artisan command rolls back the last migration batch?',
                'answer' => 'migrate:rollback'
            ],
            [
                'question' => 'What facade provides access to the cache?',
                'answer' => 'Cache'
            ],
            [
                'question' => 'What middleware verifies CSRF tokens on POST requests?',
                'answer' => 'VerifyCsrfToken'
            ],
        ];

        foreach ($lightningQuestions as $index => $questionData) {
            $game->lightningQuestions()->create([
                'question_text' => $questionData['question'],
                'answer_text' => $questionData['answer'],
                'order_position' => $index + 1,
                'is_current' => false,
                'is_answered' => false,
            ]);
        }

        $this->command->info('Game seeded successfully with ID: ' . $game->id);
    }
}