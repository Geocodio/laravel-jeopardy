<?php

use App\Livewire\ClueDisplay;
use App\Models\Category;
use App\Models\Clue;
use App\Models\Game;
use App\Models\Team;
use App\Services\BuzzerService;
use Livewire\Livewire;

beforeEach(function () {
    $this->game = Game::factory()->create();
    $this->category = Category::factory()->create(['game_id' => $this->game->id]);
    $this->clue = Clue::factory()->create([
        'category_id' => $this->category->id,
        'value' => 200,
        'is_daily_double' => false,
    ]);
    $this->teams = Team::factory()->count(3)->create(['game_id' => $this->game->id]);
});

test('clue display component mounts with clue id', function () {
    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->assertSet('clue.id', $this->clue->id)
        ->assertSet('isDailyDouble', false);
});

test('loads daily double clue correctly', function () {
    $dailyDouble = Clue::factory()->create([
        'category_id' => $this->category->id,
        'value' => 400,
        'is_daily_double' => true,
    ]);

    Livewire::test(ClueDisplay::class, ['clueId' => $dailyDouble->id])
        ->assertSet('isDailyDouble', true);
});


test('handles buzzer press correctly', function () {
    $team = $this->teams->first();

    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->dispatch('buzzer-pressed', teamId: $team->id)
        ->assertSet('buzzerTeam.id', $team->id)
        ->assertDispatched('buzzer-accepted', teamId: $team->id);
});

test('ignores buzzer when team already buzzed', function () {
    $team1 = $this->teams->first();
    $team2 = $this->teams->get(1);

    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->set('buzzerTeam', $team1)
        ->dispatch('buzzer-pressed', teamId: $team2->id)
        ->assertSet('buzzerTeam.id', $team1->id)
        ->assertNotDispatched('buzzer-accepted');
});

test('marks answer correct and awards points', function () {
    $team = $this->teams->first();
    $initialScore = $team->score;

    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->set('buzzerTeam', $team)
        ->call('markCorrect')
        ->assertDispatched('play-sound', sound: 'correct')
        ->assertDispatched('score-updated',
            teamId: $team->id,
            points: 200,
            correct: true
        )
        ->assertDispatched('clue-answered', clueId: $this->clue->id)
        ->assertSet('buzzerTeam', null);

    // Verify actual database changes
    $team->refresh();
    expect($team->score)->toBe($initialScore + $this->clue->value);

    $this->clue->refresh();
    expect($this->clue->is_answered)->toBeTrue();
});

test('marks answer incorrect and deducts points', function () {
    $team = $this->teams->first();
    $team->update(['score' => 500]);
    $initialScore = $team->score;

    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->set('buzzerTeam', $team)
        ->call('markIncorrect')
        ->assertDispatched('play-sound', sound: 'incorrect')
        ->assertDispatched('score-updated',
            teamId: $team->id,
            points: -200,
            correct: false
        )
        ->assertSet('buzzerTeam', null);

    // Verify actual database changes
    $team->refresh();
    expect($team->score)->toBe($initialScore - $this->clue->value);

    // Verify team is locked out
    $buzzerService = app(BuzzerService::class);
    expect($buzzerService->isTeamLockedOut($team->id))->toBeTrue();
});

test('handles daily double wager correctly', function () {
    $dailyDouble = Clue::factory()->create([
        'category_id' => $this->category->id,
        'value' => 400,
        'is_daily_double' => true,
    ]);

    Livewire::test(ClueDisplay::class, ['clueId' => $dailyDouble->id])
        ->assertSet('isDailyDouble', true)
        ->call('setWager', 1000)
        ->assertSet('wagerAmount', 1000);
});

test('limits wager amount to valid range', function () {
    $dailyDouble = Clue::factory()->create([
        'category_id' => $this->category->id,
        'is_daily_double' => true,
    ]);

    Livewire::test(ClueDisplay::class, ['clueId' => $dailyDouble->id])
        ->call('setWager', 3000)
        ->assertSet('wagerAmount', 2000)
        ->call('setWager', 1)
        ->assertSet('wagerAmount', 5);
});

test('handles daily double correct answer', function () {
    $team = $this->teams->first();
    $team->update(['score' => 1500]);
    $initialScore = $team->score;

    $dailyDouble = Clue::factory()->create([
        'category_id' => $this->category->id,
        'is_daily_double' => true,
    ]);

    Livewire::test(ClueDisplay::class, ['clueId' => $dailyDouble->id])
        ->set('buzzerTeam', $team)
        ->set('wagerAmount', 1000)
        ->call('markCorrect')
        ->assertDispatched('score-updated',
            teamId: $team->id,
            points: 1000,
            correct: true
        )
        ->assertDispatched('clue-answered', clueId: $dailyDouble->id);

    // Verify actual score change
    $team->refresh();
    expect($team->score)->toBe($initialScore + 1000);

    // Verify daily double is marked as used
    $this->game->refresh();
    expect($this->game->daily_double_used)->toBeTrue();
});

test('skips clue and marks as answered', function () {
    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->call('skipClue')
        ->assertDispatched('clue-answered', clueId: $this->clue->id);

    $this->clue->refresh();
    expect($this->clue->is_answered)->toBeTrue();
});

test('toggles manual team selection', function () {
    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->assertSet('showManualTeamSelection', false)
        ->call('toggleManualTeamSelection')
        ->assertSet('showManualTeamSelection', true)
        ->call('toggleManualTeamSelection')
        ->assertSet('showManualTeamSelection', false);
});

test('selects team manually', function () {
    $team = $this->teams->first();

    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->set('showManualTeamSelection', true)
        ->call('selectTeamManually', $team->id)
        ->assertSet('buzzerTeam.id', $team->id)
        ->assertSet('showManualTeamSelection', false)
        ->assertDispatched('buzzer-accepted', teamId: $team->id);
});

test('cannot manually select team when one already selected', function () {
    $team1 = $this->teams->first();
    $team2 = $this->teams->get(1);

    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->set('buzzerTeam', $team1)
        ->call('selectTeamManually', $team2->id)
        ->assertSet('buzzerTeam.id', $team1->id)
        ->assertNotDispatched('buzzer-accepted', teamId: $team2->id);
});

test('loads available teams for selection', function () {
    Livewire::test(ClueDisplay::class, ['clueId' => $this->clue->id])
        ->assertCount('availableTeams', 3);
});
