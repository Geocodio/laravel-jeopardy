// Game Animations and Interactive Effects

// Sound Effects Manager (only define if not already defined)
if (!window.playSound) {
    window.playSound = function (soundName) {
        const sounds = {
            'daily-double': '/sounds/daily-double.mp3',
        };

        if (sounds[soundName]) {
            const audio = new Audio(sounds[soundName]);
            audio.volume = 0.5;
            audio.play().catch(e => console.log('Sound play failed:', e));
        }
    };
}
