<?php

use App\Livewire\GameBoard;
use App\Models\Game;
use App\Models\Category;
use App\Models\Clue;
use App\Models\Team;
use App\Services\GameService;
use Livewire\Livewire;

beforeEach(function () {
    $this->game = Game::factory()->create(['status' => 'main_game']);
    $this->category = Category::factory()->create([
        'game_id' => $this->game->id,
        'position' => 1,
    ]);
    $this->clues = Clue::factory()->count(4)->create([
        'category_id' => $this->category->id,
        'is_answered' => false,
    ]);
    Team::factory()->count(3)->create(['game_id' => $this->game->id]);
});

test('game board component mounts with game id', function () {
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->assertSet('game.id', $this->game->id)
        ->assertSet('showClueModal', false)
        ->assertSet('selectedClue', null);
});

test('game board loads game with relationships', function () {
    $component = Livewire::test(GameBoard::class)
        ->call('loadGame', $this->game->id)
        ->assertSet('game.id', $this->game->id);
    
    expect($component->get('categories'))->not->toBeNull();
});

test('selecting clue opens modal and updates game state', function () {
    $clue = $this->clues->first();
    
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->call('selectClue', $clue->id)
        ->assertSet('selectedClue.id', $clue->id)
        ->assertSet('showClueModal', true)
        ->assertDispatched('clue-selected', clueId: $clue->id);
    
    $clue->refresh();
    expect($clue->is_revealed)->toBeTrue();
    
    $this->game->refresh();
    expect($this->game->current_clue_id)->toBe($clue->id);
});

test('cannot select already answered clue', function () {
    $clue = $this->clues->first();
    $clue->update(['is_answered' => true]);
    
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->call('selectClue', $clue->id)
        ->assertSet('selectedClue', null)
        ->assertSet('showClueModal', false);
});

test('handles clue answered event', function () {
    $clue = $this->clues->first();
    
    $component = Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->set('selectedClue', $clue)
        ->set('showClueModal', true);
    
    $component->dispatch('clue-answered', clueId: $clue->id)
        ->assertSet('showClueModal', false)
        ->assertSet('selectedClue', null);
});

test('returns to board closes modal and resets state', function () {
    $clue = $this->clues->first();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->set('selectedClue', $clue)
        ->set('showClueModal', true)
        ->call('returnToBoard')
        ->assertSet('showClueModal', false)
        ->assertSet('selectedClue', null)
        ->assertDispatched('reset-buzzers');
    
    $this->game->refresh();
    expect($this->game->current_clue_id)->toBeNull();
});

test('starts lightning round when in main game', function () {
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->call('startLightningRound')
        ->assertDispatched('lightning-round-started');
    
    $this->game->refresh();
    expect($this->game->status)->toBe('lightning_round');
    expect($this->game->lightningQuestions()->count())->toBeGreaterThan(0);
});

test('cannot start lightning round if not in main game', function () {
    $this->game->update(['status' => 'setup']);
    
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->call('startLightningRound')
        ->assertNotDispatched('lightning-round-started');
    
    $this->game->refresh();
    expect($this->game->status)->toBe('setup');
    expect($this->game->lightningQuestions()->count())->toBe(0);
});

test('ends game and calculates final scores', function () {
    // Create teams with scores
    $team1 = Team::factory()->create(['game_id' => $this->game->id, 'score' => 1000]);
    $team2 = Team::factory()->create(['game_id' => $this->game->id, 'score' => 800]);
    
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->call('endGame')
        ->assertDispatched('game-ended');
    
    $this->game->refresh();
    expect($this->game->status)->toBe('finished');
    
    // Verify scores are calculated
    $logs = $this->game->gameLogs()->where('action', 'final_scores_calculated')->first();
    expect($logs)->not->toBeNull();
});

test('renders game board view with game layout', function () {
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->assertViewIs('livewire.game-board');
});

test('categories are sorted by position', function () {
    // Delete existing category to start fresh
    Category::where('game_id', $this->game->id)->delete();
    
    $cat1 = Category::factory()->create([
        'game_id' => $this->game->id,
        'position' => 3,
        'name' => 'Third',
    ]);
    
    $cat2 = Category::factory()->create([
        'game_id' => $this->game->id,
        'position' => 1,
        'name' => 'First',
    ]);
    
    $cat3 = Category::factory()->create([
        'game_id' => $this->game->id,
        'position' => 2,
        'name' => 'Second',
    ]);
    
    $component = Livewire::test(GameBoard::class, ['gameId' => $this->game->id]);
    
    $categories = $component->get('categories');
    expect($categories->count())->toBe(3);
    expect($categories->first()->position)->toBe(1);
    expect($categories->get(1)->position)->toBe(2);
    expect($categories->get(2)->position)->toBe(3);
});

test('refreshes game after clue answered', function () {
    $clue = $this->clues->first();
    $originalUpdatedAt = $this->game->updated_at;
    
    // Simulate some change
    $this->game->teams->first()->update(['score' => 500]);
    
    Livewire::test(GameBoard::class, ['gameId' => $this->game->id])
        ->dispatch('clue-answered', clueId: $clue->id);
    
    $component = Livewire::test(GameBoard::class, ['gameId' => $this->game->id]);
    $refreshedGame = $component->get('game');
    
    expect($refreshedGame->teams->first()->score)->toBe(500);
});