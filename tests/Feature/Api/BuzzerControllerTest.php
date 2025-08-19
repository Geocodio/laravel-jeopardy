<?php

use App\Events\BuzzerPressed;
use App\Models\Game;
use App\Models\Team;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->game = Game::factory()->create(['status' => 'main_game']);
});

test('buzzer endpoint with team blade pin', function () {
    Event::fake();

    Team::factory()->create([
        'game_id' => $this->game->id,
        'name' => 'Team Blade',
    ]);

    $response = $this->getJson('/api/buzzer?pin_id=0');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'team' => 'Team Blade',
        ]);

    Event::assertDispatched(BuzzerPressed::class);
});

test('buzzer endpoint with team artisan pin', function () {
    Event::fake();

    Team::factory()->create([
        'game_id' => $this->game->id,
        'name' => 'Team Artisan',
    ]);

    $response = $this->getJson('/api/buzzer?pin_id=1');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'team' => 'Team Artisan',
        ]);

    Event::assertDispatched(BuzzerPressed::class);
});

test('buzzer endpoint with team eloquent pin', function () {
    Event::fake();

    Team::factory()->create([
        'game_id' => $this->game->id,
        'name' => 'Team Eloquent',
    ]);

    $response = $this->getJson('/api/buzzer?pin_id=2');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'team' => 'Team Eloquent',
        ]);

    Event::assertDispatched(BuzzerPressed::class);
});

test('buzzer endpoint with team facade pin', function () {
    Event::fake();

    Team::factory()->create([
        'game_id' => $this->game->id,
        'name' => 'Team Facade',
    ]);

    $response = $this->getJson('/api/buzzer?pin_id=3');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'team' => 'Team Facade',
        ]);

    Event::assertDispatched(BuzzerPressed::class);
});

test('buzzer endpoint with team illuminate pin', function () {
    Event::fake();

    Team::factory()->create([
        'game_id' => $this->game->id,
        'name' => 'Team Illuminate',
    ]);

    $response = $this->getJson('/api/buzzer?pin_id=4');

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'team' => 'Team Illuminate',
        ]);

    Event::assertDispatched(BuzzerPressed::class);
});

test('buzzer endpoint validates pin_id is required', function () {
    $response = $this->getJson('/api/buzzer');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['pin_id']);
});

test('buzzer endpoint returns error for invalid pin', function () {
    $response = $this->getJson('/api/buzzer?pin_id=99');

    $response->assertStatus(400);
});

test('buzzer endpoint returns 404 when team not found', function () {
    // No teams created, so any valid pin should fail
    $response = $this->getJson('/api/buzzer?pin_id=0');

    $response->assertStatus(404);
});
