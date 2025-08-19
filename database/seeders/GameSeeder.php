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

        // Categories and clues
        $categoriesData = [
            'Taylor\'s Version' => [
                100 => [
                    'question' => 'Swift\'s emotional 2012 album title describes what developers see when tests fail',
                    'answer' => 'What is "Red"?',
                ],
                300 => [
                    'question' => 'Taylor\'s 2014 hit about relationships gone wrong also describes what PHP throws when you try to echo an uninitialized variable',
                    'answer' => 'What is "Blank Space"?',
                ],
                500 => [
                    'question' => 'Before creating Laravel, Taylor Otwell programmed in this 1960s business language',
                    'answer' => 'What is COBOL?',
                ],
                1000 => [
                    'question' => 'Taylor Otwell played this sport after school every day growing up',
                    'answer' => 'What is basketball?',
                ],
            ],
            '404: Category Not Found' => [
                100 => [
                    'question' => 'This HTTP status code is returned when the client should authenticate itself',
                    'answer' => 'What is 401 (Unauthorized)?',
                ],
                300 => [
                    'question' => 'This Laravel package provides a beautiful debugging interface to inspect errors',
                    'answer' => 'What is Debugbar for Laravel?',
                ],
                500 => [
                    'question' => 'This error occurs when PHP runs out of allocated memory',
                    'answer' => 'What is "Fatal error: Allowed memory size exhausted"?',
                ],
                1000 => [
                    'question' => 'This HTTP status code means "I\'m a teapot" and was an April Fool\'s joke',
                    'answer' => 'What is 418?',
                ],
            ],
            'Laracon Legends' => [
                100 => [
                    'question' => 'This European city has hosted Laracon EU the most times since 2013',
                    'answer' => 'What is Amsterdam?',
                ],
                300 => [
                    'question' => 'These two Australian cities have hosted Laracon AU',
                    'answer' => 'What are Sydney and Brisbane?',
                ],
                500 => [
                    'question' => 'This U.S. capital city hosted the very first Laracon in 2013',
                    'answer' => 'What is Washington, DC?',
                ],
                1000 => [
                    'question' => 'This entrepreneur had the vision for the first Laracon',
                    'answer' => 'Who is Ian Landsman?',
                ],
            ],
            'Rød Grød Med Fløde' => [
                100 => [
                    'question' => 'This Danish shipping giant is the world\'s second-largest container company',
                    'answer' => 'What is Maersk?',
                ],
                300 => [
                    'question' => 'These seafarers from whom Europe\'s oldest monarchy traces back',
                    'answer' => 'Who are the Vikings?',
                ],
                500 => [
                    'question' => 'This Danish programmer created PHP in 1994 and was born in Greenland',
                    'answer' => 'Who is Rasmus Lerdorf?',
                ],
                1000 => [
                    'question' => 'These three special letters make Danish keyboards unique among Nordic countries',
                    'answer' => 'What are Æ, Ø, and Å?',
                ],
            ],
            'Breaking Prod' => [
                100 => [
                    'question' => 'The worst day of the week to deploy to production',
                    'answer' => 'What is Friday?',
                ],
                300 => [
                    'question' => 'This containerization tool ensures "it works on everyone\'s machine"',
                    'answer' => 'What is Docker?',
                ],
                500 => [
                    'question' => 'This deployment strategy uses color-coded environments to achieve zero-downtime deployment',
                    'answer' => 'What is blue-green deployment?',
                ],
                1000 => [
                    'question' => 'This phenomenon occurs when a popular cache key expires and multiple processes try to regenerate it simultaneously',
                    'answer' => 'What is a cache stampede (or thundering herd)?',
                ],
            ],
            'Eloquently Speaking' => [
                100 => [
                    'question' => 'This relationship represents the inverse of hasMany',
                    'answer' => 'What is belongsTo()?',
                ],
                300 => [
                    'question' => 'This property specifies which attributes can be mass assigned',
                    'answer' => 'What is $fillable?',
                ],
                500 => [
                    'question' => 'This feature allows models to be "deleted" without removing them from the database',
                    'answer' => 'What are soft deletes?',
                ],
                1000 => [
                    'question' => 'This feature allows you to listen to model lifecycle events',
                    'answer' => 'What are model events (or observers)?',
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
        })->where('value', '>=', config('jeopardy.game_settings.daily_double_min_value', 300))->get();

        if ($eligibleClues->isNotEmpty()) {
            $eligibleClues->random()->update(['is_daily_double' => true]);
        }

        // Create Lightning Round questions
        $lightningQuestions = [
            // Laravel/PHP Quick Recognition
            ['question' => 'The default Laravel database driver', 'answer' => 'SQLite'],
            ['question' => 'Laravel\'s CLI tool name', 'answer' => 'Artisan'],
            ['question' => 'Laravel\'s ORM', 'answer' => 'Eloquent'],
            ['question' => 'Default Laravel testing framework', 'answer' => 'Pest or PHPUnit'],
            ['question' => 'Laravel\'s frontend scaffolding tool', 'answer' => 'Breeze'],
            ['question' => 'Laravel\'s real-time event broadcasting', 'answer' => 'Echo'],
            ['question' => 'Laravel\'s default queue connection', 'answer' => 'sync'],
            ['question' => 'Laravel\'s blade directive for CSRF', 'answer' => '@csrf'],
            ['question' => 'Laravel\'s creator', 'answer' => 'Taylor Otwell'],
            ['question' => 'Laravel\'s package manager', 'answer' => 'Composer'],

            // Laravel Commands
            ['question' => 'Command to make a new controller', 'answer' => 'php artisan make:controller'],
            ['question' => 'Command to clear all caches', 'answer' => 'php artisan optimize:clear'],
            ['question' => 'Command for fresh migrations with seeds', 'answer' => 'php artisan migrate:fresh --seed'],
            ['question' => 'Command to start the development server', 'answer' => 'php artisan serve'],
            ['question' => 'Command to create a new model', 'answer' => 'php artisan make:model'],
            ['question' => 'Command to run tests', 'answer' => 'php artisan test'],
            ['question' => 'Command to list all routes', 'answer' => 'php artisan route:list'],
            ['question' => 'Command to create a migration', 'answer' => 'php artisan make:migration'],

            // HTTP & Web Basics
            ['question' => 'HTTP method for creating resources', 'answer' => 'POST'],
            ['question' => 'HTTP method for updating', 'answer' => 'PUT or PATCH'],
            ['question' => 'Status code for success', 'answer' => '200'],
            ['question' => 'Status code for not found', 'answer' => '404'],
            ['question' => 'Status code for server error', 'answer' => '500'],
            ['question' => 'Status code for redirect', 'answer' => '301 or 302'],
            ['question' => 'Status code for unauthorized', 'answer' => '401'],
            ['question' => 'Status code for forbidden', 'answer' => '403'],

            // Frontend/CSS
            ['question' => 'CSS framework used in this project', 'answer' => 'Tailwind'],
            ['question' => 'JavaScript framework for reactivity in Livewire', 'answer' => 'Alpine.js'],
            ['question' => 'Default Vite development server port', 'answer' => '5173'],
            ['question' => 'CSS property for spacing between flex items', 'answer' => 'gap'],
            ['question' => 'Tailwind dark mode prefix', 'answer' => 'dark:'],
            ['question' => 'CSS Grid competitor for layouts', 'answer' => 'Flexbox'],

            // Danish/Copenhagen Tech
            ['question' => 'Denmark\'s domain extension', 'answer' => '.dk'],
            ['question' => 'Danish word for "developer"', 'answer' => 'Udvikler'],
            ['question' => 'Copenhagen\'s tech district', 'answer' => 'Ørestad'],
            ['question' => 'Danish currency', 'answer' => 'Kroner or DKK'],
            ['question' => '"Hello World" in Danish', 'answer' => 'Hej Verden'],

            // Fun/Trivia
            ['question' => 'PHP originally stood for', 'answer' => 'Personal Home Page'],
            ['question' => 'Year Laravel was released', 'answer' => '2011'],
            ['question' => 'PHP\'s elephant mascot name', 'answer' => 'ElePHPant'],
            ['question' => 'Laravel\'s annual conference', 'answer' => 'Laracon'],
            ['question' => 'Number of standard HTTP methods', 'answer' => '9'],
            ['question' => 'Port 3306 is typically used for', 'answer' => 'MySQL'],
            ['question' => 'Port 5432 is typically used for', 'answer' => 'PostgreSQL'],
            ['question' => 'Laravel\'s real-time frontend framework', 'answer' => 'Livewire'],
            ['question' => 'Laravel\'s task scheduling runs via', 'answer' => 'Cron'],
            ['question' => 'Laravel\'s default session driver', 'answer' => 'database'],

            // Additional Laravel-specific questions
            ['question' => 'What method adds a where clause to an Eloquent query?', 'answer' => 'where()'],
            ['question' => 'What artisan command rolls back the last migration batch?', 'answer' => 'migrate:rollback'],
            ['question' => 'What facade provides access to the cache?', 'answer' => 'Cache'],
            ['question' => 'What middleware verifies CSRF tokens on POST requests?', 'answer' => 'VerifyCsrfToken'],
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

        $this->command->info('Game seeded successfully with ID: '.$game->id);
    }
}
