<?php

use App\Models\Game;
use App\Models\Team;
use App\Models\Category;
use App\Models\Clue;
use App\Models\LightningQuestion;
use App\Models\GameLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->game = Game::factory()->create();
});

test('game has fillable attributes', function () {
    $game = Game::create([
        'status' => 'setup',
        'current_clue_id' => null,
        'daily_double_used' => false,
    ]);

    expect($game->status)->toBe('setup');
    expect($game->current_clue_id)->toBeNull();
    expect($game->daily_double_used)->toBeFalse();
});

test('game casts daily_double_used to boolean', function () {
    $game = Game::create([
        'status' => 'setup',
        'daily_double_used' => 1,
    ]);

    expect($game->daily_double_used)->toBeBool();
    expect($game->daily_double_used)->toBeTrue();
});

test('game has many teams relationship', function () {
    $teams = Team::factory()->count(3)->create(['game_id' => $this->game->id]);

    expect($this->game->teams)->toHaveCount(3);
    expect($this->game->teams->first())->toBeInstanceOf(Team::class);
});

test('game has many categories relationship', function () {
    $categories = Category::factory()->count(6)->create(['game_id' => $this->game->id]);

    expect($this->game->categories)->toHaveCount(6);
    expect($this->game->categories->first())->toBeInstanceOf(Category::class);
});

test('game belongs to current clue', function () {
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    $this->game->refresh();

    expect($this->game->currentClue)->toBeInstanceOf(Clue::class);
    expect($this->game->currentClue->id)->toBe($clue->id);
});

test('game has many lightning questions relationship', function () {
    $questions = LightningQuestion::factory()->count(5)->create(['game_id' => $this->game->id]);

    expect($this->game->lightningQuestions)->toHaveCount(5);
    expect($this->game->lightningQuestions->first())->toBeInstanceOf(LightningQuestion::class);
});

test('game has many game logs relationship', function () {
    $logs = GameLog::factory()->count(10)->create(['game_id' => $this->game->id]);

    expect($this->game->gameLogs)->toHaveCount(10);
    expect($this->game->gameLogs->first())->toBeInstanceOf(GameLog::class);
});

test('game can transition through different statuses', function () {
    $game = Game::create(['status' => 'setup']);
    
    expect($game->status)->toBe('setup');
    
    $game->update(['status' => 'main_game']);
    expect($game->status)->toBe('main_game');
    
    $game->update(['status' => 'lightning_round']);
    expect($game->status)->toBe('lightning_round');
    
    $game->update(['status' => 'finished']);
    expect($game->status)->toBe('finished');
});

test('game tracks daily double usage', function () {
    $game = Game::create([
        'status' => 'main_game',
        'daily_double_used' => false,
    ]);

    expect($game->daily_double_used)->toBeFalse();

    $game->update(['daily_double_used' => true]);
    expect($game->daily_double_used)->toBeTrue();
});