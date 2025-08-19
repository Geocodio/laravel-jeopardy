<div
    x-data="{
        mounted: false,
        lightning: false,
        pulseEffect: false,
        createElectricParticles() {
            // Electric particle effect
            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            // Continuous electric sparkles
            setInterval(() => {
                // Electric sparks from random positions
                window.confetti({
                    particleCount: 4,
                    startVelocity: 20,
                    spread: 160,
                    gravity: 0.3,
                    ticks: 50,
                    shapes: ['circle'],
                    scalar: 0.6,
                    drift: randomInRange(-0.5, 0.5),
                    origin: {
                        x: Math.random(),
                        y: randomInRange(-0.1, 0.4)
                    },
                    colors: ['#fbbf24', '#fde68a', '#c084fc', '#60a5fa'],
                    disableForReducedMotion: true
                });
            }, 200);

            // Electric arc bursts
            setInterval(() => {
                const x = Math.random();
                window.confetti({
                    particleCount: 20,
                    startVelocity: 35,
                    spread: 70,
                    gravity: 0.6,
                    ticks: 60,
                    shapes: ['circle'],
                    scalar: 0.5,
                    origin: { x: x, y: -0.1 },
                    colors: ['#fbbf24', '#e9d5ff', '#60a5fa'],
                    disableForReducedMotion: true
                });
            }, 2000);

            // Side electric streams
            setInterval(() => {
                // Left side
                window.confetti({
                    particleCount: 10,
                    startVelocity: 25,
                    angle: 45,
                    spread: 30,
                    gravity: 0.4,
                    ticks: 45,
                    shapes: ['circle'],
                    scalar: 0.5,
                    origin: { x: 0, y: randomInRange(0.3, 0.7) },
                    colors: ['#a78bfa', '#fbbf24'],
                    disableForReducedMotion: true
                });
                // Right side
                window.confetti({
                    particleCount: 10,
                    startVelocity: 25,
                    angle: 135,
                    spread: 30,
                    gravity: 0.4,
                    ticks: 45,
                    shapes: ['circle'],
                    scalar: 0.5,
                    origin: { x: 1, y: randomInRange(0.3, 0.7) },
                    colors: ['#a78bfa', '#fbbf24'],
                    disableForReducedMotion: true
                });
            }, 3000);
        }
    }"
    x-init="
        mounted = true;
        // Start electric particles
        createElectricParticles();

        // Create lightning effect
        setInterval(() => {
            lightning = true;
            setTimeout(() => lightning = false, 200);
        }, 3000);

        // Pulse effect
        setInterval(() => {
            pulseEffect = !pulseEffect;
        }, 1000);
    "
    class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-black relative overflow-hidden">

    <!-- Lightning Background Effect -->
    <div class="absolute inset-0 pointer-events-none">
        <!-- Canvas confetti replaces static particles -->
    </div>

    @if($game && $currentQuestion)
        <script>
            window.gameId = {{ $game->id }};
        </script>

        <div class="relative container mx-auto px-4 py-8">
            <!-- Title with Electric Effect -->
            <div
                x-show="mounted"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 scale-150"
                x-transition:enter-end="opacity-100 scale-100"
                class="text-center mb-8">
                <h1 class="text-6xl md:text-7xl font-black relative inline-block">
                    <span
                        class="absolute inset-0 blur-xl bg-gradient-to-r from-purple-400 via-pink-400 to-yellow-400 opacity-75 animate-pulse"></span>
                    <span
                        class="relative bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-400 to-yellow-400">
                        ⚡ LIGHTNING ROUND ⚡
                    </span>
                </h1>
                <div class="mt-4 text-xl text-purple-300 tracking-[0.5em] uppercase animate-pulse">
                    Rapid Fire Mode
                </div>
            </div>

            <!-- Team Scoreboard with Glow -->
            <div
                x-show="mounted"
                x-transition:enter="transition ease-out duration-1000 delay-200"
                x-transition:enter-start="opacity-0 translate-y-10"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mb-8">
                <livewire:team-scoreboard :game-id="$game->id"/>
            </div>


            <!-- Current Question with Electric Border -->
            <div
                x-show="mounted"
                x-transition:enter="transition ease-out duration-700 delay-600"
                x-transition:enter-start="opacity-0 scale-95 rotate-3"
                x-transition:enter-end="opacity-100 scale-100 rotate-0"
                class="relative max-w-5xl mx-auto mt-8">

                <!-- Animated Border -->
                <div
                    class="absolute -inset-1 bg-gradient-to-r from-purple-600 via-pink-600 to-yellow-600 rounded-2xl blur opacity-75 animate-pulse"></div>

                <!-- Question Card -->
                <div
                    class="relative backdrop-blur-xl bg-gradient-to-br from-purple-900/90 to-indigo-900/90 rounded-2xl p-12 border-2 border-white/20 shadow-2xl">
                    <div class="text-center">
                        <p class="text-3xl md:text-5xl font-bold text-white leading-tight animate-pulse">
                            {{ $currentQuestion->question_text }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buzzer Listener -->
        <livewire:buzzer-listener :game-id="$game->id"/>

        @include('partials.buzzer-audio')
    @else
        <div class="flex items-center justify-center h-screen">
            <div
                x-show="mounted"
                x-transition:enter="transition ease-out duration-700"
                x-transition:enter-start="opacity-0 scale-75"
                x-transition:enter-end="opacity-100 scale-100"
                class="text-center backdrop-blur-lg bg-black/50 rounded-2xl p-12 border border-purple-500/30">
                <h1 class="text-4xl font-bold mb-4 text-purple-400">Lightning Round Not Ready</h1>
                <p class="text-xl text-purple-300">No questions available or game not in lightning round.</p>
            </div>
        </div>
    @endif
</div>
