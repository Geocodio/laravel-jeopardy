<?php

use App\Models\Game;
use App\Models\Team;
use App\Models\Clue;
use App\Models\BuzzerEvent;
use App\Events\BuzzerPressed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->game = Game::factory()->create(['status' => 'main_game']);
    $this->team = Team::factory()->create([
        'game_id' => $this->game->id,
        'buzzer_pin' => 1,
        'name' => 'Test Team',
    ]);
});

test('buzzer store endpoint registers valid buzz', function () {
    Event::fake();
    
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $response = $this->postJson('/api/buzzer', [
        'team_id' => $this->team->id,
        'timestamp' => now()->toIso8601String(),
    ]);
    
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'is_first' => true,
            'team' => 'Test Team',
        ]);
    
    expect(BuzzerEvent::where('team_id', $this->team->id)->exists())->toBeTrue();
    Event::assertDispatched(BuzzerPressed::class);
});

test('buzzer store validates required fields', function () {
    $response = $this->postJson('/api/buzzer', []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['team_id', 'timestamp']);
});

test('buzzer store validates team exists', function () {
    $response = $this->postJson('/api/buzzer', [
        'team_id' => 99999,
        'timestamp' => now()->toIso8601String(),
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['team_id']);
});

test('buzzer store returns error for invalid buzzer state', function () {
    $response = $this->postJson('/api/buzzer', [
        'team_id' => $this->team->id,
        'timestamp' => now()->toIso8601String(),
    ]);
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ]);
});

test('buzzer test endpoint validates connection', function () {
    $response = $this->postJson('/api/buzzer/test', [
        'pin' => 1,
    ]);
    
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'team_name' => $this->team->name,
            'team_color' => $this->team->color_hex,
            'pin' => 1,
        ])
        ->assertJsonStructure(['timestamp']);
});

test('buzzer test returns error for unassigned pin', function () {
    $response = $this->postJson('/api/buzzer/test', [
        'pin' => 99,
    ]);
    
    $response->assertOk()
        ->assertJson([
            'success' => false,
            'message' => 'No team assigned to this buzzer pin',
        ]);
});

test('buzzer test validates pin is required', function () {
    $response = $this->postJson('/api/buzzer/test', []);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['pin']);
});

test('buzzer status endpoint returns team statuses', function () {
    $team2 = Team::factory()->create([
        'game_id' => $this->game->id,
        'name' => 'Team 2',
    ]);
    
    Cache::put("lockout_{$this->team->id}", true, 60);
    Cache::put("last_buzz_{$team2->id}", now(), 60);
    
    $response = $this->postJson('/api/buzzer/status', [
        'game_id' => $this->game->id,
    ]);
    
    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'status' => [
                '*' => ['team_id', 'team_name', 'locked_out', 'last_buzz']
            ]
        ]);
    
    $status = $response->json('status');
    expect($status)->toHaveCount(2);
    
    $team1Status = collect($status)->firstWhere('team_id', $this->team->id);
    expect($team1Status['locked_out'])->toBeTrue();
});

test('buzzer status validates game exists', function () {
    $response = $this->postJson('/api/buzzer/status', [
        'game_id' => 99999,
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['game_id']);
});

test('buzzer reset endpoint clears all buzzers', function () {
    Cache::put("lockout_{$this->team->id}", true, 60);
    Cache::put("last_buzz_{$this->team->id}", now(), 60);
    
    $response = $this->postJson('/api/buzzer/reset');
    
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'All buzzers have been reset',
        ]);
    
    expect(Cache::has("lockout_{$this->team->id}"))->toBeFalse();
    expect(Cache::has("last_buzz_{$this->team->id}"))->toBeFalse();
});

test('buzzer store handles second team buzz correctly', function () {
    Event::fake();
    
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $team2 = Team::factory()->create([
        'game_id' => $this->game->id,
        'buzzer_pin' => 2,
        'name' => 'Team 2',
    ]);
    
    // First buzz
    $this->postJson('/api/buzzer', [
        'team_id' => $this->team->id,
        'timestamp' => now()->toIso8601String(),
    ]);
    
    // Second buzz
    $response = $this->postJson('/api/buzzer', [
        'team_id' => $team2->id,
        'timestamp' => now()->addSecond()->toIso8601String(),
    ]);
    
    $response->assertOk()
        ->assertJson([
            'success' => true,
            'is_first' => false,
            'team' => 'Team 2',
        ]);
    
    expect(BuzzerEvent::where('team_id', $team2->id)->exists())->toBeTrue();
    Event::assertDispatchedTimes(BuzzerPressed::class, 2);
});

test('buzzer store handles exception gracefully', function () {
    // Create a team with wrong pin to trigger exception
    $wrongTeam = Team::factory()->create([
        'game_id' => $this->game->id,
        'buzzer_pin' => 99,
    ]);
    
    $clue = Clue::factory()->create();
    $this->game->update(['current_clue_id' => $clue->id]);
    
    $response = $this->postJson('/api/buzzer', [
        'team_id' => $wrongTeam->id,
        'timestamp' => now()->toIso8601String(),
    ]);
    
    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure(['message']);
});