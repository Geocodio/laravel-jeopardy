// Buzzer Handler for Laravel Jeopardy

class BuzzerHandler {
    constructor() {
        this.isListening = false;
        this.lockedOutTeams = new Set();
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
}

// Initialize buzzer handler
const buzzerHandler = new BuzzerHandler();
document.addEventListener('DOMContentLoaded', () => {
    buzzerHandler.init();
});

// Export for global access
window.buzzerHandler = buzzerHandler;
