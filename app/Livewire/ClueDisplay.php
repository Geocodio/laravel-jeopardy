<?php

namespace App\Livewire;

use App\Models\Clue;
use App\Models\Team;
use App\Services\ScoringService;
use App\Services\BuzzerService;
use Livewire\Component;
use Livewire\Attributes\On;

class ClueDisplay extends Component
{
    public ?Clue $clue = null;
    public int $timeRemaining = 30;
    public ?Team $buzzerTeam = null;
    public bool $isDailyDouble = false;
    public int $wagerAmount = 0;
    public bool $timerRunning = false;
    public bool $showingAnswer = false;

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
        $this->clue = Clue::with('category')->findOrFail($clueId);
        $this->isDailyDouble = $this->clue->is_daily_double;
        $this->timeRemaining = $this->isDailyDouble ? 0 : 30;
        
        if (!$this->isDailyDouble) {
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
        if ($this->buzzerTeam || !$this->timerRunning) {
            return;
        }

        $this->buzzerTeam = Team::find($teamId);
        $this->timerRunning = false;
        $this->dispatch('buzzer-accepted', teamId: $teamId);
    }

    public function markCorrect()
    {
        if (!$this->buzzerTeam || !$this->clue) {
            return;
        }

        if ($this->isDailyDouble) {
            $this->scoringService->handleDailyDouble(
                $this->buzzerTeam->id,
                $this->wagerAmount,
                true
            );
        } else {
            $this->scoringService->recordAnswer(
                $this->clue->id,
                $this->buzzerTeam->id,
                true
            );
        }

        $this->dispatch('play-sound', sound: 'correct');
        $this->dispatch('clue-answered', clueId: $this->clue->id);
        $this->reset(['buzzerTeam', 'timerRunning', 'showingAnswer']);
    }

    public function markIncorrect()
    {
        if (!$this->buzzerTeam || !$this->clue) {
            return;
        }

        if ($this->isDailyDouble) {
            $this->scoringService->handleDailyDouble(
                $this->buzzerTeam->id,
                $this->wagerAmount,
                false
            );
            $this->dispatch('clue-answered', clueId: $this->clue->id);
        } else {
            // Deduct points but allow other teams to buzz
            $this->scoringService->deductPoints(
                $this->buzzerTeam->id,
                $this->clue->value
            );
            
            $this->buzzerService->lockoutTeam($this->buzzerTeam->id);
            $this->buzzerTeam = null;
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
        $this->startTimer();
    }

    public function showAnswer()
    {
        $this->showingAnswer = true;
    }

    public function render()
    {
        return view('livewire.clue-display');
    }
}
