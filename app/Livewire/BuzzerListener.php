<?php

namespace App\Livewire;

use App\Models\Game;
use App\Services\BuzzerService;
use Livewire\Component;
use Livewire\Attributes\On;

class BuzzerListener extends Component
{
    public bool $isListening = false;
    public array $lockedOut = [];
    public ?Game $game = null;

    protected $buzzerService;

    public function boot(BuzzerService $buzzerService)
    {
        $this->buzzerService = $buzzerService;
    }

    public function mount($gameId = null)
    {
        if ($gameId) {
            $this->game = Game::find($gameId);
        }
    }

    #[On('clue-selected')]
    #[On('lightning-question-shown')]
    public function enableBuzzers()
    {
        // Only enable buzzers if there's no controlling team (or for lightning round)
        if ($this->game && $this->game->current_team_id) {
            $this->isListening = false;
        } else {
            $this->isListening = true;
        }
        $this->lockedOut = [];
        $this->buzzerService->resetAllBuzzers();
    }

    #[On('open-buzzers')]
    public function openBuzzers()
    {
        // Called when host opens buzzers for all teams
        $this->isListening = true;
        $this->lockedOut = [];
    }

    #[On('clue-answered')]
    public function disableBuzzers()
    {
        $this->isListening = false;
    }

    #[On('reset-buzzers')]
    public function resetBuzzers()
    {
        $this->lockedOut = [];
        $this->buzzerService->resetAllBuzzers();
        $this->isListening = true;
    }

    #[On('buzzer-webhook-received')]
    public function processBuzz($teamId, $timestamp)
    {
        if (!$this->isListening) {
            return;
        }

        if (in_array($teamId, $this->lockedOut)) {
            return;
        }

        try {
            $buzzerEvent = $this->buzzerService->registerBuzz(
                $teamId,
                $this->game->teams->find($teamId)->buzzer_pin,
                $timestamp
            );

            if ($buzzerEvent->is_first) {
                $this->dispatch('play-sound', sound: 'buzzer');
                
                if ($this->game->status === 'main_game') {
                    $this->dispatch('buzzer-pressed', teamId: $teamId);
                } else {
                    $this->dispatch('lightning-buzzer-pressed', teamId: $teamId);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't crash
            logger()->error('Buzzer processing error', [
                'team_id' => $teamId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function testBuzzer($pin)
    {
        $result = $this->buzzerService->testBuzzer($pin);
        
        if ($result['success']) {
            $this->dispatch('buzzer-test-success', result: $result);
        } else {
            $this->dispatch('buzzer-test-failed', result: $result);
        }
    }

    public function render()
    {
        return view('livewire.buzzer-listener');
    }
}
