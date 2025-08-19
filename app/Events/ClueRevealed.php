<?php

namespace App\Events;

use App\Models\Clue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClueRevealed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gameId;

    public $clueId;

    public $clue;

    /**
     * Create a new event instance.
     */
    public function __construct($gameId, $clueId)
    {
        $this->gameId = $gameId;
        $this->clueId = $clueId;
        $this->clue = Clue::with('category')->find($clueId);
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
            'clueId' => $this->clueId,
            'clue' => $this->clue,
            'gameId' => $this->gameId,
        ];
    }
}
