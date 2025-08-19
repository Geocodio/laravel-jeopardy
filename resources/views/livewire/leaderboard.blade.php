<div class="min-h-screen bg-gradient-to-br from-purple-900 via-blue-900 to-indigo-900 overflow-hidden relative">
    <!-- Animated background particles -->
    <div class="absolute inset-0">
        @for ($i = 0; $i < 30; $i++)
            <div class="absolute animate-float-{{ $i % 3 + 1 }} opacity-20"
                 style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ $i * 0.3 }}s;">
                <div class="w-3 h-3 bg-white rounded-full blur-sm"></div>
            </div>
        @endfor
    </div>

    <div class="relative z-10 container mx-auto px-4 py-8">
        <!-- Epic title with animation -->
        <div class="text-center mb-12 animate-title-entrance">
            <h1 class="text-8xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 mb-4 animate-pulse-glow">
                FINAL STANDINGS
            </h1>
            <div class="text-3xl text-white/80 font-bold tracking-wider animate-slide-up">
                {{ $game->name ?? 'Game Results' }}
            </div>
        </div>

        <!-- Leaderboard container -->
        <div class="max-w-4xl mx-auto">
            @foreach ($teams as $team)
                <div class="leaderboard-item opacity-0 transform translate-y-10"
                     style="animation: slideInUp {{ 1 + $team->animation_delay }}s ease-out forwards;">
                    

                    <div class="relative mb-6 group">
                        <div class="absolute inset-0 bg-gradient-to-r from-{{ $team->position == 1 ? 'yellow-400' : ($team->position == 2 ? 'gray-400' : ($team->position == 3 ? 'orange-500' : 'blue-500')) }} 
                                    to-{{ $team->position == 1 ? 'yellow-600' : ($team->position == 2 ? 'gray-600' : ($team->position == 3 ? 'orange-700' : 'purple-600')) }} 
                                    rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity duration-500"></div>
                        
                        <div class="relative bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20 
                                    hover:bg-white/20 transition-all duration-500 hover:scale-[1.02] hover:shadow-2xl
                                    {{ $team->position == 1 ? 'ring-4 ring-yellow-400/50 animate-winner-pulse' : '' }}">
                            
                            <div class="flex items-center justify-between">
                                <!-- Team info -->
                                <div class="flex items-center space-x-4">
                                    <!-- Position indicator for all teams -->
                                    <div class="flex-shrink-0">
                                        @if ($team->position == 1)
                                            <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center shadow-2xl animate-trophy-glow">
                                                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            </div>
                                        @elseif ($team->position == 2)
                                            <div class="w-14 h-14 bg-gradient-to-br from-gray-300 to-gray-500 rounded-full flex items-center justify-center shadow-xl">
                                                <span class="text-2xl font-black text-white">2</span>
                                            </div>
                                        @elseif ($team->position == 3)
                                            <div class="w-12 h-12 bg-gradient-to-br from-orange-600 to-orange-800 rounded-full flex items-center justify-center shadow-xl">
                                                <span class="text-xl font-black text-white">3</span>
                                            </div>
                                        @else
                                            <div class="text-4xl font-black text-white/60 w-16 text-center">
                                                #{{ $team->position }}
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div>
                                        <h2 class="text-3xl font-bold text-white group-hover:scale-105 transition-transform duration-300"
                                            style="color: {{ $team->color_hex }}; text-shadow: 0 0 20px {{ $team->color_hex }}66;">
                                            {{ $team->name }}
                                        </h2>
                                        @if ($team->position == 1)
                                            <div class="text-yellow-300 font-semibold animate-pulse mt-1">
                                                🏆 CHAMPION 🏆
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Score with animation -->
                                <div class="text-right">
                                    <div class="text-5xl font-black text-white animate-score-pop"
                                         style="animation-delay: {{ 1.5 + $team->animation_delay }}s;">
                                        ${{ number_format($team->score) }}
                                    </div>
                                    <div class="text-sm text-white/60 mt-1">
                                        Final Score
                                    </div>
                                </div>
                            </div>

                            <!-- Progress bar showing relative score -->
                            @php
                                $maxScore = $teams->max('score');
                            @endphp
                            @if ($maxScore > 0)
                                <div class="mt-4 bg-white/10 rounded-full h-3 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-{{ $team->position == 1 ? 'yellow-400' : 'blue-400' }} 
                                                to-{{ $team->position == 1 ? 'yellow-600' : 'purple-600' }} 
                                                animate-score-fill rounded-full"
                                         style="width: 0%; animation: fillScore {{ 2 + $team->animation_delay }}s ease-out forwards;
                                                --final-width: {{ ($team->score / $maxScore) * 100 }}%;"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fillScore {
            to {
                width: var(--final-width);
            }
        }

        @keyframes float-1 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        @keyframes float-2 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(-10deg); }
        }

        @keyframes float-3 {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-25px) rotate(5deg); }
        }

        @keyframes title-entrance {
            0% {
                transform: scale(0.3) rotate(-5deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.1) rotate(2deg);
            }
            100% {
                transform: scale(1) rotate(0);
                opacity: 1;
            }
        }

        @keyframes pulse-glow {
            0%, 100% {
                filter: drop-shadow(0 0 20px rgba(255, 255, 0, 0.5));
            }
            50% {
                filter: drop-shadow(0 0 40px rgba(255, 255, 0, 0.8));
            }
        }

        @keyframes trophy-glow {
            0%, 100% {
                box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
            }
            50% {
                box-shadow: 0 0 50px rgba(255, 215, 0, 0.9);
            }
        }

        @keyframes winner-pulse {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4);
            }
            50% {
                box-shadow: 0 0 20px 10px rgba(255, 215, 0, 0);
            }
        }

        @keyframes score-pop {
            0% {
                transform: scale(0) rotate(360deg);
            }
            80% {
                transform: scale(1.2) rotate(-10deg);
            }
            100% {
                transform: scale(1) rotate(0);
            }
        }

        @keyframes bounce-slow {
            0%, 100% {
                transform: translateY(-50%) scale(1);
            }
            50% {
                transform: translateY(-50%) scale(1.1);
            }
        }

        @keyframes slide-up {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .animate-title-entrance {
            animation: title-entrance 1s ease-out;
        }

        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .animate-trophy-glow {
            animation: trophy-glow 2s ease-in-out infinite;
        }

        .animate-winner-pulse {
            animation: winner-pulse 2s ease-in-out infinite;
        }

        .animate-score-pop {
            animation: score-pop 0.8s ease-out both;
        }

        .animate-bounce-slow {
            animation: bounce-slow 2s ease-in-out infinite;
        }

        .animate-slide-up {
            animation: slide-up 0.8s ease-out 0.3s both;
        }

        .animate-float-1 {
            animation: float-1 6s ease-in-out infinite;
        }

        .animate-float-2 {
            animation: float-2 8s ease-in-out infinite;
        }

        .animate-float-3 {
            animation: float-3 7s ease-in-out infinite;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const teams = @json($teams);
            const totalTeams = teams.length;
            let confettiCount = 0;
            
            // Helper function for random range
            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }
            
            // Start confetti as each team is revealed
            teams.forEach((team, index) => {
                const delay = (1000 + team.animation_delay * 1000);
                
                setTimeout(() => {
                    // Small burst for each team reveal
                    window.confetti({
                        particleCount: 30,
                        spread: 60,
                        origin: { 
                            x: randomInRange(0.3, 0.7),
                            y: 0.7 - (index * 0.1)
                        },
                        colors: ['#ffffff', '#fbbf24', '#c084fc']
                    });
                    
                    // If it's top 3, extra celebration
                    if (team.position <= 3) {
                        setTimeout(() => {
                            window.confetti({
                                particleCount: 50,
                                spread: 80,
                                origin: { x: 0.5, y: 0.6 },
                                colors: team.position === 1 ? ['#FFD700', '#FFA500', '#FFFF00'] : 
                                        team.position === 2 ? ['#C0C0C0', '#E5E5E5', '#FFFFFF'] :
                                        ['#CD7F32', '#FFA500', '#FF6347']
                            });
                        }, 200);
                    }
                    
                    // MASSIVE celebration for the winner
                    if (team.position === 1) {
                        // Delay for dramatic effect
                        setTimeout(() => {
                            // Explosion of golden confetti
                            for (let i = 0; i < 5; i++) {
                                setTimeout(() => {
                                    window.confetti({
                                        particleCount: 150,
                                        spread: 100,
                                        startVelocity: 45,
                                        origin: { 
                                            x: randomInRange(0.2, 0.8),
                                            y: randomInRange(0.4, 0.6)
                                        },
                                        colors: ['#FFD700', '#FFA500', '#FFFF00', '#FFD700'],
                                        ticks: 300
                                    });
                                }, i * 200);
                            }
                            
                            // Side cannons
                            window.confetti({
                                particleCount: 100,
                                angle: 60,
                                spread: 100,
                                origin: { x: 0, y: 0.6 },
                                colors: ['#FFD700', '#FFA500']
                            });
                            window.confetti({
                                particleCount: 100,
                                angle: 120,
                                spread: 100,
                                origin: { x: 1, y: 0.6 },
                                colors: ['#FFD700', '#FFA500']
                            });
                            
                            // Fireworks effect
                            const fireworks = () => {
                                const x = Math.random();
                                const y = randomInRange(0.3, 0.7);
                                
                                // Launch
                                window.confetti({
                                    particleCount: 5,
                                    startVelocity: 30,
                                    spread: 5,
                                    origin: { x: x, y: 1 },
                                    colors: ['#ffffff'],
                                    ticks: 60
                                });
                                
                                // Explosion
                                setTimeout(() => {
                                    window.confetti({
                                        particleCount: 100,
                                        startVelocity: 40,
                                        spread: 360,
                                        origin: { x: x, y: y },
                                        colors: ['#FFD700', '#FFA500', '#FF69B4', '#00FFFF', '#FF00FF'],
                                        gravity: 0.8,
                                        scalar: 1.2,
                                        ticks: 150
                                    });
                                }, 600);
                            };
                            
                            // Multiple fireworks
                            for (let i = 0; i < 8; i++) {
                                setTimeout(fireworks, i * 400);
                            }
                            
                        }, 500);
                    }
                }, delay);
            });
            
            // Continuous background sparkles for atmosphere
            const sparkleInterval = setInterval(() => {
                window.confetti({
                    particleCount: 3,
                    spread: 60,
                    startVelocity: 5,
                    ticks: 100,
                    colors: ['#ffffff', '#ffd700', '#c084fc'],
                    origin: {
                        x: Math.random(),
                        y: randomInRange(-0.1, 0.3)
                    },
                    gravity: 0.3,
                    scalar: 0.7
                });
            }, 500);
            
            // Celebration rain
            const celebrationRain = setInterval(() => {
                window.confetti({
                    particleCount: 10,
                    spread: 180,
                    startVelocity: 10,
                    origin: { 
                        x: Math.random(),
                        y: -0.1
                    },
                    colors: ['#fbbf24', '#c084fc', '#60a5fa', '#f472b6', '#34d399'],
                    gravity: 0.7,
                    scalar: 0.8,
                    drift: randomInRange(-0.4, 0.4)
                });
            }, 300);
            
            // Epic finale after all teams revealed
            const finaleDelay = (1000 + teams[teams.length - 1].animation_delay * 1000) + 2000;
            setTimeout(() => {
                // Grand finale
                const colors = ['#FFD700', '#FFA500', '#FF69B4', '#00FFFF', '#FF00FF', '#00FF00', '#FF0000'];
                
                // Massive center burst
                window.confetti({
                    particleCount: 500,
                    spread: 180,
                    startVelocity: 50,
                    origin: { x: 0.5, y: 0.5 },
                    colors: colors,
                    gravity: 0.5,
                    scalar: 1.5,
                    ticks: 400
                });
                
                // Diagonal crosses
                for (let i = 0; i < 4; i++) {
                    setTimeout(() => {
                        window.confetti({
                            particleCount: 75,
                            angle: 45 + (i * 90),
                            spread: 50,
                            startVelocity: 60,
                            origin: { x: 0.5, y: 0.5 },
                            colors: colors
                        });
                    }, i * 100);
                }
                
                // Stop continuous effects after 60 seconds
                setTimeout(() => {
                    clearInterval(sparkleInterval);
                    clearInterval(celebrationRain);
                }, 60000);
                
            }, finaleDelay);
        });
    </script>
</div>