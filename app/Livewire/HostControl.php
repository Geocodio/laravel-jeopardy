<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Team;
use App\Models\Clue;
use App\Services\ScoringService;
use App\Services\BuzzerService;
use Livewire\Component;
use Livewire\Attributes\On;

class HostControl extends Component
{
    public ?Game $game = null;
    public $categories;
    public ?Clue $selectedClue = null;
    public ?Team $currentTeam = null;
    public $teams = [];
    
    // Daily Double
    public bool $showDailyDoubleWager = false;
    public ?Team $dailyDoubleTeam = null;
    public int $dailyDoubleWager = 0;
    
    // Clue Display
    public bool $showClueModal = false;
    public bool $showAnswer = false;
    public int $timeRemaining = 30;
    public bool $timerRunning = false;
    
    protected $scoringService;
    protected $buzzerService;

    public function boot(ScoringService $scoringService, BuzzerService $buzzerService)
    {
        $this->scoringService = $scoringService;
        $this->buzzerService = $buzzerService;
    }

    public function mount($gameId)
    {
        $this->loadGame($gameId);
    }

    public function loadGame($gameId)
    {
        $this->game = Game::with([
            'categories.clues',
            'teams',
            'lightningQuestions'
        ])->findOrFail($gameId);

        $this->categories = $this->game->categories->sortBy('position');
        $this->teams = $this->game->teams;
        $this->currentTeam = $this->game->current_team_id ? 
            $this->teams->find($this->game->current_team_id) : null;
            
        // Check if there's an active clue
        if ($this->game->current_clue_id) {
            $this->selectedClue = Clue::find($this->game->current_clue_id);
            $this->showClueModal = true;
        }
    }

    // Team Selection
    public function selectCurrentTeam($teamId)
    {
        $this->currentTeam = Team::find($teamId);
        $this->game->update(['current_team_id' => $teamId]);
        
        // Broadcast to main display
        $this->dispatch('team-selected', teamId: $teamId)->to(GameBoard::class);
        $this->dispatch('team-selected', teamId: $teamId)->to(ClueDisplay::class);
    }

    // Clue Control
    public function selectClue($clueId)
    {
        $clue = Clue::findOrFail($clueId);
        
        if ($clue->is_answered) {
            return;
        }

        $this->selectedClue = $clue;
        $this->showClueModal = true;
        $this->showAnswer = false;
        $this->timeRemaining = 30;
        
        // Check if Daily Double
        if ($clue->is_daily_double) {
            $this->showDailyDoubleWager = true;
            $this->dailyDoubleTeam = $this->currentTeam;
            $this->dailyDoubleWager = 0;
            $this->dispatch('play-sound', sound: 'daily-double')->to(GameBoard::class);
        } else {
            $this->timerRunning = true;
        }
        
        // Update game state
        $clue->update(['is_revealed' => true]);
        $this->game->update(['current_clue_id' => $clueId]);
        
        // Broadcast to main display
        $this->dispatch('clue-selected', clueId: $clueId)->to(GameBoard::class);
        $this->dispatch('remote-clue-selected', clueId: $clueId)->to(ClueDisplay::class);
    }

    // Daily Double Wager
    public function setDailyDoubleWager($amount)
    {
        $this->dailyDoubleWager = max(5, min($amount, 2000));
        $this->showDailyDoubleWager = false;
        $this->timerRunning = true;
        
        // Broadcast wager to main display
        $this->dispatch('daily-double-wager-set', 
            teamId: $this->dailyDoubleTeam->id,
            wager: $this->dailyDoubleWager
        )->to(ClueDisplay::class);
    }

    // Answer Control
    public function revealAnswer()
    {
        $this->showAnswer = true;
    }

    public function markCorrect($teamId = null)
    {
        $team = $teamId ? Team::find($teamId) : $this->currentTeam;
        
        if (!$team || !$this->selectedClue) {
            return;
        }

        $points = $this->selectedClue->is_daily_double ? 
            $this->dailyDoubleWager : $this->selectedClue->value;

        // Award points
        if ($this->selectedClue->is_daily_double) {
            $this->scoringService->handleDailyDouble($team->id, $this->dailyDoubleWager, true);
        } else {
            $this->scoringService->recordAnswer($this->selectedClue->id, $team->id, true);
        }
        
        // Update current team (they keep control)
        $this->currentTeam = $team;
        $this->game->update(['current_team_id' => $team->id]);
        
        // Mark clue as answered
        $this->selectedClue->update(['is_answered' => true]);
        
        // Broadcast events
        $this->dispatch('answer-judged', 
            clueId: $this->selectedClue->id,
            teamId: $team->id,
            correct: true,
            points: $points
        )->to(ClueDisplay::class);
        
        $this->dispatch('score-updated', 
            teamId: $team->id,
            points: $points,
            correct: true
        )->to(TeamScoreboard::class);
        
        $this->dispatch('play-sound', sound: 'correct')->to(GameBoard::class);
        
        $this->closeClue();
    }

    public function markIncorrect($teamId = null)
    {
        $team = $teamId ? Team::find($teamId) : $this->currentTeam;
        
        if (!$team || !$this->selectedClue) {
            return;
        }

        $points = $this->selectedClue->is_daily_double ? 
            $this->dailyDoubleWager : $this->selectedClue->value;

        // Deduct points
        if ($this->selectedClue->is_daily_double) {
            $this->scoringService->handleDailyDouble($team->id, $this->dailyDoubleWager, false);
            // Mark clue as answered for Daily Double
            $this->selectedClue->update(['is_answered' => true]);
        } else {
            $this->scoringService->deductPoints($team->id, $this->selectedClue->value);
        }
        
        // Open buzzers for other teams (unless Daily Double)
        if (!$this->selectedClue->is_daily_double) {
            $this->currentTeam = null;
            $this->game->update(['current_team_id' => null]);
            $this->dispatch('open-buzzers')->to(BuzzerListener::class);
        }
        
        // Broadcast events
        $this->dispatch('answer-judged', 
            clueId: $this->selectedClue->id,
            teamId: $team->id,
            correct: false,
            points: -$points
        )->to(ClueDisplay::class);
        
        $this->dispatch('score-updated', 
            teamId: $team->id,
            points: -$points,
            correct: false
        )->to(TeamScoreboard::class);
        
        $this->dispatch('play-sound', sound: 'incorrect')->to(GameBoard::class);
        
        if ($this->selectedClue->is_daily_double) {
            $this->closeClue();
        }
    }

    public function skipClue()
    {
        if ($this->selectedClue) {
            $this->selectedClue->update(['is_answered' => true]);
            $this->dispatch('clue-skipped', clueId: $this->selectedClue->id)->to(GameBoard::class);
            $this->closeClue();
        }
    }

    public function closeClue()
    {
        $this->showClueModal = false;
        $this->selectedClue = null;
        $this->showAnswer = false;
        $this->showDailyDoubleWager = false;
        $this->dailyDoubleTeam = null;
        $this->dailyDoubleWager = 0;
        $this->timerRunning = false;
        
        $this->game->update(['current_clue_id' => null]);
        
        // Broadcast to main display
        $this->dispatch('clue-closed')->to(GameBoard::class);
        $this->dispatch('clue-closed')->to(ClueDisplay::class);
        
        $this->refreshGame();
    }

    // Manual Score Adjustment
    public function adjustScore($teamId, $amount)
    {
        $team = Team::find($teamId);
        if (!$team) return;
        
        $team->increment('score', $amount);
        
        $this->dispatch('score-adjusted', 
            teamId: $teamId,
            newScore: $team->score
        )->to(TeamScoreboard::class);
        
        $this->teams = $this->game->teams()->get();
    }

    // Manual Buzzer Trigger
    public function triggerBuzzer($teamId)
    {
        $team = Team::find($teamId);
        if (!$team) return;
        
        // Simulate buzzer press
        $this->dispatch('buzzer-pressed', teamId: $teamId)->to(ClueDisplay::class);
        $this->dispatch('buzzer-pressed', teamId: $teamId)->to(BuzzerListener::class);
        
        // Set as current answering team
        $this->currentTeam = $team;
    }

    // Lightning Round Control
    public function startLightningRound()
    {
        if ($this->game->status !== 'main_game') {
            return;
        }

        $this->game->update(['status' => 'lightning_round']);
        
        // Redirect to lightning round
        return redirect()->route('game.lightning', ['gameId' => $this->game->id]);
    }

    // Timer Management
    #[On('timer-tick')]
    public function handleTimerTick()
    {
        if ($this->timerRunning && $this->timeRemaining > 0) {
            $this->timeRemaining--;
            
            if ($this->timeRemaining === 0) {
                $this->timerRunning = false;
                $this->dispatch('timer-expired')->to(ClueDisplay::class);
                $this->dispatch('play-sound', sound: 'times-up')->to(GameBoard::class);
            }
        }
    }

    // Listen for buzzer events from main display
    #[On('buzzer-webhook-received')]
    public function handleBuzzerWebhook($teamId)
    {
        if ($this->showClueModal && !$this->currentTeam && !$this->selectedClue->is_daily_double) {
            $this->currentTeam = Team::find($teamId);
            $this->timerRunning = false;
        }
    }

    private function refreshGame()
    {
        $this->game->refresh();
        $this->categories = $this->game->categories->sortBy('position');
        $this->teams = $this->game->teams;
    }

    public function render()
    {
        return view('livewire.host-control')
            ->layout('layouts.game');
    }
}