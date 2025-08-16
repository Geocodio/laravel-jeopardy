// Buzzer sound handler for Jeopardy game
export function initBuzzerListener(gameId) {
    if (!window.Echo || !gameId) {
        console.warn('Echo or gameId not available for buzzer listener');
        return;
    }

    // Listen on the game channel
    window.Echo.join(`game.${gameId}`)
        .listen('.buzzer.pressed', (e) => {
            console.log('Buzzer pressed event received:', e);
            playBuzzerSound(e.teamName);
        });

    // Also listen on the general buzzers channel
    window.Echo.channel('buzzers')
        .listen('.buzzer.pressed', (e) => {
            console.log('Buzzer pressed event received on buzzers channel:', e);
            playBuzzerSound(e.teamName);
        });
}

function playBuzzerSound(teamName) {
    // Map team name to sound file
    let soundFile = null;
    if (teamName && teamName.includes('Blade')) {
        soundFile = 'buzzer-blade';
    } else if (teamName && teamName.includes('Artisan')) {
        soundFile = 'buzzer-artisan';
    } else if (teamName && teamName.includes('Eloquent')) {
        soundFile = 'buzzer-eloquent';
    } else if (teamName && teamName.includes('Facade')) {
        soundFile = 'buzzer-facade';
    } else if (teamName && teamName.includes('Illuminate')) {
        soundFile = 'buzzer-illuminate';
    }

    // Play the sound
    if (soundFile) {
        const audio = document.getElementById(soundFile);
        if (audio) {
            audio.currentTime = 0; // Reset to start
            audio.play().catch(error => {
                console.error('Error playing buzzer sound:', error);
            });
        }
    }
}

// Auto-initialize if gameId is available
document.addEventListener('DOMContentLoaded', () => {
    if (window.gameId) {
        initBuzzerListener(window.gameId);
    }
});