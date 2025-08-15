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

    public int $timeRemaining = 30;

    public ?Team $buzzerTeam = null;

    public bool $isDailyDouble = false;

    public int $wagerAmount = 0;

    public bool $timerRunning = false;

    public bool $showingAnswer = false;

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
        $this->timeRemaining = 30;
        $this->availableTeams = $this->clue->category->game->teams;

        // Don't start timer for Daily Double - wait for wager
        if (! $this->isDailyDouble) {
            $this->startTimer();
        }
    }

    public function startTimer()
    {
        $this->timerRunning = true;
        $this->dispatch('start-timer');
    }

    #[On('timer-tick')]
    public function handleTimerTick()
    {
        if ($this->timerRunning && $this->timeRemaining > 0) {
            $this->timeRemaining--;

            if ($this->timeRemaining === 0) {
                $this->handleTimerExpired();
            }
        }
    }

    public function handleTimerExpired()
    {
        $this->timerRunning = false;
        $this->dispatch('timer-expired');
        $this->dispatch('play-sound', sound: 'times-up');
    }

    #[On('buzzer-pressed')]
    public function handleBuzzer($teamId)
    {
        if ($this->buzzerTeam || ! $this->timerRunning) {
            return;
        }

        $this->buzzerTeam = Team::find($teamId);
        $this->timerRunning = false;
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

        $this->dispatch('play-sound', sound: 'correct');
        $this->dispatch('score-updated',
            teamId: $this->buzzerTeam->id,
            points: $pointsAwarded,
            correct: true
        );
        $this->dispatch('team-keeps-control', teamId: $this->buzzerTeam->id);
        $this->dispatch('clue-answered', clueId: $this->clue->id);
        $this->reset(['buzzerTeam', 'timerRunning', 'showingAnswer', 'showManualTeamSelection', 'wagerAmount']);
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
                correct: false
            );
            $this->dispatch('clue-answered', clueId: $this->clue->id);
            $this->reset(['buzzerTeam', 'timerRunning', 'showingAnswer', 'showManualTeamSelection', 'wagerAmount']);
        } else {
            // Deduct points but allow other teams to buzz
            $this->scoringService->deductPoints(
                $this->buzzerTeam->id,
                $this->clue->value
            );

            $this->dispatch('score-updated',
                teamId: $this->buzzerTeam->id,
                points: -$pointsDeducted,
                correct: false
            );

            // Check if this was the controlling team
            $game = $this->clue->category->game;
            if ($game->current_team_id == $this->buzzerTeam->id) {
                // Controlling team got it wrong, open buzzers for everyone
                $game->update(['current_team_id' => null]);
                $this->dispatch('team-lost-control', teamId: $this->buzzerTeam->id);
            }

            $this->buzzerService->lockoutTeam($this->buzzerTeam->id);
            $this->buzzerTeam = null;
            $this->showManualTeamSelection = false;
            $this->startTimer();
        }

        $this->dispatch('play-sound', sound: 'incorrect');
    }

    public function skipClue()
    {
        if ($this->clue) {
            $this->clue->update(['is_answered' => true]);
            $this->dispatch('clue-answered', clueId: $this->clue->id);
        }
    }

    public function setWager($amount)
    {
        $this->wagerAmount = max(5, min($amount, 2000));
        // Start timer after wager is set
        $this->timeRemaining = 30;
        $this->startTimer();
    }

    public function showAnswer()
    {
        $this->showingAnswer = true;
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
        $this->timerRunning = false;
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
            // Update local state when host judges answer
            if ($this->clue && $this->clue->id == $data['clueId']) {
                $this->timerRunning = false;
                $this->dispatch('clue-answered', clueId: $data['clueId']);
                // Don't reset immediately - let GameBoard handle the modal closing
            }
        } elseif ($state === 'clue-closed') {
            $this->timerRunning = false;
            $this->reset(['clue', 'buzzerTeam', 'timerRunning', 'showingAnswer', 'showManualTeamSelection', 'wagerAmount']);
        } elseif ($state === 'team-selected' && isset($data['teamId'])) {
            // Update current team when host selects
            if (!$this->buzzerTeam && $this->clue && !$this->clue->is_daily_double) {
                $this->buzzerTeam = Team::find($data['teamId']);
                $this->timerRunning = false;
            }
        } elseif ($state === 'daily-double-wager-set') {
            if (isset($data['teamId']) && isset($data['wager'])) {
                $this->buzzerTeam = Team::find($data['teamId']);
                $this->wagerAmount = $data['wager'];
                $this->timeRemaining = 30;
                $this->startTimer();
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
        if (!$this->buzzerTeam && $this->clue && !$this->clue->is_daily_double) {
            $this->buzzerTeam = Team::find($teamId);
            $this->timerRunning = false;
        }
    }

    public function render()
    {
        return view('livewire.clue-display');
    }
}
