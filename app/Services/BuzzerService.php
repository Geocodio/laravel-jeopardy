<?php

namespace App\Services;

use App\Models\Team;
use App\Models\BuzzerEvent;
use App\Models\Clue;
use App\Models\LightningQuestion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class BuzzerService
{
    private const LOCKOUT_DURATION = 5; // seconds
    private const DEBOUNCE_TIME = 0.1; // 100ms

    public function registerBuzz(int $teamId, int $pin, string $timestamp): BuzzerEvent
    {
        $team = Team::where('id', $teamId)
            ->where('buzzer_pin', $pin)
            ->firstOrFail();

        $parsedTimestamp = Carbon::parse($timestamp);

        // Check if team is locked out
        if ($this->isTeamLockedOut($teamId)) {
            throw new \Exception('Team is currently locked out');
        }

        // Debounce check
        $lastBuzz = Cache::get("last_buzz_{$teamId}");
        if ($lastBuzz && $parsedTimestamp->diffInSeconds($lastBuzz) < self::DEBOUNCE_TIME) {
            throw new \Exception('Buzz debounced');
        }

        Cache::put("last_buzz_{$teamId}", $parsedTimestamp, 10);

        $game = $team->game;
        $isFirst = false;

        // Determine context (main game or lightning round)
        if ($game->status === 'main_game' && $game->current_clue_id) {
            $existingBuzz = BuzzerEvent::where('clue_id', $game->current_clue_id)
                ->where('is_first', true)
                ->first();
            
            if (!$existingBuzz) {
                $isFirst = true;
            }

            $buzzerEvent = BuzzerEvent::create([
                'team_id' => $teamId,
                'clue_id' => $game->current_clue_id,
                'buzzed_at' => $parsedTimestamp,
                'is_first' => $isFirst,
            ]);
        } elseif ($game->status === 'lightning_round') {
            $currentQuestion = $game->lightningQuestions()
                ->where('is_current', true)
                ->first();

            if (!$currentQuestion) {
                throw new \Exception('No current lightning question');
            }

            $existingBuzz = BuzzerEvent::where('lightning_question_id', $currentQuestion->id)
                ->where('is_first', true)
                ->first();
            
            if (!$existingBuzz) {
                $isFirst = true;
            }

            $buzzerEvent = BuzzerEvent::create([
                'team_id' => $teamId,
                'lightning_question_id' => $currentQuestion->id,
                'buzzed_at' => $parsedTimestamp,
                'is_first' => $isFirst,
            ]);
        } else {
            throw new \Exception('Game not in valid state for buzzing');
        }

        return $buzzerEvent;
    }

    public function determineFirstBuzzer(int $clueId): ?BuzzerEvent
    {
        return BuzzerEvent::where('clue_id', $clueId)
            ->where('is_first', true)
            ->with('team')
            ->first();
    }

    public function lockoutTeam(int $teamId, int $duration = null): void
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
            Cache::forget("last_buzz_{$team->id}");
        }

        Cache::put('buzzers_reset', true, 1);
    }

    public function testBuzzer(int $pin): array
    {
        $team = Team::where('buzzer_pin', $pin)->first();

        if (!$team) {
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
            'timestamp' => now()->toIso8601String(),
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
                'last_buzz' => Cache::get("last_buzz_{$team->id}"),
            ];
        }

        return $status;
    }
}