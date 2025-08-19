<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\LightningQuestion;
use App\Models\Team;
use App\Services\ScoringService;
use Livewire\Attributes\On;
use Livewire\Component;

class LightningRound extends Component
{
    public ?Game $game = null;

    public ?LightningQuestion $currentQuestion = null;

    public int $questionsRemaining = 0;

    public array $buzzerOrder = [];

    public ?Team $currentAnsweringTeam = null;

    public function mount($gameId)
    {
        $this->game = Game::with('lightningQuestions', 'teams')->findOrFail($gameId);

        // Clear any lingering team selection from the main game
        if ($this->game->current_team_id) {
            $this->game->update(['current_team_id' => null]);
            $this->game->refresh();

            // Broadcast to clear team selection on all clients
            broadcast(new \App\Events\GameStateChanged($this->game->id, 'team-deselected'));
        }

        $this->loadCurrentQuestion();
    }

    private function loadCurrentQuestion()
    {
        $this->currentQuestion = $this->game->lightningQuestions
            ->where('is_current', true)
            ->first();

        if (! $this->currentQuestion) {
            $this->currentQuestion = $this->game->lightningQuestions
                ->where('is_answered', false)
                ->sortBy('order_position')
                ->first();

            if ($this->currentQuestion) {
                $this->currentQuestion->update(['is_current' => true]);
            }
        }

        $this->questionsRemaining = $this->game->lightningQuestions
            ->where('is_answered', false)
            ->count();

        $this->buzzerOrder = [];
        $this->currentAnsweringTeam = null;
    }

    #[On('buzzer-pressed')]
    public function handleBuzzer($teamId)
    {
        if ($this->currentAnsweringTeam) {
            return;
        }

        if (! in_array($teamId, $this->buzzerOrder)) {
            $this->buzzerOrder[] = $teamId;
        }

        if (count($this->buzzerOrder) === 1) {
            $this->currentAnsweringTeam = Team::find($teamId);

            // Update game state to set active team
            $this->game->update(['current_team_id' => $teamId]);
            $this->game->refresh();

            $this->dispatch('buzzer-accepted', teamId: $teamId);

            // Broadcast to all clients that a team has buzzed in
            broadcast(new \App\Events\GameStateChanged($this->game->id, 'team-selected', ['teamId' => $teamId]));
        }
    }

    #[On('game-state-changed')]
    public function handleGameStateChanged($state, $data = [])
    {
        if ($state === 'buzzer-pressed' && isset($data['teamId'])) {
            $this->handleBuzzer($data['teamId']);
        } elseif ($state === 'lightning-next-question') {
            // Refresh when host moves to next question
            $this->handleRefresh();
        }
    }

    #[On('lightning-mark-correct')]
    public function handleMarkCorrect()
    {
        $this->markLightningCorrect();
    }

    #[On('lightning-mark-incorrect')]
    public function handleMarkIncorrect()
    {
        $this->markLightningIncorrect();
    }

    #[On('lightning-skip-question')]
    public function handleSkipQuestion()
    {
        $this->skipQuestion();
    }

    #[On('lightning-next-question')]
    public function handleNextQuestion()
    {
        $this->nextQuestion();
    }

    #[On('lightning-refresh')]
    public function handleRefresh()
    {
        // Reload the game with fresh lightning questions
        $this->game = Game::with('lightningQuestions', 'teams')->findOrFail($this->game->id);
        $this->loadCurrentQuestion();
    }

    public function markLightningCorrect()
    {
        if (! $this->currentAnsweringTeam || ! $this->currentQuestion) {
            return;
        }

        $scoringService = app(ScoringService::class);
        $scoringService->recordLightningAnswer(
            $this->currentQuestion->id,
            $this->currentAnsweringTeam->id,
            true
        );

        $this->dispatch('play-sound', sound: 'correct');
        $this->nextQuestion();
    }

    public function markLightningIncorrect()
    {
        if (! $this->currentAnsweringTeam || ! $this->currentQuestion) {
            return;
        }

        // Remove team from current question and try next in buzzer order
        array_shift($this->buzzerOrder);

        if (! empty($this->buzzerOrder)) {
            $nextTeamId = $this->buzzerOrder[0];
            $this->currentAnsweringTeam = Team::find($nextTeamId);

            // Update game state to set next active team
            $this->game->update(['current_team_id' => $nextTeamId]);
            $this->game->refresh();

            $this->dispatch('buzzer-accepted', teamId: $nextTeamId);
            broadcast(new \App\Events\GameStateChanged($this->game->id, 'team-selected', ['teamId' => $nextTeamId]));
        } else {
            // No more teams buzzed, clear active team and move to next question
            $this->currentAnsweringTeam = null;
            $this->game->update(['current_team_id' => null]);
            $this->game->refresh();
            $this->nextQuestion();
        }

        $this->dispatch('play-sound', sound: 'incorrect');
    }

    public function nextQuestion()
    {
        if ($this->currentQuestion) {
            $this->currentQuestion->update([
                'is_current' => false,
                'is_answered' => true,
            ]);
        }

        // Clear active team when moving to next question
        $this->game->update(['current_team_id' => null]);

        $nextQuestion = $this->game->lightningQuestions
            ->where('is_answered', false)
            ->sortBy('order_position')
            ->first();

        if ($nextQuestion) {
            $nextQuestion->update(['is_current' => true]);
            $this->loadCurrentQuestion();
            $this->dispatch('reset-buzzers');
        } else {
            // Lightning round complete
            $this->game->update(['status' => 'finished', 'current_team_id' => null]);
            $this->game->refresh();

            // Dispatch to browser for redirect - this will trigger the JavaScript listener
            $this->dispatch('lightning-round-complete');

            // Also broadcast to all clients
            broadcast(new \App\Events\GameStateChanged($this->game->id, 'lightning-round-complete', []));
        }
    }

    public function skipQuestion()
    {
        $this->nextQuestion();
    }

    public function render()
    {
        return view('livewire.lightning-round')
            ->layout('layouts.game');
    }
}
