<?php

use App\Services\GameService;
use App\Models\Game;
use App\Models\Team;
use App\Models\Category;
use App\Models\Clue;
use App\Models\LightningQuestion;
use App\Models\GameLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gameService = new GameService();
});

test('creates a new game with initial setup status', function () {
    $game = $this->gameService->createGame();

    expect($game)->toBeInstanceOf(Game::class);
    expect($game->status)->toBe('setup');
    expect($game->daily_double_used)->toBeFalse();
    expect($game->gameLogs()->where('action', 'game_created')->exists())->toBeTrue();
});

test('sets up teams for a game', function () {
    $game = Game::factory()->create();
    
    $this->gameService->setupTeams($game);
    $game->refresh();

    $configuredTeams = config('jeopardy.teams');
    expect($game->teams)->toHaveCount(count($configuredTeams));
    
    $teamNames = $game->teams->pluck('name')->toArray();
    foreach ($configuredTeams as $team) {
        expect($teamNames)->toContain($team['name']);
    }
    
    expect($game->teams->first()->buzzer_pin)->toBe($configuredTeams[0]['buzzer_pin']);
    expect($game->teams->last()->buzzer_pin)->toBe($configuredTeams[count($configuredTeams) - 1]['buzzer_pin']);
    
    expect($game->gameLogs()->where('action', 'teams_created')->exists())->toBeTrue();
});

test('generates game board with categories and clues', function () {
    $game = Game::factory()->create();
    
    $this->gameService->generateBoard($game->id);
    $game->refresh();

    expect($game->categories)->toHaveCount(6);
    
    $categoryNames = $game->categories->pluck('name')->toArray();
    expect($categoryNames)->toContain('Laravel Basics');
    expect($categoryNames)->toContain('Eloquent ORM');
    expect($categoryNames)->toContain('Blade Templates');
    expect($categoryNames)->toContain('Artisan Commands');
    expect($categoryNames)->toContain('Package Dev');
    expect($categoryNames)->toContain('Laravel History');
    
    foreach ($game->categories as $category) {
        expect($category->clues)->toHaveCount(4);
        
        $values = $category->clues->pluck('value')->toArray();
        expect($values)->toContain(100);
        expect($values)->toContain(200);
        expect($values)->toContain(300);
        expect($values)->toContain(400);
    }
    
    expect($game->gameLogs()->where('action', 'board_generated')->exists())->toBeTrue();
});

test('places daily double on eligible clue', function () {
    $game = Game::factory()->create();
    $category = Category::factory()->create(['game_id' => $game->id]);
    
    $minValue = config('jeopardy.game_settings.daily_double_min_value', 200);
    
    Clue::factory()->create(['category_id' => $category->id, 'value' => 100]);
    Clue::factory()->create(['category_id' => $category->id, 'value' => 200]);
    Clue::factory()->create(['category_id' => $category->id, 'value' => 300]);
    Clue::factory()->create(['category_id' => $category->id, 'value' => 400]);
    
    $this->gameService->placeDailyDouble($game->id);
    
    $dailyDoubleClues = Clue::where('is_daily_double', true)
        ->whereIn('category_id', $game->categories->pluck('id'))
        ->get();
    
    expect($dailyDoubleClues)->toHaveCount(1);
    expect($dailyDoubleClues->first()->value)->toBeGreaterThanOrEqual($minValue);
    
    expect($game->gameLogs()->where('action', 'daily_double_placed')->exists())->toBeTrue();
});

test('transitions game to lightning round', function () {
    $game = Game::factory()->create(['status' => 'main_game']);
    
    $this->gameService->transitionToLightningRound($game->id);
    $game->refresh();
    
    expect($game->status)->toBe('lightning_round');
    expect($game->lightningQuestions)->toHaveCount(5);
    
    $firstQuestion = $game->lightningQuestions()->where('order_position', 1)->first();
    expect($firstQuestion->is_current)->toBeTrue();
    expect($firstQuestion->is_answered)->toBeFalse();
    
    expect($game->gameLogs()->where('action', 'lightning_round_started')->exists())->toBeTrue();
});

test('calculates final scores correctly', function () {
    $game = Game::factory()->create();
    
    Team::factory()->create(['game_id' => $game->id, 'name' => 'Team A', 'score' => 1500]);
    Team::factory()->create(['game_id' => $game->id, 'name' => 'Team B', 'score' => 2000]);
    Team::factory()->create(['game_id' => $game->id, 'name' => 'Team C', 'score' => 800]);
    
    $scores = $this->gameService->calculateFinalScores($game->id);
    
    expect($scores)->toHaveCount(3);
    expect($scores[0]['team_name'])->toBe('Team B');
    expect($scores[0]['score'])->toBe(2000);
    expect($scores[1]['team_name'])->toBe('Team A');
    expect($scores[1]['score'])->toBe(1500);
    expect($scores[2]['team_name'])->toBe('Team C');
    expect($scores[2]['score'])->toBe(800);
    
    expect($game->gameLogs()->where('action', 'final_scores_calculated')->exists())->toBeTrue();
});

test('saves game state for recovery', function () {
    $game = Game::factory()->create();
    Team::factory()->count(3)->create(['game_id' => $game->id]);
    $category = Category::factory()->create(['game_id' => $game->id]);
    Clue::factory()->count(4)->create(['category_id' => $category->id]);
    LightningQuestion::factory()->count(2)->create(['game_id' => $game->id]);
    
    $this->gameService->saveGameState($game->id);
    
    expect($game->gameLogs()->where('action', 'game_state_saved')->exists())->toBeTrue();
    
    $log = $game->gameLogs()->where('action', 'game_state_saved')->first();
    expect($log->details)->toHaveKey('timestamp');
});

test('restores game with all relationships', function () {
    $game = Game::factory()->create();
    Team::factory()->count(3)->create(['game_id' => $game->id]);
    $category = Category::factory()->create(['game_id' => $game->id]);
    Clue::factory()->count(4)->create(['category_id' => $category->id]);
    LightningQuestion::factory()->count(2)->create(['game_id' => $game->id]);
    GameLog::factory()->create(['game_id' => $game->id]);
    
    $restoredGame = $this->gameService->restoreGame($game->id);
    
    expect($restoredGame->id)->toBe($game->id);
    expect($restoredGame->teams)->toHaveCount(3);
    expect($restoredGame->categories)->toHaveCount(1);
    expect($restoredGame->lightningQuestions)->toHaveCount(2);
    expect($restoredGame->gameLogs)->not->toBeEmpty();
    
    expect($game->gameLogs()->where('action', 'game_restored')->exists())->toBeTrue();
});

test('does not place daily double when no eligible clues', function () {
    $game = Game::factory()->create();
    $category = Category::factory()->create(['game_id' => $game->id]);
    
    Clue::factory()->create(['category_id' => $category->id, 'value' => 100]);
    
    $this->gameService->placeDailyDouble($game->id);
    
    $dailyDoubleClues = Clue::where('is_daily_double', true)
        ->whereIn('category_id', $game->categories->pluck('id'))
        ->get();
    
    expect($dailyDoubleClues)->toHaveCount(0);
});