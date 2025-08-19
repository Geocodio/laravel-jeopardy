<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Team;
use Livewire\Component;

class Leaderboard extends Component
{
    public $teams;

    public $game;

    public $showAnimation = true;

    public function mount($gameId)
    {
        $this->game = Game::findOrFail($gameId);

        $teams = Team::where('game_id', $this->game->id)
            ->orderByDesc('score')
            ->get()
            ->map(function ($team, $index) {
                $team->position = $index + 1;

                return $team;
            });

        // Calculate animation delays for bottom-to-top reveal
        // Last place gets shortest delay, first place gets longest
        $totalTeams = $teams->count();
        $this->teams = $teams->map(function ($team) use ($totalTeams) {
            $team->animation_delay = ($totalTeams - $team->position) * 0.8;

            return $team;
        });
    }

    public function render()
    {
        return view('livewire.leaderboard')
            ->layout('layouts.game');
    }
}
