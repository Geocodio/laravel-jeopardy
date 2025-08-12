<?php

namespace App\Livewire;

use App\Models\Team;
use App\Services\ScoringService;
use Livewire\Component;
use Livewire\Attributes\On;

class TeamScoreboard extends Component
{
    public $teams;
    public ?int $activeTeamId = null;
    public int $gameId;

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
