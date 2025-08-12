<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\GameBoard;
use App\Livewire\LightningRound;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

// Game routes
Route::get('/game/new', function () {
    $gameService = app(\App\Services\GameService::class);
    $game = $gameService->createGame();
    $gameService->setupTeams($game);
    $gameService->generateBoard($game->id);
    
    return redirect()->route('game.board', ['gameId' => $game->id]);
})->name('game.new');

Route::get('/game/{gameId}', GameBoard::class)->name('game.board');
Route::get('/game/{gameId}/lightning', LightningRound::class)->name('game.lightning');

require __DIR__.'/auth.php';
