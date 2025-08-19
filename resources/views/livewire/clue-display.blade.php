<div
    x-data="{
        showModal: true,
        showContent: false,
        isDailyDouble: {{ $isDailyDouble ? 'true' : 'false' }},
        showFloatingScore: false,
        floatingScore: { amount: 0, teamName: '', teamColor: '' }
    }"
    x-init="
        setTimeout(() => showContent = true, 100);
        if (isDailyDouble && {{ $wagerAmount }} === 0) {
            // Play Daily Double sound
            if (window.playSound) window.playSound('daily-double');
        }

        Livewire.on('score-updated', (event) => {
            if (!event.correct && event.points < 0) {
                // Show floating score for incorrect answers
                floatingScore = {
                    amount: Math.abs(event.points),
                    teamName: event.teamName || '',
                    teamColor: event.teamColor || '#ef4444'
                };
                showFloatingScore = true;
                setTimeout(() => showFloatingScore = false, 3000);
            }
        });
    "
    class="fixed inset-0 z-50 flex items-center justify-center">

    <!-- Backdrop -->
    <div
        x-show="showModal"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="absolute inset-0 bg-black/90 backdrop-blur-md">
    </div>

    @if($clue)
        <!-- Daily Double Wager Screen -->
        @if($isDailyDouble && $wagerAmount == 0)
            <div
                x-show="showContent"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 scale-0 rotate-180"
                x-transition:enter-end="opacity-100 scale-100 rotate-0"
                class="relative max-w-4xl w-full mx-4">

                <!-- Animated Background Effect -->
                <div class="absolute inset-0 rounded-3xl overflow-hidden">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-yellow-600 via-orange-600 to-red-600 animate-pulse"></div>
                    <div
                        class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 to-transparent animate-shimmer"></div>
                </div>

                <div
                    class="relative backdrop-blur-xl bg-black/50 rounded-3xl p-12 border-4 border-yellow-400 shadow-[0_0_100px_rgba(250,204,21,0.5)]">
                    <h1 class="text-6xl md:text-8xl font-black text-center animate-bounce">
                        <span
                            class="bg-clip-text text-transparent bg-gradient-to-r from-yellow-400 via-yellow-300 to-yellow-500">
                            DAILY DOUBLE!
                        </span>
                    </h1>
                </div>
            </div>
        @else
            <!-- Clue Display -->
            <div
                x-show="showContent"
                x-transition:enter="transition ease-out duration-700"
                x-transition:enter-start="opacity-0 scale-75"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative max-w-6xl w-full mx-4">

                <div
                    class="backdrop-blur-xl bg-gradient-to-br from-blue-900/90 via-indigo-900/90 to-purple-900/90 rounded-3xl p-8 md:p-12 border-2 border-white/20 shadow-2xl">

                    <!-- Buzzer Status Indicator - Only shows when buzzers are open -->
                    <x-buzzer-indicator
                        :show="$clue && $clue->category && $clue->category->game && !$clue->category->game->current_team_id"/>

                    <!-- Category and Value -->
                    <div class="text-center mb-6"
                         x-show="showContent"
                         x-transition:enter="transition ease-out duration-700 delay-200"
                         x-transition:enter-start="opacity-0 -translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        <h2 class="text-2xl md:text-3xl font-bold text-yellow-400 uppercase tracking-wider">
                            {{ $clue->category->name }}
                        </h2>
                        <div class="text-4xl md:text-5xl font-black text-white mt-2">
                            @if($isDailyDouble && $wagerAmount > 0)
                                ${{ number_format($wagerAmount) }}
                                <span class="text-2xl md:text-3xl text-yellow-400 block mt-1">(Daily Double)</span>
                            @else
                                ${{ number_format($clue->value) }}
                            @endif
                        </div>
                    </div>

                    <!-- Question/Answer Display -->
                    <div class="min-h-[300px] flex items-center justify-center"
                         x-show="showContent"
                         x-transition:enter="transition ease-out duration-700 delay-400"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100">
                        <p class="text-3xl md:text-5xl lg:text-6xl text-center text-white font-bold leading-tight">
                            {{ $clue->question_text }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Floating Score Loss Animation -->
    <div
        x-show="showFloatingScore"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0 translate-y-0 scale-50"
        x-transition:enter-end="opacity-100 -translate-y-20 scale-100"
        x-transition:leave="transition ease-in duration-1000"
        x-transition:leave-start="opacity-100 -translate-y-20 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-40 scale-110"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
        style="z-index: 100;">
        <div class="text-8xl md:text-9xl font-black text-red-500 drop-shadow-[0_0_30px_rgba(239,68,68,0.8)]">
            -$<span x-text="floatingScore.amount"></span>
        </div>
    </div>
</div>
