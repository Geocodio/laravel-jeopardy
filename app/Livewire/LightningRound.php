<?php

namespace App\Livewire;

use App\Events\GameStateChanged;
use App\Models\Game;
use App\Models\LightningQuestion;
use App\Models\Team;
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
            broadcast(new GameStateChanged($this->game->id, 'team-deselected'));
        }

        $this->loadCurrentQuestion();
    }

    private function loadCurrentQuestion()
    {
        $this->currentQuestion = $this->game->lightningQuestions
            ->where('is_current', true)
            ->first();

        if (!$this->currentQuestion) {
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

        if (!in_array($teamId, $this->buzzerOrder)) {
            $this->buzzerOrder[] = $teamId;
        }

        if (count($this->buzzerOrder) === 1) {
            $this->currentAnsweringTeam = Team::find($teamId);

            // Update game state to set active team
            $this->game->update(['current_team_id' => $teamId]);
            $this->game->refresh();

            $this->dispatch('buzzer-accepted', teamId: $teamId);

            // Broadcast to all clients that a team has buzzed in
            broadcast(new GameStateChanged($this->game->id, 'team-selected', ['teamId' => $teamId]));
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

    #[On('lightning-refresh')]
    public function handleRefresh()
    {
        // Reload the game with fresh lightning questions
        $this->game = Game::with('lightningQuestions', 'teams')->findOrFail($this->game->id);
        $this->loadCurrentQuestion();
    }

    public function render()
    {
        return view('livewire.lightning-round')
            ->layout('layouts.game');
    }
}
