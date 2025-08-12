// Laravel Jeopardy Game Application

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Laravel Echo
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Import game modules
import './buzzer-handler.js';
import './game-timer.js';
import './game-animations.js';

// Alpine.js is included via Livewire

console.log('Laravel Jeopardy Game initialized');

// Join game channel for real-time updates
if (window.gameId) {
    window.Echo.join(`game.${window.gameId}`)
        .here((users) => {
            console.log('Users in game:', users);
        })
        .joining((user) => {
            console.log('User joined:', user);
        })
        .leaving((user) => {
            console.log('User left:', user);
        })
        .listen('BuzzerPressed', (e) => {
            console.log('Buzzer pressed:', e);
            Livewire.dispatch('buzzer-webhook-received', {
                teamId: e.teamId,
                timestamp: e.timestamp
            });
        })
        .listen('ScoreUpdated', (e) => {
            console.log('Score updated:', e);
            Livewire.dispatch('score-updated');
        });
}