import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import confetti from 'canvas-confetti';

// Make confetti available globally for Alpine.js
window.confetti = confetti;

// Configure Laravel Echo
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});

// Import game modules
import './buzzer.js';
import './buzzer-handler.js';
import './game-timer.js';
import './game-animations.js';

// Subscribe to private game channel for real-time updates
if (window.gameId) {
    window.Echo.private(`game.${window.gameId}`)
        .listen('BuzzerPressed', (e) => {
            console.log('Buzzer pressed:', e);
            Livewire.dispatch('buzzer-webhook-received', {
                teamId: e.teamId,
                timestamp: e.timestamp
            });
        })
        .listen('ScoreUpdated', (e) => {
            console.log('Score updated:', e);
            // Dispatch to all Livewire components listening for this event
            Livewire.dispatch('score-updated', e);
        })
        .listen('ClueRevealed', (e) => {
            console.log('Clue revealed:', e);
            Livewire.dispatch('clue-revealed', {
                clueId: e.clueId
            });
        })
        .listen('GameStateChanged', (e) => {
            console.log('Game state changed:', e);
            Livewire.dispatch('game-state-changed', e);
        })
        .listen('DailyDoubleTriggered', (e) => {
            console.log('Daily double triggered:', e);
            Livewire.dispatch('daily-double-triggered', e);
        });
}
