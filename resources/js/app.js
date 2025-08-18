import confetti from 'canvas-confetti';
// Import game modules
import './echo.js';
import './buzzer.js';
import './buzzer-handler.js';
import './game-animations.js';

// Make confetti available globally for Alpine.js
window.confetti = confetti;

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

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */
