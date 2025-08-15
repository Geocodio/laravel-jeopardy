<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BuzzerPressed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $teamId;
    public string $timestamp;
    public ?int $gameId;

    /**
     * Create a new event instance.
     */
    public function __construct(int $teamId, string $timestamp, ?int $gameId = null)
    {
        $this->teamId = $teamId;
        $this->timestamp = $timestamp;
        $this->gameId = $gameId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        if ($this->gameId) {
            $channels[] = new PresenceChannel('game.' . $this->gameId);
        }

        $channels[] = new Channel('buzzers');

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'buzzer.pressed';
    }
}
