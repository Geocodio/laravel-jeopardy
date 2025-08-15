<div class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 text-white">
    @if($game)
        <div class="container mx-auto px-4 py-4">
            <!-- Header -->
            <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-4 mb-4 border border-slate-700">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-yellow-400">Host Control Panel</h1>
                    <div class="flex gap-4 items-center">
                        <span class="text-sm text-slate-400">Game ID: {{ $game->id }}</span>
                        @if($game->status === 'main_game')
                            <button wire:click="startLightningRound" 
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-colors">
                                ⚡ Start Lightning Round
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left Column: Teams & Scoring -->
                <div class="space-y-4">
                    <!-- Team Selection -->
                    <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-4 border border-slate-700">
                        <h2 class="text-lg font-bold mb-3 text-yellow-400">Current Team</h2>
                        <div class="grid grid-cols-1 gap-2">
                            @foreach($teams as $team)
                                <div class="flex items-center justify-between p-3 rounded-lg border-2 transition-all
                                    {{ $currentTeam && $currentTeam->id === $team->id 
                                        ? 'border-yellow-400 bg-yellow-400/10' 
                                        : 'border-slate-600 hover:border-slate-500' }}"
                                    style="background-color: {{ $currentTeam && $currentTeam->id === $team->id ? $team->color_hex . '20' : '' }}">
                                    <button wire:click="selectCurrentTeam({{ $team->id }})"
                                        class="flex-1 text-left font-semibold">
                                        {{ $team->name }}
                                    </button>
                                    @if($currentTeam && $currentTeam->id === $team->id)
                                        <span class="text-yellow-400">👑</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Score Management -->
                    <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-4 border border-slate-700">
                        <h2 class="text-lg font-bold mb-3 text-yellow-400">Score Control</h2>
                        <div class="space-y-3">
                            @foreach($teams as $team)
                                <div class="flex items-center justify-between p-3 rounded-lg border border-slate-600">
                                    <div>
                                        <div class="font-semibold">{{ $team->name }}</div>
                                        <div class="text-2xl font-bold text-green-400">${{ number_format($team->score) }}</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="adjustScore({{ $team->id }}, -100)"
                                            class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded text-sm font-bold">
                                            -100
                                        </button>
                                        <button wire:click="adjustScore({{ $team->id }}, 100)"
                                            class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded text-sm font-bold">
                                            +100
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Manual Buzzer -->
                    <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-4 border border-slate-700">
                        <h2 class="text-lg font-bold mb-3 text-yellow-400">Manual Buzzer</h2>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($teams as $team)
                                <button wire:click="triggerBuzzer({{ $team->id }})"
                                    class="px-4 py-3 rounded-lg font-semibold text-white transition-all hover:scale-105"
                                    style="background-color: {{ $team->color_hex }}">
                                    🔔 {{ $team->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Middle Column: Game Board -->
                <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-4 border border-slate-700">
                    <h2 class="text-lg font-bold mb-3 text-yellow-400">Game Board</h2>
                    
                    <!-- Categories -->
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        @foreach($categories->take(3) as $category)
                            <div class="text-xs font-bold text-center p-2 bg-blue-800/50 rounded">
                                {{ Str::limit($category->name, 15) }}
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- First 3 Categories Clues -->
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        @foreach([100, 200, 300, 400] as $value)
                            @foreach($categories->take(3) as $category)
                                @php
                                    $clue = $category->clues->where('value', $value)->first();
                                @endphp
                                @if($clue)
                                    <button 
                                        wire:click="selectClue({{ $clue->id }})"
                                        @if($clue->is_answered) disabled @endif
                                        class="h-16 rounded flex items-center justify-center text-sm font-bold transition-all
                                            {{ $clue->is_answered 
                                                ? 'bg-gray-700/50 text-gray-500 cursor-not-allowed' 
                                                : 'bg-blue-600 hover:bg-blue-700 cursor-pointer hover:scale-105' }}
                                            {{ $selectedClue && $selectedClue->id === $clue->id ? 'ring-2 ring-yellow-400' : '' }}">
                                        @if(!$clue->is_answered)
                                            ${{ $value }}
                                            @if($clue->is_daily_double)
                                                <span class="ml-1 text-yellow-400">⭐</span>
                                            @endif
                                        @else
                                            ✓
                                        @endif
                                    </button>
                                @else
                                    <div class="h-16 bg-gray-800/30 rounded"></div>
                                @endif
                            @endforeach
                        @endforeach
                    </div>

                    <!-- Categories for columns 4-6 -->
                    <div class="grid grid-cols-3 gap-2 mb-2">
                        @foreach($categories->slice(3, 3) as $category)
                            <div class="text-xs font-bold text-center p-2 bg-blue-800/50 rounded">
                                {{ Str::limit($category->name, 15) }}
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Last 3 Categories Clues -->
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([100, 200, 300, 400] as $value)
                            @foreach($categories->slice(3, 3) as $category)
                                @php
                                    $clue = $category->clues->where('value', $value)->first();
                                @endphp
                                @if($clue)
                                    <button 
                                        wire:click="selectClue({{ $clue->id }})"
                                        @if($clue->is_answered) disabled @endif
                                        class="h-16 rounded flex items-center justify-center text-sm font-bold transition-all
                                            {{ $clue->is_answered 
                                                ? 'bg-gray-700/50 text-gray-500 cursor-not-allowed' 
                                                : 'bg-blue-600 hover:bg-blue-700 cursor-pointer hover:scale-105' }}
                                            {{ $selectedClue && $selectedClue->id === $clue->id ? 'ring-2 ring-yellow-400' : '' }}">
                                        @if(!$clue->is_answered)
                                            ${{ $value }}
                                            @if($clue->is_daily_double)
                                                <span class="ml-1 text-yellow-400">⭐</span>
                                            @endif
                                        @else
                                            ✓
                                        @endif
                                    </button>
                                @else
                                    <div class="h-16 bg-gray-800/30 rounded"></div>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                </div>

                <!-- Right Column: Clue Display -->
                <div class="space-y-4">
                    @if($showClueModal && $selectedClue)
                        <!-- Daily Double Wager -->
                        @if($showDailyDoubleWager)
                            <div class="bg-gradient-to-br from-yellow-600/20 to-orange-600/20 backdrop-blur-lg rounded-xl p-6 border-2 border-yellow-400">
                                <h2 class="text-2xl font-black text-yellow-400 mb-4 animate-pulse">DAILY DOUBLE!</h2>
                                <p class="text-lg mb-4">Team: {{ $dailyDoubleTeam->name }}</p>
                                <p class="text-sm text-gray-300 mb-4">Select Wager Amount:</p>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach([200, 400, 600, 800, 1000, 1200, 1500, 2000] as $amount)
                                        <button wire:click="setDailyDoubleWager({{ $amount }})"
                                            class="px-4 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold text-lg transition-all hover:scale-105">
                                            ${{ number_format($amount) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <!-- Clue Info -->
                            <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-6 border border-slate-700">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-sm text-gray-400">Category</h3>
                                        <p class="text-lg font-bold text-yellow-400">{{ $selectedClue->category->name }}</p>
                                    </div>
                                    <div class="text-right">
                                        <h3 class="text-sm text-gray-400">Value</h3>
                                        <p class="text-2xl font-bold text-green-400">
                                            ${{ $selectedClue->is_daily_double ? number_format($dailyDoubleWager) : number_format($selectedClue->value) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Question -->
                                <div class="mb-6 p-4 bg-blue-900/30 rounded-lg">
                                    <h3 class="text-sm text-gray-400 mb-2">Question</h3>
                                    <p class="text-xl font-semibold">{{ $selectedClue->question_text }}</p>
                                </div>

                                <!-- Answer (Host Only) -->
                                <div class="mb-6 p-4 bg-green-900/30 rounded-lg">
                                    <div class="flex justify-between items-center mb-2">
                                        <h3 class="text-sm text-gray-400">Answer (Host Only)</h3>
                                        @if(!$showAnswer)
                                            <button wire:click="revealAnswer" 
                                                class="text-xs px-2 py-1 bg-green-600 hover:bg-green-700 rounded">
                                                Reveal
                                            </button>
                                        @endif
                                    </div>
                                    @if($showAnswer)
                                        <p class="text-xl font-semibold text-green-400">{{ $selectedClue->answer_text }}</p>
                                    @else
                                        <p class="text-gray-500 italic">Click reveal to see answer</p>
                                    @endif
                                </div>

                                <!-- Timer -->
                                @if($timerRunning)
                                    <div class="mb-4 text-center">
                                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full border-4 
                                            {{ $timeRemaining <= 5 ? 'border-red-500 text-red-500' : ($timeRemaining <= 10 ? 'border-yellow-500 text-yellow-500' : 'border-green-500 text-green-500') }}">
                                            <span class="text-3xl font-bold">{{ $timeRemaining }}</span>
                                        </div>
                                    </div>
                                @endif

                                <!-- Answering Team -->
                                @if($currentTeam)
                                    <div class="mb-4 p-3 rounded-lg border-2 border-yellow-400 bg-yellow-400/10">
                                        <p class="text-sm text-gray-400">Answering:</p>
                                        <p class="text-xl font-bold">{{ $currentTeam->name }}</p>
                                    </div>
                                @endif

                                <!-- Answer Controls -->
                                <div class="grid grid-cols-2 gap-3">
                                    <button wire:click="markCorrect" 
                                        class="px-4 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-bold text-lg transition-all hover:scale-105">
                                        ✓ Correct
                                    </button>
                                    <button wire:click="markIncorrect" 
                                        class="px-4 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-bold text-lg transition-all hover:scale-105">
                                        ✗ Incorrect
                                    </button>
                                </div>

                                <!-- Skip/Close -->
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <button wire:click="skipClue" 
                                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded-lg font-semibold transition-all">
                                        Skip Clue
                                    </button>
                                    <button wire:click="closeClue" 
                                        class="px-4 py-2 bg-slate-600 hover:bg-slate-700 rounded-lg font-semibold transition-all">
                                        Close
                                    </button>
                                </div>
                            </div>
                        @endif
                    @else
                        <!-- No Clue Selected -->
                        <div class="bg-slate-800/50 backdrop-blur-lg rounded-xl p-6 border border-slate-700">
                            <p class="text-center text-gray-400">Select a clue from the game board</p>
                        </div>
                    @endif
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