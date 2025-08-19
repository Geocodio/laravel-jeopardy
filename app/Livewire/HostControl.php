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

    public int $dailyDoubleWager = 0;

    public array $wagerOptions = [];

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
            'lightningQuestions',
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

        // If game just started and no team is selected, select Team Illuminate
        if ($this->game->status === 'main_game' && ! $this->currentTeam) {
            $teamIlluminate = $this->teams->where('name', 'Team Illuminate')->first();
            if ($teamIlluminate) {
                $this->selectCurrentTeam($teamIlluminate->id);
            }
        }
    }

    // Team Selection
    public function selectCurrentTeam($teamId)
    {
        // If clicking on already selected team, deselect it
        if ($this->currentTeam && $this->currentTeam->id == $teamId) {
            $this->currentTeam = null;
            $this->game->current_team_id = null;
            $this->game->save();

            Log::info('Team deselected', [
                'game_id' => $this->game->id,
                'previous_team_id' => $teamId,
            ]);

            // Broadcast team deselection to all clients
            broadcast(new GameStateChanged($this->game->id, 'team-deselected'));
        } else {
            // Select the new team
            $this->currentTeam = Team::find($teamId);

            // Update the database and ensure it's saved
            $this->game->current_team_id = $teamId;
            $this->game->save();

            // Verify the save worked
            Log::info('Team selected', [
                'game_id' => $this->game->id,
                'team_id' => $teamId,
                'saved_team_id' => $this->game->fresh()->current_team_id,
            ]);

            // Broadcast team selection to all clients
            broadcast(new GameStateChanged($this->game->id, 'team-selected', ['teamId' => $teamId]));
        }
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
            $this->dailyDoubleWager = 0;
            $this->calculateWagerOptions();
            // Broadcast daily double sound event
            broadcast(new DailyDoubleTriggered($this->game->id, $clueId));
        }

        // Update game state
        $clue->update(['is_revealed' => true]);
        $this->game->update(['current_clue_id' => $clueId]);
        $this->game->refresh();

        // Broadcast the ClueRevealed event to all connected clients
        broadcast(new ClueRevealed($this->game->id, $clueId));

        // Local dispatch for components on this page
        $this->dispatch('remote-clue-selected', clueId: $clueId)->to(ClueDisplay::class);
    }

    // Daily Double Wager
    public function setDailyDoubleWager($amount)
    {
        $maxWager = $this->getMaximumWager();
        $this->dailyDoubleWager = max(100, min($amount, $maxWager));
        $this->showDailyDoubleWager = false;

        // Broadcast wager to all clients
        broadcast(new GameStateChanged($this->game->id, 'daily-double-wager-set', [
            'teamId' => $this->currentTeam->id,
            'wager' => $this->dailyDoubleWager,
        ]));
    }

    private function getMaximumWager()
    {
        if (! $this->currentTeam) {
            return 400; // Default max if no team selected
        }

        // Maximum is either current score (if positive) or highest clue value in round
        $teamScore = $this->currentTeam->score;

        // Determine the highest clue value based on game phase
        // In regular Jeopardy, max clue value is 400
        // Could be extended for Double Jeopardy round (800) in future
        $highestClueValue = 400;

        // If team has positive score, they can wager up to their score
        // If zero or negative, they can wager up to the highest clue value
        return $teamScore > 0 ? $teamScore : $highestClueValue;
    }

    private function calculateWagerOptions()
    {
        if (! $this->currentTeam) {
            $this->wagerOptions = [];

            return;
        }

        $maxWager = $this->getMaximumWager();
        $options = [];

        // Always include minimum wager (we'll use 100 as minimum for simplicity)
        $options[] = 100;

        // Add common increments
        $increments = [200, 300, 400, 500, 600, 800, 1000, 1200, 1500, 2000];

        foreach ($increments as $amount) {
            if ($amount <= $maxWager && ! in_array($amount, $options)) {
                $options[] = $amount;
            }
        }

        // If team score is positive and not already in options, add it as "True Daily Double"
        if ($this->currentTeam->score > 0 && ! in_array($this->currentTeam->score, $options)) {
            $options[] = $this->currentTeam->score;
        }

        // Sort options
        sort($options);

        // Limit to 8 options for UI consistency
        if (count($options) > 8) {
            // Keep first few, some middle values, and the max
            $filtered = [];
            $filtered[] = $options[0]; // minimum

            // Add evenly distributed values
            $step = (count($options) - 2) / 6; // We want 6 middle values
            for ($i = 1; $i <= 6; $i++) {
                $index = min(round($i * $step), count($options) - 2);
                if (! in_array($options[$index], $filtered)) {
                    $filtered[] = $options[$index];
                }
            }

            $filtered[] = $options[count($options) - 1]; // maximum

            $this->wagerOptions = $filtered;
        } else {
            $this->wagerOptions = $options;
        }
    }

    public function markCorrect($teamId = null)
    {
        $team = $teamId ? Team::find($teamId) : $this->currentTeam;

        if (! $team || ! $this->selectedClue) {
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
            'teamId' => $team->id,
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
            'new_score' => $team->score,
        ]);

        broadcast($event);

        // Broadcast game state for answer judged
        broadcast(new GameStateChanged($this->game->id, 'answer-judged', [
            'clueId' => $this->selectedClue->id,
            'teamId' => $team->id,
            'correct' => true,
            'points' => $points,
        ]));

        // Close the clue modal after a short delay
        $this->closeClue();
    }

    public function markIncorrect($teamId = null)
    {
        $team = $teamId ? Team::find($teamId) : $this->currentTeam;

        if (! $team || ! $this->selectedClue) {
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
        if (! $this->selectedClue->is_daily_double) {
            $this->currentTeam = null;
            $this->game->current_team_id = null;
            $this->game->save();

            // Broadcast that buzzers are now open
            broadcast(new GameStateChanged($this->game->id, 'buzzers-opened'));
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
            'points' => -$points,
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
        $this->dailyDoubleWager = 0;
        $this->wagerOptions = [];

        $this->game->update(['current_clue_id' => null]);
        $this->game->refresh();

        // Broadcast clue closed to all clients
        broadcast(new GameStateChanged($this->game->id, 'clue-closed'));

        $this->refreshGame();
    }

    // Manual Score Adjustment
    public function adjustScore($teamId, $amount)
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        $team->increment('score', $amount);

        $this->dispatch('score-adjusted',
            teamId: $teamId,
            newScore: $team->score
        )->to(TeamScoreboard::class);

        $this->teams = $this->game->teams()->get();
    }

    // Lightning Round Control
    public function startLightningRound()
    {
        if ($this->game->status !== 'main_game') {
            return;
        }

        $this->game->update(['status' => 'lightning_round']);
        $this->game->refresh();

        // Redirect to lightning round
        return redirect()->route('game.lightning', ['gameId' => $this->game->id]);
    }

    // Listen for buzzer events from main display
    #[On('buzzer-webhook-received')]
    public function handleBuzzerWebhook($teamId)
    {
        if ($this->showClueModal && ! $this->currentTeam && ! $this->selectedClue->is_daily_double) {
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
