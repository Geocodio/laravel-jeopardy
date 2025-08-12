<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Clue;
use App\Models\Game;
use App\Models\GameLog;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    public function awardPoints(int $teamId, int $amount): Team
    {
        return DB::transaction(function () use ($teamId, $amount) {
            $team = Team::lockForUpdate()->findOrFail($teamId);
            $oldScore = $team->score;
            $team->increment('score', $amount);
            
            $this->logScoreChange($team->game_id, $teamId, $oldScore, $team->score, 'award');
            
            return $team->fresh();
        });
    }

    public function deductPoints(int $teamId, int $amount): Team
    {
        return DB::transaction(function () use ($teamId, $amount) {
            $team = Team::lockForUpdate()->findOrFail($teamId);
            $oldScore = $team->score;
            $team->decrement('score', $amount);
            
            $this->logScoreChange($team->game_id, $teamId, $oldScore, $team->score, 'deduct');
            
            return $team->fresh();
        });
    }

    public function handleDailyDouble(int $teamId, int $wager, bool $correct): Team
    {
        $team = Team::findOrFail($teamId);
        
        // Validate wager
        $maxWager = max($team->score, 1000); // Can wager up to current score or $1000, whichever is higher
        $wager = min($wager, $maxWager);
        $wager = max($wager, 5); // Minimum wager of $5

        DB::transaction(function () use ($team, $wager, $correct) {
            $oldScore = $team->score;
            
            if ($correct) {
                $team->increment('score', $wager);
                $action = 'daily_double_correct';
            } else {
                $team->decrement('score', $wager);
                $action = 'daily_double_incorrect';
            }
            
            $this->logScoreChange($team->game_id, $team->id, $oldScore, $team->score, $action, [
                'wager' => $wager,
                'correct' => $correct,
            ]);

            // Mark Daily Double as used
            $team->game->update(['daily_double_used' => true]);
        });

        return $team->fresh();
    }

    public function getLeaderboard(int $gameId = null): array
    {
        $query = Team::query();
        
        if ($gameId) {
            $query->where('game_id', $gameId);
        }

        $teams = $query->orderByDesc('score')->get();

        return $teams->map(function ($team, $index) {
            return [
                'position' => $index + 1,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'team_color' => $team->color_hex,
                'score' => $team->score,
                'formatted_score' => $this->formatScore($team->score),
            ];
        })->toArray();
    }

    public function recordAnswer(int $clueId, int $teamId, bool $correct): void
    {
        DB::transaction(function () use ($clueId, $teamId, $correct) {
            $clue = Clue::findOrFail($clueId);
            $team = Team::findOrFail($teamId);

            if ($clue->is_answered) {
                throw new \Exception('Clue has already been answered');
            }

            // Update clue
            $clue->update([
                'is_answered' => true,
                'answered_by_team_id' => $teamId,
            ]);

            // Update score
            if ($correct) {
                $this->awardPoints($teamId, $clue->value);
            } else {
                $this->deductPoints($teamId, $clue->value);
            }

            // Log the answer
            GameLog::create([
                'game_id' => $team->game_id,
                'action' => 'clue_answered',
                'details' => [
                    'clue_id' => $clueId,
                    'team_id' => $teamId,
                    'correct' => $correct,
                    'value' => $clue->value,
                    'category' => $clue->category->name,
                ],
            ]);
        });
    }

    public function recordLightningAnswer(int $questionId, int $teamId, bool $correct): void
    {
        DB::transaction(function () use ($questionId, $teamId, $correct) {
            $question = \App\Models\LightningQuestion::findOrFail($questionId);
            $team = Team::findOrFail($teamId);

            if ($question->is_answered) {
                throw new \Exception('Question has already been answered');
            }

            // Update question
            $question->update([
                'is_answered' => true,
                'answered_by_team_id' => $teamId,
            ]);

            // Lightning round: only award points for correct answers, no deduction for wrong
            if ($correct) {
                $this->awardPoints($teamId, 200);
            }

            // Log the answer
            GameLog::create([
                'game_id' => $team->game_id,
                'action' => 'lightning_answered',
                'details' => [
                    'question_id' => $questionId,
                    'team_id' => $teamId,
                    'correct' => $correct,
                ],
            ]);
        });
    }

    public function formatScore(int $score): string
    {
        if ($score < 0) {
            return '-$' . number_format(abs($score));
        }
        return '$' . number_format($score);
    }

    private function logScoreChange(
        int $gameId,
        int $teamId,
        int $oldScore,
        int $newScore,
        string $action,
        array $additionalDetails = []
    ): void {
        GameLog::create([
            'game_id' => $gameId,
            'action' => 'score_changed',
            'details' => array_merge([
                'team_id' => $teamId,
                'old_score' => $oldScore,
                'new_score' => $newScore,
                'change' => $newScore - $oldScore,
                'action_type' => $action,
            ], $additionalDetails),
        ]);
    }

    public function resetScores(int $gameId): void
    {
        DB::transaction(function () use ($gameId) {
            Team::where('game_id', $gameId)->update(['score' => 0]);
            
            GameLog::create([
                'game_id' => $gameId,
                'action' => 'scores_reset',
                'details' => ['timestamp' => now()->toIso8601String()],
            ]);
        });
    }
}