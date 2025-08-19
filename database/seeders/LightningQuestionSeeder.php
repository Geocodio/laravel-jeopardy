<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\LightningQuestion;
use Illuminate\Database\Seeder;

class LightningQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $game = Game::first() ?? Game::factory()->create();

        $questions = [
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
        ];

        foreach ($questions as $index => $questionData) {
            LightningQuestion::create([
                'game_id' => $game->id,
                'question_text' => $questionData['question'],
                'answer_text' => $questionData['answer'],
                'order_position' => $index + 1,
                'is_current' => false,
                'is_answered' => false,
            ]);
        }
    }
}
