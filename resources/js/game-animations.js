// Game Animations and Interactive Effects

// Sound Effects Manager (only define if not already defined)
if (!window.playSound) {
    window.playSound = function(soundName) {
        const sounds = {
            'daily-double': '/sounds/daily-double.mp3',
            'times-up': '/sounds/times-up.mp3',
            'right-answer': '/sounds/right-answer.mp3',
            'buzzer': '/sounds/buzzer.mp3'
        };
        
        if (sounds[soundName]) {
            const audio = new Audio(sounds[soundName]);
            audio.volume = 0.5;
            audio.play().catch(e => console.log('Sound play failed:', e));
        }
    };
}


// Correct Answer Effects
window.celebrateCorrectAnswer = function(teamElement) {
    window.playSound('right-answer');
    teamElement.classList.add('animate-bounce');
    
    // Create confetti effect
    const confettiColors = ['#FFD700', '#FFA500', '#FF69B4', '#00CED1', '#32CD32'];
    const confettiCount = 30;
    
    for (let i = 0; i < confettiCount; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.backgroundColor = confettiColors[Math.floor(Math.random() * confettiColors.length)];
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.animationDelay = Math.random() * 3 + 's';
        confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
        document.body.appendChild(confetti);
        
        setTimeout(() => confetti.remove(), 5000);
    }
};

// Daily Double Reveal Animation
window.revealDailyDouble = function(element) {
    element.classList.add('daily-double-reveal');
    window.playSound('daily-double');
    
    // Create spinning star effect
    const stars = ['⭐', '✨', '💫', '🌟'];
    const starCount = 20;
    
    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.className = 'floating-star';
        star.textContent = stars[Math.floor(Math.random() * stars.length)];
        star.style.left = Math.random() * 100 + '%';
        star.style.top = Math.random() * 100 + '%';
        star.style.animationDelay = Math.random() * 2 + 's';
        element.appendChild(star);
        
        setTimeout(() => star.remove(), 3000);
    }
};

// Score Change Animation
window.animateScoreChange = function(element, oldScore, newScore) {
    const duration = 1000;
    const startTime = performance.now();
    const difference = newScore - oldScore;
    
    function updateScore(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Easing function for smooth animation
        const easeOutQuart = 1 - Math.pow(1 - progress, 4);
        const currentScore = Math.round(oldScore + (difference * easeOutQuart));
        
        element.textContent = '$' + currentScore.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(updateScore);
        } else {
            // Flash on completion
            element.classList.add('score-flash');
            setTimeout(() => element.classList.remove('score-flash'), 500);
        }
    }
    
    requestAnimationFrame(updateScore);
};

// Category Hover Effects
window.setupCategoryEffects = function() {
    const categories = document.querySelectorAll('.category-card');
    
    categories.forEach(category => {
        category.addEventListener('mouseenter', function() {
            this.style.transform = 'perspective(1000px) rotateX(-10deg) translateZ(20px)';
        });
        
        category.addEventListener('mouseleave', function() {
            this.style.transform = 'perspective(1000px) rotateX(0) translateZ(0)';
        });
    });
};

// Initialize effects when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    setupCategoryEffects();
    
    // Setup intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe elements with scroll animation
    document.querySelectorAll('.scroll-animate').forEach(el => {
        observer.observe(el);
    });
});

// Make functions globally available
window.celebrateCorrectAnswer = celebrateCorrectAnswer;
window.revealDailyDouble = revealDailyDouble;
window.animateScoreChange = animateScoreChange;