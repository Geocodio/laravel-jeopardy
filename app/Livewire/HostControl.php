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
use App\Services\GameService;
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
        if ($this->game->status === 'main_game' && !$this->currentTeam) {
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

    // Trigger buzzer for a team
    public function triggerBuzzer($teamId)
    {
        $team = Team::find($teamId);

        if (!$team) {
            return;
        }

        // Use centralized buzzer handling logic
        $this->buzzerService->handleBuzzerPress($team);

        // Update local state
        $this->currentTeam = $team;
        $this->game->refresh();
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
        if (!$this->currentTeam) {
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
        if (!$this->currentTeam) {
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
            if ($amount <= $maxWager && !in_array($amount, $options)) {
                $options[] = $amount;
            }
        }

        // If team score is positive and not already in options, add it as "True Daily Double"
        if ($this->currentTeam->score > 0 && !in_array($this->currentTeam->score, $options)) {
            $options[] = $this->currentTeam->score;
        }

        // Sort options
        sort($options);

        $this->wagerOptions = $options;
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
        if (!$team) {
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

        // Transition to lightning round
        $gameService = app(GameService::class);
        $gameService->transitionToLightningRound($this->game->id);

        $this->currentTeam = null; // Clear current team

        // Broadcast event to tell game board to navigate to lightning round
        broadcast(new GameStateChanged($this->game->id, 'lightning-round-started'));

        // Host stays on the control page, no redirect
        $this->refreshGame();
    }

    // Lightning Round Question Controls
    public function markLightningCorrect()
    {
        if ($this->currentTeam) {
            // Award points directly here
            $scoringService = app(ScoringService::class);

            // Need to refresh to get the latest lightning questions
            $this->game->refresh();
            $currentQuestion = $this->game->lightningQuestions
                ->where('is_current', true)
                ->first();

            if ($currentQuestion) {
                $scoringService->recordLightningAnswer(
                    $currentQuestion->id,
                    $this->currentTeam->id,
                    true
                );

                // Mark question as answered and not current
                $currentQuestion->update([
                    'is_current' => false,
                    'is_answered' => true,
                ]);

                // Refresh team to get updated score
                $this->currentTeam->refresh();

                // Broadcast score update
                broadcast(new ScoreUpdated(
                    $this->game->id,
                    $this->currentTeam->id,
                    $this->currentTeam->score,
                    200,
                    true
                ));

                // Get next question
                $this->nextLightningQuestion();
            }
        }

        // Clear current team for next question
        $this->currentTeam = null;
        $this->game->current_team_id = null;
        $this->game->save();

        // Now tell the lightning round component to refresh
        $this->dispatch('lightning-refresh')->to(LightningRound::class);

        // Refresh our own game state
        $this->refreshGame();
    }

    public function markLightningIncorrect()
    {
        if ($this->currentTeam) {
            // Deduct points from current team
            $scoringService = app(ScoringService::class);
            $scoringService->deductPoints($this->currentTeam->id, 200);

            // Refresh team to get updated score
            $this->currentTeam->refresh();

            // Broadcast score update
            broadcast(new ScoreUpdated(
                $this->game->id,
                $this->currentTeam->id,
                $this->currentTeam->score,
                -200,
                false
            ));

            // Clear current team to allow others to buzz in
            $this->currentTeam = null;
            $this->game->current_team_id = null;
            $this->game->save();

            // Broadcast that buzzers are open again
            broadcast(new GameStateChanged($this->game->id, 'buzzers-opened'));
        }
    }

    // Listen for buzzer events from main display
    #[On('buzzer-webhook-received')]
    public function handleBuzzerWebhook($teamId)
    {
        if ($this->showClueModal && !$this->currentTeam && !$this->selectedClue->is_daily_double) {
            $this->currentTeam = Team::find($teamId);
        }
    }

    #[On('buzzer-pressed')]
    public function handleBuzzerPressed($teamId)
    {
        // Track which team buzzed in during lightning round
        if ($this->game->status === 'lightning_round') {
            $this->currentTeam = Team::find($teamId);
            $this->game->current_team_id = $teamId;
            $this->game->save();
        }
    }

    #[On('game-state-changed')]
    public function handleGameStateChanged($state, $data = [])
    {
        // Handle team selection events from both manual triggers and buzzer API
        if (in_array($state, ['team-selected', 'buzzer-pressed', 'lightning-round-started']) && isset($data['teamId'])) {
            $this->currentTeam = Team::find($data['teamId']);
            $this->game->current_team_id = $data['teamId'];
            $this->refreshGame();
        }
    }

    #[On('score-updated')]
    public function handleScoreUpdated()
    {
        // Refresh teams to show updated scores
        $this->refreshGame();
    }

    private function refreshGame()
    {
        $this->game->refresh();
        $this->categories = $this->game->categories->sortBy('position');
        $this->teams = $this->game->teams()->get();

        // Refresh current team if it exists
        if ($this->currentTeam) {
            $this->currentTeam = $this->teams->find($this->currentTeam->id);
        }
    }

    public function render()
    {
        return view('livewire.host-control')
            ->layout('layouts.game');
    }

    /**
     * @return void
     */
    public function nextLightningQuestion(): void
    {
        $nextQuestion = $this->game->lightningQuestions
            ->where('is_answered', false)
            ->sortBy('order_position')
            ->first();

        if ($nextQuestion) {
            $nextQuestion->update(['is_current' => true]);
            // Broadcast that we've moved to the next question
            broadcast(new GameStateChanged($this->game->id, 'lightning-next-question'));
        } else {
            // Lightning round complete
            $this->game->update(['status' => 'finished', 'current_team_id' => null]);
            $this->game->refresh();

            // Dispatch to browser for redirect - this will trigger the JavaScript listener
            $this->dispatch('lightning-round-complete');

            // Also broadcast to all clients
            broadcast(new GameStateChanged($this->game->id, 'lightning-round-complete', []));
        }
    }
}
