<?php

namespace App\Livewire;

use App\Events\ClueRevealed;
use App\Events\DailyDoubleTriggered;
use App\Events\GameStateChanged;
use App\Events\ScoreUpdated;
use App\Models\Clue;
use App\Models\Game;
use App\Models\Team;
use App\Services\BuzzerService;
use App\Services\ScoringService;
use Livewire\Attributes\On;
use Livewire\Component;
use Log;

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

        // Update the database and ensure it's saved
        $this->game->current_team_id = $teamId;
        $this->game->save();

        // Verify the save worked
        Log::info('Team selected', [
            'game_id' => $this->game->id,
            'team_id' => $teamId,
            'saved_team_id' => $this->game->fresh()->current_team_id
        ]);

        // Broadcast team selection to all clients
        broadcast(new GameStateChanged($this->game->id, 'team-selected', ['teamId' => $teamId]));
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

        // Check if Daily Double
        if ($clue->is_daily_double) {
            $this->showDailyDoubleWager = true;
            $this->dailyDoubleTeam = $this->currentTeam;
            $this->dailyDoubleWager = 0;
            // Broadcast daily double sound event
            broadcast(new DailyDoubleTriggered($this->game->id, $clueId));
        }

        // Update game state
        $clue->update(['is_revealed' => true]);
        $this->game->update(['current_clue_id' => $clueId]);

        // Broadcast the ClueRevealed event to all connected clients
        broadcast(new ClueRevealed($this->game->id, $clueId));

        // Local dispatch for components on this page
        $this->dispatch('remote-clue-selected', clueId: $clueId)->to(ClueDisplay::class);
    }

    // Daily Double Wager
    public function setDailyDoubleWager($amount)
    {
        $this->dailyDoubleWager = max(5, min($amount, 2000));
        $this->showDailyDoubleWager = false;

        // Broadcast wager to all clients
        broadcast(new GameStateChanged($this->game->id, 'daily-double-wager-set', [
            'teamId' => $this->dailyDoubleTeam->id,
            'wager' => $this->dailyDoubleWager
        ]));
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
        $this->game->current_team_id = $team->id;
        $this->game->save();

        // Mark clue as answered
        $this->selectedClue->update(['is_answered' => true]);

        // Refresh team to get updated score
        $team->refresh();

        // First broadcast team selection so ClueDisplay knows who answered
        broadcast(new GameStateChanged($this->game->id, 'team-selected', [
            'teamId' => $team->id
        ]));

        // Broadcast score update
        $event = new ScoreUpdated(
            $this->game->id,
            $team->id,
            $team->score,
            $points,
            true
        );

        Log::info('Broadcasting ScoreUpdated event', [
            'game_id' => $this->game->id,
            'team_id' => $team->id,
            'points' => $points,
            'new_score' => $team->score
        ]);

        broadcast($event);

        // Broadcast game state for answer judged
        broadcast(new GameStateChanged($this->game->id, 'answer-judged', [
            'clueId' => $this->selectedClue->id,
            'teamId' => $team->id,
            'correct' => true,
            'points' => $points
        ]));

        // Close the clue modal after a short delay
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
            // Mark clue as answered for Daily Double (only one chance)
            $this->selectedClue->update(['is_answered' => true]);
        } else {
            $this->scoringService->deductPoints($team->id, $this->selectedClue->value);
        }

        // Clear current team to open buzzers for others (unless Daily Double)
        if (!$this->selectedClue->is_daily_double) {
            $this->currentTeam = null;
            $this->game->current_team_id = null;
            $this->game->save();

        }

        // Refresh team to get updated score
        $team->refresh();

        // Broadcast score update
        broadcast(new ScoreUpdated(
            $this->game->id,
            $team->id,
            $team->score,
            -$points,
            false
        ));

        // Broadcast incorrect answer (but don't close clue for regular questions)
        broadcast(new GameStateChanged($this->game->id, 'answer-incorrect', [
            'clueId' => $this->selectedClue->id,
            'teamId' => $team->id,
            'points' => -$points
        ]));

        // Only close clue for Daily Double (they only get one chance)
        if ($this->selectedClue->is_daily_double) {
            $this->closeClue();
        }
        // For regular clues, keep it open for other teams to buzz in
    }

    public function skipClue()
    {
        if ($this->selectedClue) {
            $this->selectedClue->update(['is_answered' => true]);
            broadcast(new GameStateChanged($this->game->id, 'clue-skipped', ['clueId' => $this->selectedClue->id]));
            $this->closeClue();
        }
    }

    public function closeClue()
    {
        $this->showClueModal = false;
        $this->selectedClue = null;
        $this->showDailyDoubleWager = false;
        $this->dailyDoubleTeam = null;
        $this->dailyDoubleWager = 0;

        $this->game->update(['current_clue_id' => null]);

        // Broadcast clue closed to all clients
        broadcast(new GameStateChanged($this->game->id, 'clue-closed'));

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


    // Listen for buzzer events from main display
    #[On('buzzer-webhook-received')]
    public function handleBuzzerWebhook($teamId)
    {
        if ($this->showClueModal && !$this->currentTeam && !$this->selectedClue->is_daily_double) {
            $this->currentTeam = Team::find($teamId);
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
