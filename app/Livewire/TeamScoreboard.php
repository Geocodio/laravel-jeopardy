<?php

namespace App\Livewire;

use App\Models\Team;
use App\Services\ScoringService;
use Livewire\Attributes\On;
use Livewire\Component;

class TeamScoreboard extends Component
{
    public $teams;

    public ?int $activeTeamId = null;

    public int $gameId;

    public array $recentScoreChanges = [];

    protected $scoringService;

    public function boot(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    public function mount($gameId)
    {
        $this->gameId = $gameId;
        $this->refreshScores();
        
        // Check if there's already a current team selected
        $game = \App\Models\Game::find($gameId);
        if ($game && $game->current_team_id) {
            $this->activeTeamId = $game->current_team_id;
        }
    }

    #[On('score-updated')]
    public function handleScoreUpdate($gameId = null, $teamId = null, $newScore = null, $points = null, $correct = null)
    {
        \Log::info('TeamScoreboard received score-updated', [
            'gameId' => $gameId,
            'teamId' => $teamId,
            'newScore' => $newScore,
            'points' => $points,
            'correct' => $correct
        ]);
        
        if (!$teamId) {
            $this->refreshScores();
            return;
        }
        
        // Store the score change for animation
        $this->recentScoreChanges[$teamId] = [
            'points' => (int) $points,
            'correct' => $correct,
            'timestamp' => now()->timestamp,
        ];
        
        \Log::info('Score change stored', [
            'teamId' => $teamId,
            'change' => $this->recentScoreChanges[$teamId]
        ]);

        $this->refreshScores();

        // Clear the animation after 3 seconds using JavaScript
        $this->dispatch('clear-score-animation-js', teamId: $teamId);
    }

    #[On('clear-score-animation')]
    public function clearScoreAnimation($teamId)
    {
        unset($this->recentScoreChanges[$teamId]);
    }

    #[On('clue-answered')]
    public function refreshScores()
    {
        $this->teams = Team::where('game_id', $this->gameId)
            ->orderBy('id')
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'color_hex' => $team->color_hex,
                    'score' => $team->score,
                    'formatted_score' => $this->scoringService->formatScore($team->score),
                ];
            });
    }

    #[On('buzzer-accepted')]
    public function highlightTeam($teamId)
    {
        // Handle both event object and direct parameter
        if (is_array($teamId)) {
            $teamId = $teamId['teamId'] ?? null;
        }
        $this->activeTeamId = $teamId;
    }

    #[On('game-state-changed')]
    public function handleGameStateChanged($state, $data = [])
    {
        if ($state === 'team-selected' && isset($data['teamId'])) {
            // Highlight the selected team
            $this->activeTeamId = $data['teamId'];
        }
        // Don't clear the team highlight when clue closes - team keeps control
    }

    #[On('reset-buzzers')]
    public function clearHighlight()
    {
        $this->activeTeamId = null;
    }

    #[On('score-adjusted')]
    public function handleScoreAdjustment($event = null)
    {
        // Handle both event object and direct parameters
        if (is_array($event)) {
            $teamId = $event['teamId'] ?? null;
            $newScore = $event['newScore'] ?? null;
        } else {
            $teamId = func_get_arg(0);
            $newScore = func_get_arg(1) ?? null;
        }
        
        // Refresh scores when host manually adjusts
        $this->refreshScores();
        
        if ($teamId) {
            // Show animation for adjusted score
            $this->recentScoreChanges[$teamId] = [
                'points' => 0, // Don't show specific amount, just trigger animation
                'correct' => true,
                'timestamp' => now()->timestamp,
            ];
            
            $this->dispatch('clear-score-animation-js', teamId: $teamId);
        }
    }

    public function render()
    {
        return view('livewire.team-scoreboard');
    }
}
