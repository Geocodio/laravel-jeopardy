<div x-data="volunteerPicker()"
     x-init="init()"
     class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 overflow-hidden relative">

    <!-- Animated Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="absolute top-20 left-20 w-96 h-96 bg-purple-500/20 rounded-full filter blur-3xl animate-blob"></div>
        <div class="absolute top-40 right-20 w-96 h-96 bg-pink-500/20 rounded-full filter blur-3xl animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-20 left-1/2 w-96 h-96 bg-indigo-500/20 rounded-full filter blur-3xl animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 h-screen flex flex-col">
        @if($currentTeamIndex >= 0)
        <div class="flex justify-center gap-6 px-8 min-h-[180px] mb-8 pt-8">
            @foreach($selectedVolunteers as $index => $team)
                @if($team['completed'])
                <div class="team-card-small group relative transform transition-all duration-700 hover:scale-105 hover:-translate-y-1"
                     style="animation-delay: {{ $index * 100 }}ms;">
                    <!-- Glow effect on hover -->
                    <div class="absolute -inset-1 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                         style="background: radial-gradient(circle, {{ $team['team']['color_hex'] }}40 0%, transparent 70%);"></div>

                    <!-- Card content -->
                    <div class="relative bg-gradient-to-br from-black/70 to-black/50 backdrop-blur-xl border-2 rounded-2xl p-6 w-80 shadow-2xl"
                         style="border-color: {{ $team['team']['color_hex'] }}; box-shadow: 0 10px 40px {{ $team['team']['color_hex'] }}20, inset 0 0 30px {{ $team['team']['color_hex'] }}10;">

                        <!-- Team header with gradient text -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-black text-2xl tracking-wider bg-gradient-to-r from-white to-white/80 bg-clip-text text-transparent"
                                style="text-shadow: 0 0 20px {{ $team['team']['color_hex'] }};">
                                {{ strtoupper($team['team']['name']) }}
                            </h3>
                            <div class="w-3 h-3 rounded-full animate-pulse"
                                 style="background-color: {{ $team['team']['color_hex'] }}; box-shadow: 0 0 10px {{ $team['team']['color_hex'] }};"></div>
                        </div>

                        <!-- Member list with better styling -->
                        <div class="space-y-2">
                            @foreach($team['members'] as $memberIndex => $member)
                            <button wire:click="rerollMember({{ $index }}, {{ $memberIndex }})"
                                    class="w-full text-left px-3 py-2 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/30 transition-all duration-200 group/member">
                                <div class="flex items-center justify-between">
                                    <span class="text-white/90 font-medium text-base tracking-wide group-hover/member:text-yellow-300 transition-colors truncate pr-2">
                                        {{ $member }}
                                    </span>
                                    <svg class="w-4 h-4 text-white/20 group-hover/member:text-yellow-300 opacity-0 group-hover/member:opacity-100 transition-all duration-200 transform group-hover/member:rotate-180"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </div>
                            </button>
                            @endforeach
                        </div>

                        <!-- Subtle decoration -->
                        <div class="absolute top-0 right-0 w-20 h-20 opacity-10"
                             style="background: radial-gradient(circle, {{ $team['team']['color_hex'] }} 0%, transparent 70%);"></div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
        @endif

        <!-- Main Content Area -->
        <div class="flex-1 flex items-center justify-center px-8">
            @if($currentTeamIndex === -1)
                <!-- Initial State -->
                <div class="text-center">
                    <button wire:click="startSelection"
                            class="cursor-pointer px-16 py-8 bg-yellow-400 text-black text-4xl font-bold rounded-lg hover:bg-yellow-300 transform hover:scale-105 transition-all duration-300 shadow-2xl">
                        START
                    </button>
                </div>
            @elseif(!$selectionComplete)
                <!-- Current Team Selection -->
                <div class="team-card-active w-full max-w-5xl"
                     x-show="true"
                     x-transition:enter="transition ease-out duration-700"
                     x-transition:enter-start="opacity-0 scale-50"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="bg-black/60 backdrop-blur-md rounded-2xl p-12 border-4 transform transition-all duration-700"
                         style="border-color: {{ $selectedVolunteers[$currentTeamIndex]['team']['color_hex'] }}; box-shadow: 0 0 50px {{ $selectedVolunteers[$currentTeamIndex]['team']['color_hex'] }}80;">

                        <h2 class="text-7xl font-bold text-center mb-12"
                            style="color: {{ $selectedVolunteers[$currentTeamIndex]['team']['color_hex'] }}">
                            {{ $selectedVolunteers[$currentTeamIndex]['team']['name'] }}
                        </h2>

                        <div class="grid grid-cols-3 gap-8">
                            @for($slot = 0; $slot < 3; $slot++)
                            <div class="volunteer-slot">
                                <div class="bg-black/50 rounded-lg p-8 h-32 flex items-center justify-center border-2 border-white/20 relative overflow-hidden min-w-[400px]">
                                    <div class="slot-machine-container relative w-full h-full flex items-center justify-center" data-slot="{{ $slot }}">
                                        @if($isShuffling && !$rerollTeamIndex)
                                            <span class="text-white text-3xl font-bold">Selecting...</span>
                                        @elseif($isShuffling && $rerollTeamIndex === $currentTeamIndex && $rerollSlotIndex === $slot)
                                            <span class="text-white text-3xl font-bold">Re-rolling...</span>
                                        @else
                                            @if($selectedVolunteers[$currentTeamIndex]['members'][$slot])
                                            <button wire:click="rerollMember({{ $currentTeamIndex }}, {{ $slot }})"
                                                    class="cursor-pointer text-white text-3xl font-bold hover:text-yellow-400 transition-colors w-full"
                                                    title="Click to re-roll">
                                                {{ $selectedVolunteers[$currentTeamIndex]['members'][$slot] }}
                                            </button>
                                            @else
                                            <span class="text-white/30 text-2xl">Slot {{ $slot + 1 }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endfor
                        </div>

                        <div class="text-center mt-12 h-24 flex items-center justify-center">
                            <button wire:click="nextTeam"
                                    @if($isShuffling || !$selectedVolunteers[$currentTeamIndex]['members'][0]) disabled @endif
                                    class="cursor-pointer px-12 py-6 text-3xl font-bold rounded-lg transition-all duration-300 shadow-xl
                                           @if($isShuffling || !$selectedVolunteers[$currentTeamIndex]['members'][0])
                                               bg-gray-600 text-gray-400 cursor-not-allowed opacity-50
                                           @else
                                               bg-green-500 text-white hover:bg-green-400 transform hover:scale-105
                                           @endif">
                                @if($currentTeamIndex < count($teams) - 1)
                                    NEXT TEAM →
                                @else
                                    COMPLETE SELECTION ✓
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <!-- Selection Complete -->
                <div class="text-center" x-init="launchConfetti()">
                    <div class="flex gap-4 justify-center">
                        <a href="{{ route('game.new') }}"
                           class="px-12 py-6 bg-green-500 text-white text-3xl font-bold rounded-lg hover:bg-green-400 transform hover:scale-105 transition-all duration-300 shadow-xl">
                            LET'S BEGIN →
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function volunteerPicker() {
    return {
        shufflingIntervals: {},
        isProcessing: false,

        init() {
            // Listen for Livewire events with Alpine
            Livewire.on('start-shuffle', (data) => {
                console.log('start-shuffle event received:', data);
                if (!this.isProcessing) {
                    this.isProcessing = true;
                    this.startShuffleAnimation(data);
                }
            });

            Livewire.on('reroll-member', (data) => {
                console.log('reroll-member event received:', data);
                if (!this.isProcessing) {
                    this.isProcessing = true;
                    this.startRerollAnimation(data);
                }
            });
        },

        startShuffleAnimation(data) {
            console.log('startShuffleAnimation data:', data);
            if (!data || !data.finalNames) {
                console.error('Invalid data for shuffle animation:', data);
                return;
            }

            const { teamIndex, shufflingNames, finalNames } = data;
            const duration = 1500; // 3 seconds total
            const intervalTime = 50; // Change name every 50ms

            // Wait a bit for DOM to update
            setTimeout(() => {
                // Animate each slot
                finalNames.forEach((finalName, slotIndex) => {
                    const slotElement = document.querySelector(`[data-slot="${slotIndex}"]`);
                    console.log(`Looking for slot ${slotIndex}:`, slotElement);
                    if (!slotElement) {
                        console.error(`Slot element not found for index ${slotIndex}`);
                        return;
                    }

                const names = shufflingNames[slotIndex] || [];
                let nameIndex = 0;

                // Start with delay for cascade effect
                setTimeout(() => {
                    const startTime = Date.now();

                    // Create slot machine container
                    slotElement.innerHTML = `
                        <div class="slot-reel-container">
                            <div class="slot-reel">
                                ${names.map(name => `
                                    <div class="slot-item">
                                        <span class="text-white text-3xl font-bold">${name}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;

                    const reel = slotElement.querySelector('.slot-reel');
                    let speed = 50; // Initial fast speed
                    let position = 0;
                    const itemHeight = 80; // Height of each name item
                    const totalHeight = names.length * itemHeight;

                    const interval = setInterval(() => {
                        const elapsed = Date.now() - startTime;
                        const progress = elapsed / duration;

                        if (elapsed >= duration) {
                            // Final position - show the selected name
                            slotElement.innerHTML = `
                                <span class="text-white text-3xl font-bold animate-bounce-in">
                                    ${finalName}
                                </span>
                            `;
                            clearInterval(interval);

                            // Check if all slots are done
                            if (slotIndex === finalNames.length - 1) {
                                setTimeout(() => {
                                    this.isProcessing = false;
                                    @this.stopShuffle();
                                }, 500);
                            }
                        } else {
                            // Slow down gradually
                            if (progress > 0.5) {
                                speed = 50 * (1 - (progress - 0.5) * 2) + 5;
                            }

                            // Update position with easing
                            position += speed;
                            if (position > totalHeight) {
                                position = position % totalHeight;
                            }

                            // Apply transform with motion blur effect
                            const blurAmount = Math.min(speed / 10, 5);
                            reel.style.transform = `translateY(-${position}px)`;
                            reel.style.filter = `blur(${blurAmount}px)`;
                        }
                    }, 16); // 60fps

                    this.shufflingIntervals[slotIndex] = interval;
                }, slotIndex * 500); // 500ms delay between slots
                });
            }, 100); // Wait 100ms for DOM update
        },

        startRerollAnimation(data) {
            console.log('startRerollAnimation data:', data);
            if (!data || data.finalName === undefined) {
                console.error('Invalid data for reroll animation:', data);
                return;
            }

            const { teamIndex, slotIndex, shufflingNames, finalName } = data;
            const duration = 2000; // 2 seconds for reroll
            const intervalTime = 50;

            const slotElement = document.querySelector(`[data-slot="${slotIndex}"]`);
            if (!slotElement) return;

            let nameIndex = 0;
            const startTime = Date.now();

            const interval = setInterval(() => {
                const elapsed = Date.now() - startTime;

                if (elapsed >= duration) {
                    // Show final name
                    slotElement.innerHTML = `
                        <span class="text-white text-3xl font-bold animate-bounce-in">
                            ${finalName}
                        </span>
                    `;
                    clearInterval(interval);

                    setTimeout(() => {
                        this.isProcessing = false;
                        @this.stopReroll();
                    }, 300);
                } else {
                    // Show shuffling name
                    const progress = elapsed / duration;
                    const blurAmount = progress < 0.8 ? 0 : (progress - 0.8) * 10;

                    slotElement.innerHTML = `
                        <span class="text-white text-3xl font-bold slot-machine-text"
                              style="filter: blur(${blurAmount}px);">
                            ${shufflingNames[nameIndex % shufflingNames.length]}
                        </span>
                    `;
                    nameIndex++;
                }
            }, intervalTime);
        },

        launchConfetti() {
            // Team colors from config
            const teamColors = ['#3B82F6', '#10B981', '#EAB308', '#FFFFFF', '#EF4444'];

            // Launch multiple bursts for epic celebration
            const duration = 5 * 1000;
            const animationEnd = Date.now() + duration;
            const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            // Initial burst from center
            window.confetti({
                ...defaults,
                particleCount: 100,
                colors: teamColors,
                origin: { x: 0.5, y: 0.5 },
                spread: 70,
                startVelocity: 45,
            });

            // Continuous side bursts
            const interval = setInterval(function() {
                const timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                const particleCount = 50 * (timeLeft / duration);

                // Confetti from left
                window.confetti({
                    ...defaults,
                    particleCount: Math.floor(particleCount / 2),
                    origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 },
                    colors: teamColors,
                });

                // Confetti from right
                window.confetti({
                    ...defaults,
                    particleCount: Math.floor(particleCount / 2),
                    origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 },
                    colors: teamColors,
                });
            }, 250);

            // Extra bursts for emphasis
            setTimeout(() => {
                window.confetti({
                    particleCount: 150,
                    spread: 100,
                    origin: { x: 0.5, y: 0.4 },
                    colors: teamColors,
                    gravity: 0.8,
                    scalar: 1.2,
                    drift: 0,
                    ticks: 200,
                });
            }, 1000);

            // School pride burst (large shapes)
            setTimeout(() => {
                window.confetti({
                    particleCount: 50,
                    spread: 60,
                    origin: { x: 0.5, y: 0.5 },
                    colors: ['#FFD700', '#FFFFFF'], // Gold and white for victory
                    shapes: ['star'],
                    scalar: 2,
                    gravity: 0.5,
                    drift: 0,
                    ticks: 300,
                });
            }, 2000);
        }
    };
}
</script>

<style>
@keyframes bounce-in {
    0% {
        transform: scale(0) rotate(180deg);
        opacity: 0;
    }
    50% {
        transform: scale(1.2) rotate(360deg);
    }
    100% {
        transform: scale(1) rotate(360deg);
        opacity: 1;
    }
}

.animate-bounce-in {
    animation: bounce-in 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.slot-machine-text {
    transition: filter 0.1s ease-out;
}

.slot-machine-container {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 400px; /* Fixed width for stability */
}

.slot-reel-container {
    position: relative;
    width: 100%;
    height: 80px;
    overflow: hidden;
    mask-image: linear-gradient(to bottom,
        transparent 0%,
        black 30%,
        black 70%,
        transparent 100%);
    -webkit-mask-image: linear-gradient(to bottom,
        transparent 0%,
        black 30%,
        black 70%,
        transparent 100%);
}

.slot-reel {
    position: absolute;
    width: 100%;
    transition: none;
}

.slot-item {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}

@keyframes slot-scroll {
    0% {
        transform: translateY(100%);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100%);
        opacity: 0;
    }
}

.team-card-small {
    /* Set initial state to match animation start */
    transform: translateY(-150%) scale(0.3) rotateX(90deg);
    opacity: 0;
    filter: blur(10px);
    animation: slide-down-fancy 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    animation-fill-mode: both; /* Ensures the animation end state is maintained */
}

@keyframes slide-down-fancy {
    0% {
        transform: translateY(-150%) scale(0.3) rotateX(90deg);
        opacity: 0;
        filter: blur(10px);
    }
    50% {
        transform: translateY(-20%) scale(0.9) rotateX(20deg);
        opacity: 0.8;
        filter: blur(2px);
    }
    100% {
        transform: translateY(0) scale(1) rotateX(0);
        opacity: 1;
        filter: blur(0);
    }
}

/* Add glow animation for the status dot */
@keyframes status-glow {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.2);
    }
}
</style>
@endpush
