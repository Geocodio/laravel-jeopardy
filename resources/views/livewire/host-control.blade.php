<div class="h-screen overflow-y-auto bg-gradient-to-br from-slate-900 to-slate-800 text-white">
    @if($game)
        <div class="container mx-auto px-4 py-4 relative">
            <!-- Game ID in corner -->
            <div class="absolute top-4 left-4 text-xs text-slate-500 bg-slate-800/50 backdrop-blur px-2 py-1 rounded">
                Game ID: {{ $game->id }}
            </div>
            
            <!-- Lightning Round Button -->
            @if($game->status === 'main_game')
                <div class="flex justify-end mb-4">
                    <button wire:click="startLightningRound" 
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-colors">
                        ⚡ Start Lightning Round
                    </button>
                </div>
            @endif

            <!-- Main Content Grid - Optimized for iPad Pro -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Left Column: Team Controls (smaller) -->
                <div class="xl:col-span-1 space-y-6">
                    <!-- Combined Team Control Panel -->
                    <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-4 border border-slate-700">
                        <div class="grid grid-cols-1 gap-3">
                            @foreach($teams as $team)
                                <div class="flex items-center gap-3 p-3 rounded-lg border-2 transition-all
                                    {{ $currentTeam && $currentTeam->id === $team->id 
                                        ? 'border-yellow-400' 
                                        : 'border-slate-600' }}"
                                    style="background-color: {{ $team->color_hex }}15;
                                           {{ $currentTeam && $currentTeam->id === $team->id 
                                               ? 'box-shadow: 0 0 30px ' . $team->color_hex . '60, inset 0 0 20px ' . $team->color_hex . '30;' 
                                               : '' }}">
                                    
                                    <!-- Team Name with Glow Effect -->
                                    <div class="flex-1">
                                        <div class="text-lg font-bold text-yellow-400">
                                            {{ $team->name }}
                                            @if($currentTeam && $currentTeam->id === $team->id)
                                                <span class="ml-1">👑</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-300">
                                            Score: <span class="font-bold text-green-400">${{ number_format($team->score) }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Control Buttons -->
                                    <div class="flex gap-2">
                                        <!-- Select as Current Team -->
                                        <button wire:click="selectCurrentTeam({{ $team->id }})"
                                            class="px-3 py-3 rounded-lg font-bold transition-all hover:scale-105
                                                {{ $currentTeam && $currentTeam->id === $team->id 
                                                    ? 'bg-yellow-500 text-slate-900' 
                                                    : 'bg-slate-700 hover:bg-slate-600 text-white' }}"
                                            title="Select as current team">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Manual Buzzer -->
                                        <button wire:click="triggerBuzzer({{ $team->id }})"
                                            class="px-3 py-3 rounded-lg font-bold text-white transition-all hover:scale-105"
                                            style="background-color: {{ $team->color_hex }};
                                                   box-shadow: 0 4px 15px {{ $team->color_hex }}50;"
                                            title="Trigger buzzer for {{ $team->name }}">
                                            🔔
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right Column: Game Board (bigger) -->
                <div class="xl:col-span-2 bg-slate-800/50 backdrop-blur-lg rounded-xl p-6 border border-slate-700">
                    <!-- All 6 Categories in One Row -->
                    <div class="grid grid-cols-6 gap-3 mb-4">
                        @foreach($categories as $category)
                            <div class="text-base font-bold text-center p-4 bg-blue-800/50 rounded text-yellow-400">
                                {{ Str::limit($category->name, 15) }}
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- All Clues in 6 Columns -->
                    <div class="grid grid-cols-6 gap-3">
                        @foreach([100, 200, 300, 400] as $value)
                            @foreach($categories as $category)
                                @php
                                    $clue = $category->clues->where('value', $value)->first();
                                @endphp
                                @if($clue)
                                    <button 
                                        wire:click="selectClue({{ $clue->id }})"
                                        @if($clue->is_answered) disabled @endif
                                        class="h-24 rounded-lg flex items-center justify-center text-2xl font-bold transition-all
                                            {{ $clue->is_answered 
                                                ? 'bg-gray-700/50 text-gray-500 cursor-not-allowed' 
                                                : 'bg-blue-600 hover:bg-blue-700 cursor-pointer hover:scale-105' }}
                                            {{ $selectedClue && $selectedClue->id === $clue->id ? 'ring-4 ring-yellow-400' : '' }}">
                                        @if(!$clue->is_answered)
                                            <span class="text-yellow-400">${{ $value }}</span>
                                            @if($clue->is_daily_double)
                                                <span class="ml-1 text-yellow-400">⭐</span>
                                            @endif
                                        @else
                                            ✓
                                        @endif
                                    </button>
                                @else
                                    <div class="h-24 bg-gray-800/30 rounded-lg"></div>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Clue Display Modal - Full Width Below -->
            <div class="mt-6">
                @if($showClueModal && $selectedClue)
                    <!-- Daily Double Wager -->
                    @if($showDailyDoubleWager)
                        <div class="bg-gradient-to-br from-yellow-600/20 to-orange-600/20 backdrop-blur-lg rounded-xl p-8 border-2 border-yellow-400">
                            <h2 class="text-3xl font-black text-yellow-400 mb-6 animate-pulse">DAILY DOUBLE!</h2>
                            <p class="text-2xl mb-6">Team: {{ $dailyDoubleTeam->name }}</p>
                            <p class="text-lg text-gray-300 mb-6">Select Wager Amount:</p>
                            <div class="grid grid-cols-4 gap-4">
                                @foreach([200, 400, 600, 800, 1000, 1200, 1500, 2000] as $amount)
                                    <button wire:click="setDailyDoubleWager({{ $amount }})"
                                        class="px-6 py-6 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold text-2xl transition-all hover:scale-105">
                                        ${{ number_format($amount) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Clue Info -->
                        <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-8 border border-slate-700">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Left Side: Question and Answer -->
                                <div>
                                    <div class="flex justify-between items-start mb-6">
                                        <div>
                                            <h3 class="text-lg text-gray-400">Category</h3>
                                            <p class="text-2xl font-bold text-yellow-400">{{ $selectedClue->category->name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <h3 class="text-lg text-gray-400">Value</h3>
                                            <p class="text-3xl font-bold text-green-400">
                                                ${{ $selectedClue->is_daily_double ? number_format($dailyDoubleWager) : number_format($selectedClue->value) }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Question -->
                                    <div class="mb-6 p-6 bg-blue-900/30 rounded-lg">
                                        <h3 class="text-lg text-gray-400 mb-3">Question</h3>
                                        <p class="text-2xl font-semibold">{{ $selectedClue->question_text }}</p>
                                    </div>

                                    <!-- Answer (Host Only) -->
                                    <div class="mb-6 p-6 bg-green-900/30 rounded-lg">
                                        <div class="flex justify-between items-center mb-3">
                                            <h3 class="text-lg text-gray-400">Answer (Host Only)</h3>
                                            @if(!$showAnswer)
                                                <button wire:click="revealAnswer" 
                                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-lg text-lg font-semibold">
                                                    Reveal
                                                </button>
                                            @endif
                                        </div>
                                        @if($showAnswer)
                                            <p class="text-2xl font-semibold text-green-400">{{ $selectedClue->answer_text }}</p>
                                        @else
                                            <p class="text-xl text-gray-500 italic">Click reveal to see answer</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Right Side: Controls -->
                                <div>
                                    <!-- Timer -->
                                    @if($timerRunning)
                                        <div class="mb-6 text-center">
                                            <div class="inline-flex items-center justify-center w-32 h-32 rounded-full border-8 
                                                {{ $timeRemaining <= 5 ? 'border-red-500 text-red-500' : ($timeRemaining <= 10 ? 'border-yellow-500 text-yellow-500' : 'border-green-500 text-green-500') }}">
                                                <span class="text-5xl font-bold">{{ $timeRemaining }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Answering Team -->
                                    @if($currentTeam)
                                        <div class="mb-6 p-6 rounded-lg border-2 border-yellow-400 bg-yellow-400/10">
                                            <p class="text-lg text-gray-400">Answering:</p>
                                            <p class="text-3xl font-bold">{{ $currentTeam->name }}</p>
                                        </div>
                                    @endif

                                    <!-- Answer Controls -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <button wire:click="markCorrect" 
                                            class="px-6 py-8 bg-green-600 hover:bg-green-700 rounded-lg font-bold text-2xl transition-all hover:scale-105">
                                            ✓ Correct
                                        </button>
                                        <button wire:click="markIncorrect" 
                                            class="px-6 py-8 bg-red-600 hover:bg-red-700 rounded-lg font-bold text-2xl transition-all hover:scale-105">
                                            ✗ Incorrect
                                        </button>
                                    </div>

                                    <!-- Skip/Close -->
                                    <div class="mt-4 grid grid-cols-2 gap-4">
                                        <button wire:click="skipClue" 
                                            class="px-6 py-4 bg-gray-600 hover:bg-gray-700 rounded-lg font-bold text-xl transition-all">
                                            Skip Clue
                                        </button>
                                        <button wire:click="closeClue" 
                                            class="px-6 py-4 bg-slate-600 hover:bg-slate-700 rounded-lg font-bold text-xl transition-all">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <!-- No Clue Selected -->
                    <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-8 border border-slate-700">
                        <p class="text-center text-2xl text-gray-400">Select a clue from the game board</p>
                    </div>
                @endif
            </div>

            <!-- Score Control - Secondary Action at Bottom -->
            <div class="mt-6 bg-slate-800/50 backdrop-blur-lg rounded-xl p-6 border border-slate-700">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($teams as $team)
                        <div class="flex items-center justify-between p-4 rounded-lg border border-slate-600 bg-slate-900/50">
                            <div>
                                <div class="font-bold text-lg">{{ $team->name }}</div>
                                <div class="text-3xl font-bold text-green-400">${{ number_format($team->score) }}</div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button wire:click="adjustScore({{ $team->id }}, 100)"
                                    class="px-4 py-3 bg-green-600 hover:bg-green-700 rounded text-lg font-bold">
                                    +100
                                </button>
                                <button wire:click="adjustScore({{ $team->id }}, -100)"
                                    class="px-4 py-3 bg-red-600 hover:bg-red-700 rounded text-lg font-bold">
                                    -100
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Timer Script -->
        @if($showClueModal && $timerRunning)
            <script>
                // Start timer interval
                if (!window.hostTimerInterval) {
                    window.hostTimerInterval = setInterval(() => {
                        Livewire.dispatch('timer-tick');
                    }, 1000);
                }
            </script>
        @else
            <script>
                // Clear timer interval
                if (window.hostTimerInterval) {
                    clearInterval(window.hostTimerInterval);
                    window.hostTimerInterval = null;
                }
            </script>
        @endif
    @else
        <div class="flex items-center justify-center h-screen">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">No Game Found</h1>
                <a href="{{ route('game.new') }}" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold">
                    Start New Game
                </a>
            </div>
        </div>
    @endif
</div>