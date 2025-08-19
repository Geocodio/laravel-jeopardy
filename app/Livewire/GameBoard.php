<?php

namespace App\Livewire;

use App\Models\Clue;
use App\Models\Game;
use App\Services\GameService;
use Livewire\Attributes\On;
use Livewire\Component;

class GameBoard extends Component
{
    public ?Game $game = null;

    public $categories;

    public ?Clue $selectedClue = null;

    public bool $showClueModal = false;

    protected $gameService;

    public function boot(GameService $gameService)
    {
        $this->gameService = $gameService;
    }

    public function mount($gameId = null)
    {
        if ($gameId) {
            $this->loadGame($gameId);
        }
    }

    public function loadGame($gameId)
    {
        $this->game = Game::with([
            'categories.clues',
            'teams',
        ])->findOrFail($gameId);

        // Automatically transition from setup to main_game when board is loaded
        if ($this->game->status === 'setup') {
            // Set Team Illuminate as the first active team
            $teamIlluminate = $this->game->teams->where('name', 'Team Illuminate')->first();
            $this->game->update([
                'status' => 'main_game',
                'current_team_id' => $teamIlluminate ? $teamIlluminate->id : null,
            ]);
            $this->game->refresh();

            // Broadcast that the game has started
            broadcast(new \App\Events\GameStateChanged($this->game->id, 'game-started'));

            // Broadcast team selection
            if ($teamIlluminate) {
                broadcast(new \App\Events\GameStateChanged($this->game->id, 'team-selected', ['teamId' => $teamIlluminate->id]));
            }
        }

        $this->categories = $this->game->categories->sortBy('position');

        // If there's a current clue (e.g., page was refreshed), restore it
        if ($this->game->current_clue_id) {
            $this->selectedClue = Clue::find($this->game->current_clue_id);
            if ($this->selectedClue) {
                $this->showClueModal = true;
                $this->dispatch('clue-selected', clueId: $this->selectedClue->id);
            }
        }
    }

    public function selectClue($clueId)
    {
        $clue = Clue::findOrFail($clueId);

        if ($clue->is_answered) {
            return;
        }

        $this->selectedClue = $clue;
        $this->showClueModal = true;

        $clue->update(['is_revealed' => true]);
        $this->game->update(['current_clue_id' => $clueId]);
        $this->game->refresh();

        $this->dispatch('clue-selected', clueId: $clueId);
    }

    #[On('clue-answered')]
    public function handleClueAnswered($clueId)
    {
        $this->returnToBoard();
        $this->refreshGame();
    }

    public function returnToBoard()
    {
        $this->showClueModal = false;
        $this->selectedClue = null;
        $this->game->update(['current_clue_id' => null]);
        $this->game->refresh();
    }

    public function startLightningRound()
    {
        if ($this->game->status !== 'main_game') {
            return;
        }

        $this->gameService->transitionToLightningRound($this->game->id);
        $this->game->refresh();
        $this->dispatch('lightning-round-started');

        // Redirect to lightning round page
        return redirect()->route('game.lightning', ['gameId' => $this->game->id]);
    }

    public function endGame()
    {
        $this->game->update(['status' => 'finished']);
        $this->game->refresh();
        $finalScores = $this->gameService->calculateFinalScores($this->game->id);
        $this->dispatch('game-ended', scores: $finalScores);
    }

    private function refreshGame()
    {
        $this->game->refresh();
        $this->categories = $this->game->categories->sortBy('position');
    }

    // Listen for broadcast events from HostControl
    #[On('clue-revealed')]
    public function handleClueRevealed($clueId)
    {
        $this->selectClue($clueId);
    }

    #[On('game-state-changed')]
    public function handleGameStateChanged($state, $data = [])
    {
        if ($state === 'clue-closed') {
            $this->returnToBoard();
        } elseif ($state === 'clue-skipped') {
            $this->refreshGame();
        } elseif ($state === 'team-selected') {
            $this->game->update(['current_team_id' => $data['teamId'] ?? null]);
            $this->refreshGame();
        } elseif ($state === 'answer-judged') {
            // Answer was correct - refresh and close
            $this->refreshGame();
        } elseif ($state === 'answer-incorrect') {
            // Answer was incorrect - just refresh scores, keep clue open
            $this->refreshGame();
            // Don't close the modal - other teams can still buzz in
        } elseif ($state === 'lightning-round-started') {
            // Navigate to lightning round page
            $this->redirect(route('game.lightning', ['gameId' => $this->game->id]));
        }
    }

    #[On('score-updated')]
    public function handleScoreUpdated()
    {
        // Refresh the game when scores are updated
        $this->refreshGame();
    }

    public function render()
    {
        return view('livewire.game-board')
            ->layout('layouts.game');
    }
}
