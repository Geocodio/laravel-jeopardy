<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Laravel Jeopardy - {{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/css/jeopardy.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gradient-to-b from-blue-900 to-blue-950 text-white overflow-hidden">
    <div id="app" class="h-full">
        {{ $slot }}
    </div>

    @livewireScripts
    @stack('scripts')

    <script>
        // Global sound player
        window.playSound = function(soundName) {
            const audio = new Audio(`/sounds/${soundName}.mp3`);
            audio.play().catch(e => console.log('Sound play failed:', e));
        }

        // Listen for sound events
        Livewire.on('play-sound', ({sound}) => {
            window.playSound(sound);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Only handle shortcuts when not typing in an input
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            switch(e.key.toLowerCase()) {
                case ' ':
                    e.preventDefault();
                    Livewire.dispatch('keyboard-space');
                    break;
                case 'c':
                    e.preventDefault();
                    Livewire.dispatch('keyboard-correct');
                    break;
                case 'x':
                    e.preventDefault();
                    Livewire.dispatch('keyboard-incorrect');
                    break;
                case 'n':
                    e.preventDefault();
                    Livewire.dispatch('keyboard-next');
                    break;
                case 'r':
                    e.preventDefault();
                    Livewire.dispatch('keyboard-reset');
                    break;
                case 'escape':
                    e.preventDefault();
                    Livewire.dispatch('keyboard-escape');
                    break;
                case 'd':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        Livewire.dispatch('keyboard-daily-double');
                    }
                    break;
            }
        });
    </script>
</body>
</html>