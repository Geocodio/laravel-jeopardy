<?php

use App\Services\BuzzerService;
use App\Models\Game;
use App\Models\Team;
use App\Models\Clue;
use App\Models\BuzzerEvent;
use App\Models\LightningQuestion;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->buzzerService = new BuzzerService();
    $this->game = Game::factory()->create(['status' => 'main_game']);
    $this->team = Team::factory()->create([
        'game_id' => $this->game->id,
        'buzzer_pin' => 1,
    ]);
});

test('registers buzz for main game clue', function () {
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $timestamp = now()->toIso8601String();
    
    $buzzerEvent = $this->buzzerService->registerBuzz(
        $this->team->id,
        $this->team->buzzer_pin,
        $timestamp
    );
    
    expect($buzzerEvent)->toBeInstanceOf(BuzzerEvent::class);
    expect($buzzerEvent->team_id)->toBe($this->team->id);
    expect($buzzerEvent->clue_id)->toBe($clue->id);
    expect($buzzerEvent->is_first)->toBeTrue();
});

test('registers buzz for lightning round question', function () {
    $this->game->update(['status' => 'lightning_round']);
    $question = LightningQuestion::factory()->create([
        'game_id' => $this->game->id,
        'is_current' => true,
    ]);
    
    $timestamp = now()->toIso8601String();
    
    $buzzerEvent = $this->buzzerService->registerBuzz(
        $this->team->id,
        $this->team->buzzer_pin,
        $timestamp
    );
    
    expect($buzzerEvent)->toBeInstanceOf(BuzzerEvent::class);
    expect($buzzerEvent->team_id)->toBe($this->team->id);
    expect($buzzerEvent->lightning_question_id)->toBe($question->id);
    expect($buzzerEvent->is_first)->toBeTrue();
});

test('throws exception for incorrect buzzer pin', function () {
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $this->expectException(\Exception::class);
    
    $this->buzzerService->registerBuzz(
        $this->team->id,
        99, // Wrong pin
        now()->toIso8601String()
    );
});

test('throws exception when team is locked out', function () {
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $this->buzzerService->lockoutTeam($this->team->id);
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Team is currently locked out');
    
    $this->buzzerService->registerBuzz(
        $this->team->id,
        $this->team->buzzer_pin,
        now()->toIso8601String()
    );
});

test('debounces rapid buzzes', function () {
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $timestamp = Carbon::now();
    Cache::put("last_buzz_{$this->team->id}", $timestamp, 10);
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Buzz debounced');
    
    $this->buzzerService->registerBuzz(
        $this->team->id,
        $this->team->buzzer_pin,
        $timestamp->addMilliseconds(50)->toIso8601String()
    );
});

test('second team buzz is not marked as first', function () {
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $team2 = Team::factory()->create([
        'game_id' => $this->game->id,
        'buzzer_pin' => 2,
    ]);
    
    $firstBuzz = $this->buzzerService->registerBuzz(
        $this->team->id,
        $this->team->buzzer_pin,
        now()->toIso8601String()
    );
    
    $secondBuzz = $this->buzzerService->registerBuzz(
        $team2->id,
        $team2->buzzer_pin,
        now()->addSecond()->toIso8601String()
    );
    
    expect($firstBuzz->is_first)->toBeTrue();
    expect($secondBuzz->is_first)->toBeFalse();
});

test('determines first buzzer for clue', function () {
    $clue = Clue::factory()->create();
    
    BuzzerEvent::factory()->create([
        'team_id' => $this->team->id,
        'clue_id' => $clue->id,
        'is_first' => true,
    ]);
    
    $firstBuzzer = $this->buzzerService->determineFirstBuzzer($clue->id);
    
    expect($firstBuzzer)->toBeInstanceOf(BuzzerEvent::class);
    expect($firstBuzzer->team_id)->toBe($this->team->id);
    expect($firstBuzzer->is_first)->toBeTrue();
});

test('locks out team for specified duration', function () {
    expect($this->buzzerService->isTeamLockedOut($this->team->id))->toBeFalse();
    
    $this->buzzerService->lockoutTeam($this->team->id, 5);
    
    expect($this->buzzerService->isTeamLockedOut($this->team->id))->toBeTrue();
    expect(Cache::has("lockout_{$this->team->id}"))->toBeTrue();
});

test('resets all buzzers', function () {
    $team2 = Team::factory()->create(['game_id' => $this->game->id]);
    
    Cache::put("lockout_{$this->team->id}", true, 60);
    Cache::put("lockout_{$team2->id}", true, 60);
    Cache::put("last_buzz_{$this->team->id}", now(), 60);
    Cache::put("last_buzz_{$team2->id}", now(), 60);
    
    $this->buzzerService->resetAllBuzzers();
    
    expect(Cache::has("lockout_{$this->team->id}"))->toBeFalse();
    expect(Cache::has("lockout_{$team2->id}"))->toBeFalse();
    expect(Cache::has("last_buzz_{$this->team->id}"))->toBeFalse();
    expect(Cache::has("last_buzz_{$team2->id}"))->toBeFalse();
    expect(Cache::has('buzzers_reset'))->toBeTrue();
});

test('tests buzzer connection successfully', function () {
    $result = $this->buzzerService->testBuzzer($this->team->buzzer_pin);
    
    expect($result['success'])->toBeTrue();
    expect($result['team_name'])->toBe($this->team->name);
    expect($result['team_color'])->toBe($this->team->color_hex);
    expect($result['pin'])->toBe($this->team->buzzer_pin);
    expect($result)->toHaveKey('timestamp');
});

test('tests buzzer with unassigned pin', function () {
    $result = $this->buzzerService->testBuzzer(99);
    
    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('No team assigned to this buzzer pin');
});

test('gets buzzer status for all teams', function () {
    $team2 = Team::factory()->create(['game_id' => $this->game->id]);
    
    $this->buzzerService->lockoutTeam($this->team->id);
    Cache::put("last_buzz_{$team2->id}", now(), 60);
    
    $status = $this->buzzerService->getBuzzerStatus($this->game->id);
    
    expect($status)->toHaveCount(2);
    
    $team1Status = collect($status)->firstWhere('team_id', $this->team->id);
    expect($team1Status['locked_out'])->toBeTrue();
    expect($team1Status['last_buzz'])->toBeNull();
    
    $team2Status = collect($status)->firstWhere('team_id', $team2->id);
    expect($team2Status['locked_out'])->toBeFalse();
    expect($team2Status['last_buzz'])->not->toBeNull();
});

test('throws exception when no current clue in main game', function () {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Game not in valid state for buzzing');
    
    $this->buzzerService->registerBuzz(
        $this->team->id,
        $this->team->buzzer_pin,
        now()->toIso8601String()
    );
});

test('throws exception when no current lightning question', function () {
    $this->game->update(['status' => 'lightning_round']);
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('No current lightning question');
    
    $this->buzzerService->registerBuzz(
        $this->team->id,
        $this->team->buzzer_pin,
        now()->toIso8601String()
    );
});