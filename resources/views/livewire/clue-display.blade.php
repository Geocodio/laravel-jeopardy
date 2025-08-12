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
                    <div class="absolute inset-0 bg-gradient-to-br from-yellow-600 via-orange-600 to-red-600 animate-pulse"></div>
                    <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/20 to-transparent animate-shimmer"></div>
                </div>
                
                <div class="relative backdrop-blur-xl bg-black/50 rounded-3xl p-12 border-4 border-yellow-400 shadow-[0_0_100px_rgba(250,204,21,0.5)]">
                    <h1 class="text-6xl md:text-8xl font-black text-center mb-8 animate-bounce">
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-yellow-400 via-yellow-300 to-yellow-500">
                            DAILY DOUBLE!
                        </span>
                    </h1>
                    
                    <div class="text-3xl text-center mb-8 text-blue-200">
                        {{ $buzzerTeam ? $buzzerTeam->name : 'Select Team & Wager' }}
                    </div>
                    
                    @if(!$buzzerTeam)
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-yellow-300 mb-4 text-center">Select Team:</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                                @foreach($availableTeams as $team)
                                    <button 
                                        wire:click="selectTeamManually({{ $team->id }})"
                                        class="px-4 py-3 rounded-lg font-bold text-white transition-all transform hover:scale-105 shadow-lg"
                                        style="background-color: {{ $team->color_hex }}; border: 2px solid rgba(255,255,255,0.5)">
                                        {{ $team->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach([200, 400, 600, 800, 1000, 1200, 1500, 2000] as $amount)
                                <button 
                                    wire:click="setWager({{ $amount }})" 
                                    class="group relative overflow-hidden bg-gradient-to-br from-blue-600 to-purple-700 hover:from-blue-700 hover:to-purple-800 text-white font-bold py-4 px-6 rounded-xl text-2xl transition-all transform hover:scale-110 shadow-lg">
                                    <span class="relative z-10">${{ number_format($amount) }}</span>
                                    <div class="absolute inset-0 bg-white/20 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300"></div>
                                </button>
                            @endforeach
                        </div>
                    @endif
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
                
                <div class="backdrop-blur-xl bg-gradient-to-br from-blue-900/90 via-indigo-900/90 to-purple-900/90 rounded-3xl p-8 md:p-12 border-2 border-white/20 shadow-2xl">
                    
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
                        @if(!$showingAnswer)
                            <p class="text-3xl md:text-5xl lg:text-6xl text-center text-white font-bold leading-tight">
                                {{ $clue->question_text }}
                            </p>
                        @else
                            <div class="text-center">
                                <p class="text-2xl md:text-3xl text-blue-300 mb-4">The answer is:</p>
                                <p class="text-3xl md:text-5xl lg:text-6xl text-green-400 font-bold animate-pulse">
                                    {{ $clue->answer_text }}
                                </p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Timer Display -->
                    @if($timerRunning)
                        <div class="flex justify-center mt-8"
                             x-show="showContent"
                             x-transition:enter="transition ease-out duration-700 delay-600"
                             x-transition:enter-start="opacity-0 scale-0"
                             x-transition:enter-end="opacity-100 scale-100">
                            <div class="relative">
                                <!-- Timer Ring -->
                                <svg class="w-32 h-32 transform -rotate-90">
                                    <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="none" class="text-gray-700"></circle>
                                    <circle cx="64" cy="64" r="60" stroke="currentColor" stroke-width="8" fill="none" 
                                            class="{{ $timeRemaining <= 5 ? 'text-red-500' : ($timeRemaining <= 10 ? 'text-yellow-500' : 'text-green-500') }}"
                                            stroke-dasharray="{{ 377 * ($timeRemaining / 30) }} 377"
                                            stroke-linecap="round"></circle>
                                </svg>
                                <!-- Timer Text -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-4xl font-black {{ $timeRemaining <= 5 ? 'text-red-500 animate-pulse' : 'text-white' }}">
                                        {{ $timeRemaining }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Buzzer Team Display -->
                    @if($buzzerTeam)
                        <div class="text-center mt-8"
                             x-show="showContent"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="inline-flex items-center gap-3 px-6 py-3 rounded-full backdrop-blur-lg bg-white/10 border-2"
                                 style="border-color: {{ $buzzerTeam->color_hex }}">
                                <div class="w-4 h-4 rounded-full animate-pulse" style="background-color: {{ $buzzerTeam->color_hex }}"></div>
                                <span class="text-2xl font-bold" style="color: {{ $buzzerTeam->color_hex }}">
                                    {{ $buzzerTeam->name }} buzzed in!
                                </span>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Manual Team Selection -->
                    @if($showManualTeamSelection && !$buzzerTeam)
                        <div class="mb-6 p-4 bg-black/30 rounded-xl"
                             x-show="showContent"
                             x-transition:enter="transition ease-out duration-500"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100">
                            <h3 class="text-xl font-bold text-yellow-400 mb-4 text-center">Select Team Manually:</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($availableTeams as $team)
                                    <button 
                                        wire:click="selectTeamManually({{ $team->id }})"
                                        class="px-4 py-3 rounded-lg font-bold text-white transition-all transform hover:scale-105 shadow-lg"
                                        style="background-color: {{ $team->color_hex }}; border: 2px solid rgba(255,255,255,0.3)">
                                        {{ $team->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Host Controls -->
                    <div class="flex flex-wrap justify-center gap-4 mt-8"
                         x-show="showContent"
                         x-transition:enter="transition ease-out duration-700 delay-800"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        @if($buzzerTeam)
                            <button 
                                wire:click="markCorrect" 
                                class="group relative inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Correct (+${{ number_format($clue->value) }})
                            </button>
                            <button 
                                wire:click="markIncorrect" 
                                class="group relative inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Incorrect (-${{ number_format($clue->value) }})
                            </button>
                        @else
                            <button 
                                wire:click="toggleManualTeamSelection" 
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                Manual Select Team
                            </button>
                        @endif
                        
                        @if(!$showingAnswer)
                            <button 
                                wire:click="showAnswer" 
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                Show Answer
                            </button>
                        @endif
                        
                        <button 
                            wire:click="skipClue" 
                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                            </svg>
                            Skip
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>