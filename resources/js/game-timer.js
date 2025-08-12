// Game Timer for Laravel Jeopardy

class GameTimer {
    constructor() {
        this.timeRemaining = 30;
        this.isRunning = false;
        this.interval = null;
        this.warningThreshold = 10;
        this.dangerThreshold = 5;
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Start timer when requested
        Livewire.on('start-timer', ({duration = 30}) => {
            this.start(duration);
        });

        // Stop timer
        Livewire.on('stop-timer', () => {
            this.stop();
        });

        // Reset timer
        Livewire.on('reset-timer', () => {
            this.reset();
        });
    }

    start(duration = 30) {
        this.stop(); // Clear any existing timer
        this.timeRemaining = duration;
        this.isRunning = true;

        this.interval = setInterval(() => {
            this.tick();
        }, 1000);

        console.log(`Timer started: ${duration} seconds`);
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
        this.isRunning = false;
        console.log('Timer stopped');
    }

    reset() {
        this.stop();
        this.timeRemaining = 30;
        this.updateDisplay();
        console.log('Timer reset');
    }

    tick() {
        if (!this.isRunning || this.timeRemaining <= 0) {
            return;
        }

        this.timeRemaining--;
        
        // Emit tick event to Livewire
        Livewire.dispatch('timer-tick', {
            timeRemaining: this.timeRemaining
        });

        // Check for warning/danger states
        if (this.timeRemaining === this.warningThreshold) {
            this.onWarning();
        } else if (this.timeRemaining === this.dangerThreshold) {
            this.onDanger();
        } else if (this.timeRemaining === 0) {
            this.onExpired();
        }

        this.updateDisplay();
    }

    onWarning() {
        console.log('Timer warning: 10 seconds remaining');
        // Visual/audio feedback for warning
        const display = document.querySelector('.timer-display');
        if (display) {
            display.classList.add('warning');
        }
    }

    onDanger() {
        console.log('Timer danger: 5 seconds remaining');
        // Visual/audio feedback for danger
        const display = document.querySelector('.timer-display');
        if (display) {
            display.classList.remove('warning');
            display.classList.add('danger');
        }
    }

    onExpired() {
        console.log('Timer expired!');
        this.stop();
        
        // Play time's up sound
        window.playSound('times-up');
        
        // Notify Livewire
        Livewire.dispatch('timer-expired');
    }

    updateDisplay() {
        const display = document.querySelector('.timer-display');
        if (display) {
            display.textContent = this.formatTime(this.timeRemaining);
            
            // Update visual state
            if (this.timeRemaining > this.warningThreshold) {
                display.classList.remove('warning', 'danger');
            }
        }
    }

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    // Manual controls for testing
    addTime(seconds) {
        this.timeRemaining += seconds;
        this.updateDisplay();
    }

    subtractTime(seconds) {
        this.timeRemaining = Math.max(0, this.timeRemaining - seconds);
        this.updateDisplay();
    }
}

// Initialize timer
const gameTimer = new GameTimer();
document.addEventListener('DOMContentLoaded', () => {
    gameTimer.init();
});

// Export for global access
window.gameTimer = gameTimer;