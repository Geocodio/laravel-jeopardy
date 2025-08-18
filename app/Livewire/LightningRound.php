<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\LightningQuestion;
use App\Models\Team;
use App\Services\BuzzerService;
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

    protected $scoringService;

    protected $buzzerService;

    public function boot(ScoringService $scoringService, BuzzerService $buzzerService)
    {
        $this->scoringService = $scoringService;
        $this->buzzerService = $buzzerService;
    }

    public function mount($gameId)
    {
        $this->game = Game::with('lightningQuestions', 'teams')->findOrFail($gameId);
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

    #[On('lightning-buzzer-pressed')]
    public function handleLightningBuzzer($teamId)
    {
        if ($this->currentAnsweringTeam) {
            return;
        }

        if (! in_array($teamId, $this->buzzerOrder)) {
            $this->buzzerOrder[] = $teamId;
        }

        if (count($this->buzzerOrder) === 1) {
            $this->currentAnsweringTeam = Team::find($teamId);
            $this->dispatch('buzzer-accepted', teamId: $teamId);
        }
    }

    public function markLightningCorrect()
    {
        if (! $this->currentAnsweringTeam || ! $this->currentQuestion) {
            return;
        }

        $this->scoringService->recordLightningAnswer(
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
            $this->dispatch('buzzer-accepted', teamId: $nextTeamId);
        } else {
            // No more teams buzzed, move to next question
            $this->currentAnsweringTeam = null;
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
            $this->dispatch('lightning-round-complete');
            $this->game->update(['status' => 'finished']);
            $this->game->refresh();
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
