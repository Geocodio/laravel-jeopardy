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
    }

    #[On('score-updated')]
    public function handleScoreUpdate($teamId, $points, $correct)
    {
        \Log::info('TeamScoreboard received score update', [
            'teamId' => $teamId,
            'points' => $points,
            'correct' => $correct,
            'type_of_points' => gettype($points),
        ]);
        
        $this->recentScoreChanges[$teamId] = [
            'points' => (int) $points,
            'correct' => $correct,
            'timestamp' => now()->timestamp,
        ];

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
        $this->activeTeamId = $teamId;
    }

    #[On('reset-buzzers')]
    public function clearHighlight()
    {
        $this->activeTeamId = null;
    }

    public function render()
    {
        return view('livewire.team-scoreboard');
    }
}
