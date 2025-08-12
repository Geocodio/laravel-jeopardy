<?php

use App\Livewire\TeamScoreboard;
use App\Models\Game;
use App\Models\Team;
use App\Services\ScoringService;
use Livewire\Livewire;

beforeEach(function () {
    $this->game = Game::factory()->create();
    $this->teams = Team::factory()->count(3)->create([
        'game_id' => $this->game->id,
    ])->each(function ($team, $index) {
        $team->update(['score' => ($index + 1) * 100]);
    });
});

test('team scoreboard mounts with game id', function () {
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->assertSet('gameId', $this->game->id)
        ->assertSet('activeTeamId', null)
        ->assertCount('teams', 3);
});

test('refreshes scores and formats them correctly', function () {
    $component = Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id]);
    
    $teams = $component->get('teams');
    expect($teams)->toHaveCount(3);
    expect($teams[0]['score'])->toBe(100);
    expect($teams[0]['formatted_score'])->toBe('$100');
    expect($teams[1]['score'])->toBe(200);
    expect($teams[1]['formatted_score'])->toBe('$200');
    expect($teams[2]['score'])->toBe(300);
    expect($teams[2]['formatted_score'])->toBe('$300');
});

test('handles score update event', function () {
    $team = $this->teams->first();
    
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->dispatch('score-updated', 
            teamId: $team->id,
            points: 200,
            correct: true
        )
        ->assertSet('recentScoreChanges.' . $team->id . '.points', 200)
        ->assertSet('recentScoreChanges.' . $team->id . '.correct', true)
        ->assertDispatched('clear-score-animation-js', teamId: $team->id);
});

test('clears score animation for team', function () {
    $team = $this->teams->first();
    
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->set('recentScoreChanges', [
            $team->id => [
                'points' => 200,
                'correct' => true,
                'timestamp' => now()->timestamp,
            ]
        ])
        ->dispatch('clear-score-animation', teamId: $team->id)
        ->assertSet('recentScoreChanges', []);
});

test('highlights team on buzzer accepted', function () {
    $team = $this->teams->first();
    
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->dispatch('buzzer-accepted', teamId: $team->id)
        ->assertSet('activeTeamId', $team->id);
});

test('clears highlight on reset buzzers', function () {
    $team = $this->teams->first();
    
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->set('activeTeamId', $team->id)
        ->dispatch('reset-buzzers')
        ->assertSet('activeTeamId', null);
});

test('refreshes scores on clue answered event', function () {
    $team = $this->teams->first();
    $team->update(['score' => 500]);
    
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->dispatch('clue-answered')
        ->assertSee('500');
    
    $component = Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id]);
    $teams = $component->get('teams');
    $updatedTeam = collect($teams)->firstWhere('id', $team->id);
    expect($updatedTeam['score'])->toBe(500);
});

test('teams are ordered by id', function () {
    // Create teams with specific IDs and scores
    Team::where('game_id', $this->game->id)->delete();
    
    $team3 = Team::factory()->create([
        'game_id' => $this->game->id,
        'score' => 300,
    ]);
    $team1 = Team::factory()->create([
        'game_id' => $this->game->id,
        'score' => 100,
    ]);
    $team2 = Team::factory()->create([
        'game_id' => $this->game->id,
        'score' => 200,
    ]);
    
    $component = Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id]);
    $teams = $component->get('teams');
    
    // Teams should be ordered by ID, not score
    expect($teams[0]['id'])->toBe($team3->id);
    expect($teams[1]['id'])->toBe($team1->id);
    expect($teams[2]['id'])->toBe($team2->id);
});

test('includes team colors in data', function () {
    $this->teams->first()->update(['color_hex' => '#FF0000']);
    $this->teams->get(1)->update(['color_hex' => '#00FF00']);
    $this->teams->get(2)->update(['color_hex' => '#0000FF']);
    
    $component = Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id]);
    $teams = $component->get('teams');
    
    expect($teams[0]['color_hex'])->toBe('#FF0000');
    expect($teams[1]['color_hex'])->toBe('#00FF00');
    expect($teams[2]['color_hex'])->toBe('#0000FF');
});

test('tracks multiple recent score changes', function () {
    $team1 = $this->teams->first();
    $team2 = $this->teams->get(1);
    
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->dispatch('score-updated',
            teamId: $team1->id,
            points: 200,
            correct: true
        )
        ->dispatch('score-updated',
            teamId: $team2->id,
            points: -100,
            correct: false
        )
        ->assertSet('recentScoreChanges.' . $team1->id . '.points', 200)
        ->assertSet('recentScoreChanges.' . $team1->id . '.correct', true)
        ->assertSet('recentScoreChanges.' . $team2->id . '.points', -100)
        ->assertSet('recentScoreChanges.' . $team2->id . '.correct', false);
});

test('renders team scoreboard view', function () {
    Livewire::test(TeamScoreboard::class, ['gameId' => $this->game->id])
        ->assertViewIs('livewire.team-scoreboard');
});