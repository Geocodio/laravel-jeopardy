<div x-data="buzzerHandler()">
    <!-- Buzzer status indicator (hidden, just for listening) -->
    <div class="hidden">
        Buzzer Listener Active: {{ $isListening ? 'Yes' : 'No' }}
    </div>

    <!-- Audio elements for buzzer sounds -->
    <audio id="buzzer-blade" preload="auto">
        <source src="/sounds/buzzer/blade.wav" type="audio/wav">
    </audio>
    <audio id="buzzer-artisan" preload="auto">
        <source src="/sounds/buzzer/artisan.wav" type="audio/wav">
    </audio>
    <audio id="buzzer-eloquent" preload="auto">
        <source src="/sounds/buzzer/eloquent.wav" type="audio/wav">
    </audio>
    <audio id="buzzer-facade" preload="auto">
        <source src="/sounds/buzzer/facade.wav" type="audio/wav">
    </audio>
    <audio id="buzzer-illuminate" preload="auto">
        <source src="/sounds/buzzer/illuminate.wav" type="audio/wav">
    </audio>

    <script>
        function buzzerHandler() {
            return {
                init() {
                    // Listen for buzzer.pressed event from Laravel Echo
                    if (window.Echo && window.gameId) {
                        // Listen on the public game channel
                        window.Echo.channel(`game.${window.gameId}`)
                            .listen('.buzzer.pressed', (e) => {
                                console.log('Buzzer pressed event received:', e);
                                this.playBuzzerSound(e.teamName);
                            });

                        // Also listen on the general buzzers channel
                        window.Echo.channel('buzzers')
                            .listen('.buzzer.pressed', (e) => {
                                console.log('Buzzer pressed event received on buzzers channel:', e);
                                this.playBuzzerSound(e.teamName);
                            });
                    }
                },

                playBuzzerSound(teamName) {
                    // Map team name to sound file
                    let soundFile = null;
                    if (teamName.includes('Blade')) {
                        soundFile = 'buzzer-blade';
                    } else if (teamName.includes('Artisan')) {
                        soundFile = 'buzzer-artisan';
                    } else if (teamName.includes('Eloquent')) {
                        soundFile = 'buzzer-eloquent';
                    } else if (teamName.includes('Facade')) {
                        soundFile = 'buzzer-facade';
                    } else if (teamName.includes('Illuminate')) {
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
            }
        }
    </script>
</div>
