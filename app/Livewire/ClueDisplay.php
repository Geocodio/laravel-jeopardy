<?php

namespace App\Livewire;

use App\Models\Clue;
use App\Models\Team;
use App\Services\BuzzerService;
use App\Services\ScoringService;
use Livewire\Attributes\On;
use Livewire\Component;

class ClueDisplay extends Component
{
    public ?Clue $clue = null;

    public ?Team $buzzerTeam = null;

    public bool $isDailyDouble = false;

    public int $wagerAmount = 0;

    public bool $showManualTeamSelection = false;

    public $availableTeams = [];

    protected $scoringService;

    protected $buzzerService;

    public function boot(ScoringService $scoringService, BuzzerService $buzzerService)
    {
        $this->scoringService = $scoringService;
        $this->buzzerService = $buzzerService;
    }

    public function mount($clueId = null)
    {
        if ($clueId) {
            $this->loadClue($clueId);
        }
    }

    public function loadClue($clueId)
    {
        $this->clue = Clue::with(['category', 'category.game.teams'])->findOrFail($clueId);
        $this->isDailyDouble = $this->clue->is_daily_double;
        $this->availableTeams = $this->clue->category->game->teams;

        // If there's a current team in control, set them as the buzzer team
        $game = $this->clue->category->game;
        if ($game->current_team_id) {
            $this->buzzerTeam = Team::find($game->current_team_id);
        }
    }

    #[On('buzzer-pressed')]
    public function handleBuzzer($teamId)
    {
        // Check if there's already a team in control
        if ($this->buzzerTeam) {
            return;
        }

        // Check if buzzers should be enabled (no current team in control)
        $game = $this->clue->category->game;
        $game->refresh();
        if ($game->current_team_id && ! $this->isDailyDouble) {
            return; // Buzzers are disabled, a team is in control
        }

        $this->buzzerTeam = Team::find($teamId);
        $this->dispatch('buzzer-accepted', teamId: $teamId);
    }

    public function markCorrect()
    {
        if (! $this->buzzerTeam || ! $this->clue) {
            return;
        }

        $pointsAwarded = $this->isDailyDouble ? (int) $this->wagerAmount : (int) $this->clue->value;

        if ($this->isDailyDouble) {
            $this->scoringService->handleDailyDouble(
                $this->buzzerTeam->id,
                $this->wagerAmount,
                true
            );
            // Mark Daily Double clue as answered
            $this->clue->update(['is_answered' => true]);
        } else {
            $this->scoringService->recordAnswer(
                $this->clue->id,
                $this->buzzerTeam->id,
                true
            );
        }

        // Update current team - they keep control
        $this->clue->category->game->update(['current_team_id' => $this->buzzerTeam->id]);
        $this->clue->category->game->refresh();

        $this->dispatch('play-sound', sound: 'correct');
        $this->dispatch('score-updated',
            teamId: $this->buzzerTeam->id,
            points: $pointsAwarded,
            correct: true
        );
        $this->dispatch('team-keeps-control', teamId: $this->buzzerTeam->id);
        $this->dispatch('clue-answered', clueId: $this->clue->id);
        $this->reset(['buzzerTeam', 'showManualTeamSelection', 'wagerAmount']);
    }

    public function markIncorrect()
    {
        if (! $this->buzzerTeam || ! $this->clue) {
            return;
        }

        $pointsDeducted = $this->isDailyDouble ? (int) $this->wagerAmount : (int) $this->clue->value;

        if ($this->isDailyDouble) {
            $this->scoringService->handleDailyDouble(
                $this->buzzerTeam->id,
                $this->wagerAmount,
                false
            );
            // Mark Daily Double clue as answered
            $this->clue->update(['is_answered' => true]);
            $this->dispatch('score-updated',
                teamId: $this->buzzerTeam->id,
                points: -$pointsDeducted,
                correct: false,
                teamName: $this->buzzerTeam->name,
                teamColor: $this->buzzerTeam->color_hex
            );
            $this->dispatch('clue-answered', clueId: $this->clue->id);
            $this->reset(['buzzerTeam', 'showManualTeamSelection', 'wagerAmount']);
        } else {
            // Deduct points but allow other teams to buzz
            $this->scoringService->deductPoints(
                $this->buzzerTeam->id,
                $this->clue->value
            );

            $this->dispatch('score-updated',
                teamId: $this->buzzerTeam->id,
                points: -$pointsDeducted,
                correct: false,
                teamName: $this->buzzerTeam->name,
                teamColor: $this->buzzerTeam->color_hex
            );

            // Check if this was the controlling team
            $game = $this->clue->category->game;
            if ($game->current_team_id == $this->buzzerTeam->id) {
                // Controlling team got it wrong, open buzzers for everyone
                $game->update(['current_team_id' => null]);
                $game->refresh();
                $this->dispatch('team-lost-control', teamId: $this->buzzerTeam->id);
            }

            $this->buzzerService->lockoutTeam($this->buzzerTeam->id);
            $this->buzzerTeam = null;
            $this->showManualTeamSelection = false;
        }

        $this->dispatch('play-sound', sound: 'incorrect');
    }

    public function skipClue()
    {
        if ($this->clue) {
            $this->clue->update(['is_answered' => true]);
            $this->dispatch('clue-answered', clueId: $this->clue->id);
            $this->reset(['buzzerTeam', 'showManualTeamSelection', 'wagerAmount']);
        }
    }

    public function setWager($amount)
    {
        $this->wagerAmount = max(5, min($amount, 2000));
    }

    public function toggleManualTeamSelection()
    {
        $this->showManualTeamSelection = ! $this->showManualTeamSelection;
    }

    public function selectTeamManually($teamId)
    {
        if ($this->buzzerTeam) {
            return;
        }

        $this->buzzerTeam = Team::find($teamId);
        $this->showManualTeamSelection = false;

        // Ensure clue is fresh
        if ($this->clue) {
            $this->clue->refresh();
        }

        $this->dispatch('buzzer-accepted', teamId: $teamId);
    }

    // Listen for broadcast events
    #[On('clue-revealed')]
    public function handleClueRevealed($clueId)
    {
        $this->loadClue($clueId);
    }

    #[On('game-state-changed')]
    public function handleGameStateChanged($state, $data = [])
    {
        if ($state === 'answer-judged' && isset($data['clueId'])) {
            // Update local state when host judges answer as correct
            if ($this->clue && $this->clue->id == $data['clueId']) {
                $this->dispatch('clue-answered', clueId: $data['clueId']);
                // Don't reset immediately - let GameBoard handle the modal closing
            }
        } elseif ($state === 'answer-incorrect' && isset($data['clueId'])) {
            // Handle incorrect answer from host
            if ($this->clue && $this->clue->id == $data['clueId']) {
                $this->buzzerTeam = null;
            }
        } elseif ($state === 'clue-closed') {
            $this->reset(['clue', 'buzzerTeam', 'showManualTeamSelection', 'wagerAmount']);
        } elseif ($state === 'team-selected' && isset($data['teamId'])) {
            // Update current team when host selects
            if (! $this->buzzerTeam && $this->clue && ! $this->clue->is_daily_double) {
                $this->buzzerTeam = Team::find($data['teamId']);
            }
        } elseif ($state === 'daily-double-wager-set') {
            if (isset($data['teamId']) && isset($data['wager'])) {
                $this->buzzerTeam = Team::find($data['teamId']);
                $this->wagerAmount = $data['wager'];
            }
        }
    }

    // Keep remote control events for backwards compatibility
    #[On('remote-clue-selected')]
    public function handleRemoteClueSelected($clueId)
    {
        $this->loadClue($clueId);
    }

    #[On('team-selected')]
    public function handleTeamSelected($teamId)
    {
        // Update current team when host selects
        if (! $this->buzzerTeam && $this->clue && ! $this->clue->is_daily_double) {
            $this->buzzerTeam = Team::find($teamId);
        }
    }

    public function render()
    {
        return view('livewire.clue-display');
    }
}
