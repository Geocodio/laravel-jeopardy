<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $game = Game::first();
        
        if (!$game) {
            $this->command->info('No game found. Please create a game first.');
            return;
        }

        $categories = [
            'Laravel Basics',
            'Eloquent ORM',
            'Blade Templates',
            'Artisan Commands',
            'Package Development',
            'Laravel History',
        ];

        foreach ($categories as $index => $name) {
            Category::create([
                'name' => $name,
                'position' => $index + 1,
                'game_id' => $game->id,
            ]);
        }

        $this->command->info('Categories seeded successfully!');
    }
}
