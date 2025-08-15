<?php

use App\Models\User;
use App\Models\Game;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Game channel for real-time updates
Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    // Since running locally, allow all connections
    return true;
});

// Host control channel for iPad interface
Broadcast::channel('host.{gameId}', function ($user, $gameId) {
    // Since running locally, allow all connections
    return true;
});