<?php

namespace App\Services;

use App\Models\BuzzerEvent;
use App\Models\Team;
use Exception;
use Illuminate\Support\Facades\Cache;

class BuzzerService
{
    private const LOCKOUT_DURATION = 5; // seconds

    public function registerBuzz(Team $team): BuzzerEvent
    {
        // Check if team is locked out
        if ($this->isTeamLockedOut($team->id)) {
            throw new Exception('Team is currently locked out');
        }

        $game = $team->game;

        // Determine context (main game or lightning round)
        if ($game->status === 'main_game' && $game->current_clue_id) {
            $buzzerEvent = BuzzerEvent::create([
                'team_id' => $team->id,
                'clue_id' => $game->current_clue_id,
                'buzzed_at' => now(),
                'is_first' => false, // Client determines this
            ]);
        } elseif ($game->status === 'lightning_round') {
            $currentQuestion = $game->lightningQuestions()
                ->where('is_current', true)
                ->first();

            if (! $currentQuestion) {
                throw new Exception('No current lightning question');
            }

            $buzzerEvent = BuzzerEvent::create([
                'team_id' => $team->id,
                'lightning_question_id' => $currentQuestion->id,
                'buzzed_at' => now(),
                'is_first' => false, // Client determines this
            ]);
        } else {
            throw new Exception('Game not in valid state for buzzing');
        }

        return $buzzerEvent;
    }

    public function lockoutTeam(int $teamId, ?int $duration = null): void
    {
        $duration = $duration ?? self::LOCKOUT_DURATION;
        Cache::put("lockout_{$teamId}", true, $duration);
    }

    public function isTeamLockedOut(int $teamId): bool
    {
        return Cache::has("lockout_{$teamId}");
    }

    public function resetAllBuzzers(): void
    {
        $teams = Team::all();

        foreach ($teams as $team) {
            Cache::forget("lockout_{$team->id}");
        }

        Cache::put('buzzers_reset', true, 1);
    }

    public function testBuzzer(int $pin): array
    {
        $team = Team::where('buzzer_pin', $pin)->first();

        if (! $team) {
            return [
                'success' => false,
                'message' => 'No team assigned to this buzzer pin',
            ];
        }

        return [
            'success' => true,
            'team_name' => $team->name,
            'team_color' => $team->color_hex,
            'pin' => $pin,
        ];
    }

    public function getBuzzerStatus(int $gameId): array
    {
        $teams = Team::where('game_id', $gameId)->get();

        $status = [];
        foreach ($teams as $team) {
            $status[] = [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'locked_out' => $this->isTeamLockedOut($team->id),
            ];
        }

        return $status;
    }

    /**
     * Handle buzzer press logic centrally for both manual triggers and API calls
     * This ensures consistent behavior across all buzzer sources
     */
    public function handleBuzzerPress(Team $team): void
    {
        $game = $team->game;

        if (! $game) {
            throw new Exception('Team is not associated with a game');
        }

        // Set the team as active
        $game->current_team_id = $team->id;
        $game->save();

        // Check if we're in lightning round
        if ($game->status === 'lightning_round') {
            // For lightning round, dispatch the buzzer event to the lightning round component
            broadcast(new \App\Events\GameStateChanged($game->id, 'buzzer-pressed', ['teamId' => $team->id]));

            // Also broadcast the buzzer sound
            broadcast(new \App\Events\BuzzerPressed($team));

            \Log::info('Lightning round buzzer triggered', [
                'game_id' => $game->id,
                'team_id' => $team->id,
                'team_name' => $team->name,
            ]);
        } else {
            // Regular game mode
            // Broadcast team selection to all clients
            broadcast(new \App\Events\GameStateChanged($game->id, 'team-selected', ['teamId' => $team->id]));

            // Broadcast buzzer event to trigger sound on game board
            broadcast(new \App\Events\BuzzerPressed($team));

            \Log::info('Buzzer triggered', [
                'game_id' => $game->id,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'set_as_active' => true,
            ]);
        }
    }
}
