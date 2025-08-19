<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gameId;

    public $teamId;

    public $newScore;

    public $points;

    public $correct;

    /**
     * Create a new event instance.
     */
    public function __construct($gameId, $teamId, $newScore, $points = 0, $correct = null)
    {
        $this->gameId = $gameId;
        $this->teamId = $teamId;
        $this->newScore = $newScore;
        $this->points = $points;
        $this->correct = $correct;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('game.'.$this->gameId),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->gameId,
            'teamId' => $this->teamId,
            'newScore' => $this->newScore,
            'points' => $this->points,
            'correct' => $this->correct,
        ];
    }
}
