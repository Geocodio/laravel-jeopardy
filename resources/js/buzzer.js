// Buzzer sound handler for Jeopardy game
export function initBuzzerListener(gameId) {
    if (!window.Echo || !gameId) {
        console.warn('Echo or gameId not available for buzzer listener');
        return;
    }

    // Listen on the public game channel
    window.Echo.channel(`game.${gameId}`)
        .listen('.buzzer.pressed', (e) => {
            console.log('Buzzer pressed event received:', e);
            playBuzzerSound(e.teamName);
        })
        .listen('ScoreUpdated', (e) => {
            let soundFile = e.correct ? 'answer-correct' : 'answer-incorrect';

            const audio = document.getElementById(soundFile);
            if (audio) {
                audio.currentTime = 0; // Reset to start
                audio.play().catch(error => {
                    console.error('Error playing buzzer sound:', error);

                    // Show user-friendly error message
                    if (error.name === 'NotAllowedError') {
                        showAudioPermissionMessage();
                    }
                });
            }
        })

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

                // Show user-friendly error message
                if (error.name === 'NotAllowedError') {
                    showAudioPermissionMessage();
                }
            });
        }
    }
}

function showAudioPermissionMessage() {
    // Remove any existing message
    const existingMessage = document.getElementById('audio-permission-message');
    if (existingMessage) {
        existingMessage.remove();
    }

    // Create message element
    const message = document.createElement('div');
    message.id = 'audio-permission-message';
    message.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        z-index: 10000;
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        animation: slideDown 0.3s ease-out;
        max-width: 90%;
        text-align: center;
    `;

    message.innerHTML = `
        <div style="display: flex; align-items: center; gap: 12px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 18V5l12-2v13"></path>
                <circle cx="6" cy="18" r="3"></circle>
                <circle cx="18" cy="16" r="3"></circle>
            </svg>
            <span>🔊 Click anywhere on the page to enable buzzer sounds</span>
        </div>
    `;

    // Add CSS animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

    // Add to page
    document.body.appendChild(message);

    // Initialize audio on click
    const initAudio = () => {
        // Pre-play all audio elements with volume 0 to initialize them
        const audioElements = document.querySelectorAll('audio');
        audioElements.forEach(audio => {
            audio.volume = 0;
            audio.play().then(() => {
                audio.pause();
                audio.currentTime = 0;
                audio.volume = 1;
            }).catch(e => console.log('Audio init skipped:', audio.id));
        });

        // Remove message with fade out
        message.style.animation = 'slideDown 0.3s ease-out reverse';
        setTimeout(() => message.remove(), 300);

        // Remove click listener
        document.removeEventListener('click', initAudio);
        console.log('Audio system initialized');
    };

    // Add click listener to entire document
    document.addEventListener('click', initAudio);

    // Auto-hide after 10 seconds
    setTimeout(() => {
        if (document.getElementById('audio-permission-message')) {
            message.style.animation = 'slideDown 0.3s ease-out reverse';
            setTimeout(() => message.remove(), 300);
        }
    }, 10000);
}

// Auto-initialize if gameId is available
document.addEventListener('DOMContentLoaded', () => {
    if (window.gameId) {
        initBuzzerListener(window.gameId);
    }
});
