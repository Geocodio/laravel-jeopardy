<div
    x-data="{
        showModal: true,
        showContent: false,
        isDailyDouble: {{ $isDailyDouble ? 'true' : 'false' }}
    }"
    x-init="
        setTimeout(() => showContent = true, 100);
        if (isDailyDouble && {{ $wagerAmount }} === 0) {
            // Play Daily Double sound
            if (window.playSound) window.playSound('daily-double');
        }
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
                    @if($clue && $clue->category && $clue->category->game && !$clue->category->game->current_team_id)
                        <div class="absolute top-4 right-4">
                            <div class="relative">
                                <!-- Glow effect -->
                                <div
                                    class="absolute inset-0 bg-green-500 rounded-full blur-xl opacity-60 animate-pulse"></div>

                                <!-- Bell icon container -->
                                <div
                                    class="relative bg-gradient-to-br from-green-400 to-emerald-500 rounded-full p-3 shadow-2xl">
                                    <!-- Bell icon -->
                                    <svg class="w-8 h-8 text-white animate-[ring_2s_ease-in-out_infinite]"
                                         fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                    </svg>

                                    <!-- Animated ring waves -->
                                    <div
                                        class="absolute inset-0 rounded-full border-2 border-green-400 animate-ping"></div>
                                </div>
                            </div>
                        </div>
                    @endif

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
                            ${{ number_format($clue->value) }}
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


                    <!-- Team Display -->
                    @if($buzzerTeam)
                        @unless($clue && $clue->category && $clue->category->game && $clue->category->game->current_team_id == $buzzerTeam->id)
                            <div class="text-center mt-8"
                                 x-show="showContent"
                                 x-transition:enter="transition ease-out duration-500"
                                 x-transition:enter-start="opacity-0 translate-y-4"
                                 x-transition:enter-end="opacity-100 translate-y-0">
                                <div
                                    class="inline-flex items-center gap-3 px-6 py-3 rounded-full backdrop-blur-lg bg-white/10 border-2"
                                    style="border-color: {{ $buzzerTeam->color_hex }}">
                                    <div class="w-4 h-4 rounded-full animate-pulse"
                                         style="background-color: {{ $buzzerTeam->color_hex }}"></div>
                                    <span class="text-2xl font-bold" style="color: {{ $buzzerTeam->color_hex }}">
                                        {{ $buzzerTeam->name }} buzzed in!
                                </span>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
