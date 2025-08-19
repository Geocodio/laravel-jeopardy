<?php

use App\Livewire\GameBoard;
use App\Livewire\LightningRound;
use App\Livewire\VolunteerPicker;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

// Game routes
Route::get('/pick', VolunteerPicker::class)->name('volunteer.pick');

Route::get('/game/new', function () {
    $gameService = app(\App\Services\GameService::class);
    $game = $gameService->createGame();
    $gameService->setupTeams($game);
    $gameService->generateBoard($game->id);

    return redirect()->route('game.board', ['gameId' => $game->id]);
})->name('game.new');

Route::get('/game/{gameId}', GameBoard::class)->name('game.board');
Route::get('/game/{gameId}/host', \App\Livewire\HostControl::class)->name('game.host');
Route::get('/game/{gameId}/lightning', LightningRound::class)->name('game.lightning');
