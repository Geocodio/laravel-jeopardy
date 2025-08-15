<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Category;
use App\Models\Clue;
use App\Services\GameService;
use Livewire\Component;
use Livewire\Attributes\On;

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

        $this->categories = $this->game->categories->sortBy('position');
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
        $this->dispatch('reset-buzzers');
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
            // Refresh the game board when an answer is judged
            $this->refreshGame();
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
