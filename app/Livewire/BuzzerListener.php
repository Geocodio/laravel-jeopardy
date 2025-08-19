<?php

namespace App\Livewire;

use App\Models\Game;
use App\Services\BuzzerService;
use Livewire\Attributes\On;
use Livewire\Component;

class BuzzerListener extends Component
{
    public bool $isListening = false;

    public array $lockedOut = [];

    public ?Game $game = null;

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
        // Refresh game state to get latest current_team_id
        if ($this->game) {
            $this->game->refresh();
        }

        // Only enable buzzers if there's no controlling team (or for lightning round)
        if ($this->game && $this->game->current_team_id) {
            $this->isListening = false;
        } else {
            $this->isListening = true;
        }
        $this->lockedOut = [];
        app(BuzzerService::class)->resetAllBuzzers();
    }

    #[On('open-buzzers')]
    #[On('buzzers-opened')]
    public function openBuzzers()
    {
        // Called when host opens buzzers for all teams
        $this->isListening = true;
        $this->lockedOut = [];
        app(BuzzerService::class)->resetAllBuzzers();
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
        app(BuzzerService::class)->resetAllBuzzers();
        $this->isListening = true;
    }

    #[On('buzzer-webhook-received')]
    public function processBuzz($teamId, $timestamp)
    {
        if (! $this->isListening) {
            return;
        }

        if (in_array($teamId, $this->lockedOut)) {
            return;
        }

        try {
            $buzzerService = app(BuzzerService::class);
            $buzzerEvent = $buzzerService->registerBuzz(
                $teamId,
                $this->game->teams->find($teamId)->buzzer_pin,
                $timestamp
            );

            if ($buzzerEvent->is_first) {
                $this->dispatch('play-sound', sound: 'buzzer');

                // Always dispatch buzzer-pressed event regardless of game mode
                $this->dispatch('buzzer-pressed', teamId: $teamId);
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
        $buzzerService = app(BuzzerService::class);
        $result = $buzzerService->testBuzzer($pin);

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
