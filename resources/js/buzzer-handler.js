// Buzzer Handler for Laravel Jeopardy

class BuzzerHandler {
    constructor() {
        this.isListening = false;
        this.lockedOutTeams = new Set();
        this.lastBuzz = {};
        this.debounceTime = 100; // milliseconds
    }

    init() {
        // Listen for Livewire events
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Enable buzzers when clue is selected
        Livewire.on('clue-selected', () => {
            this.enableBuzzers();
        });

        // Disable buzzers when clue is answered
        Livewire.on('clue-answered', () => {
            this.disableBuzzers();
        });

        // Reset buzzers
        Livewire.on('reset-buzzers', () => {
            this.resetBuzzers();
        });


        // Handle buzzer lockout
        Livewire.on('lockout-team', ({teamId, duration}) => {
            this.lockoutTeam(teamId, duration);
        });
    }

    enableBuzzers() {
        this.isListening = true;
        console.log('Buzzers enabled');
    }

    disableBuzzers() {
        this.isListening = false;
        console.log('Buzzers disabled');
    }

    resetBuzzers() {
        this.lockedOutTeams.clear();
        this.lastBuzz = {};
        this.isListening = true;
        console.log('Buzzers reset');
    }

    lockoutTeam(teamId, duration = 5000) {
        this.lockedOutTeams.add(teamId);
        setTimeout(() => {
            this.lockedOutTeams.delete(teamId);
            console.log(`Team ${teamId} lockout expired`);
        }, duration);
    }

    processBuzz(teamId) {
        if (!this.isListening) {
            console.log(`Buzz rejected - not listening (Team ${teamId})`);
            return false;
        }

        if (this.lockedOutTeams.has(teamId)) {
            console.log(`Buzz rejected - team locked out (Team ${teamId})`);
            return false;
        }

        // Debounce check
        const now = Date.now();
        if (this.lastBuzz[teamId] && (now - this.lastBuzz[teamId]) < this.debounceTime) {
            console.log(`Buzz rejected - debounced (Team ${teamId})`);
            return false;
        }

        this.lastBuzz[teamId] = now;

        // Emit buzzer event to Livewire
        Livewire.dispatch('buzzer-webhook-received', {
            teamId: teamId,
        });

        console.log(`Buzz accepted (Team ${teamId})`);
        return true;
    }

    // Test buzzer connection
    testBuzzer(pin) {
        fetch('/api/buzzer/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({pin: pin})
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(`Buzzer test successful: ${data.team_name}`);
                    window.playSound('buzzer');
                } else {
                    console.error(`Buzzer test failed: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Buzzer test error:', error);
            });
    }
}

// Initialize buzzer handler
const buzzerHandler = new BuzzerHandler();
document.addEventListener('DOMContentLoaded', () => {
    buzzerHandler.init();
});

// Export for global access
window.buzzerHandler = buzzerHandler;
