<div class="h-screen overflow-y-auto bg-gradient-to-br from-slate-900 to-slate-800 text-white">
    @if($game)
        <script>
            window.gameId = {{ $game->id }};
        </script>
        <div class="container mx-auto px-4 py-4">
            <!-- Top Bar with Game ID and Lightning Round -->
            <div class="flex justify-between items-center mb-4">
                <div class="text-sm text-yellow-400 bg-slate-900/80 px-3 py-1 rounded border border-slate-700">
                    Game ID: {{ $game->id }}
                </div>

                @if($game->status === 'main_game')
                    <button wire:click="startLightningRound"
                            class="cursor-pointer px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg font-semibold transition-colors">
                        ⚡ Start Lightning Round
                    </button>
                @endif
            </div>

            <!-- Main Content Grid - Optimized for iPad Pro -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Left Column: Team Controls (always visible) -->
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
                                            Score: <span
                                                class="font-bold text-green-400">${{ number_format($team->score) }}</span>
                                        </div>
                                    </div>

                                    <!-- Control Buttons -->
                                    <div class="flex gap-2">
                                        <!-- Select as Current Team -->
                                        <button wire:click="selectCurrentTeam({{ $team->id }})"
                                                class="cursor-pointer p-3 rounded-lg transition-all hover:scale-105
                                                {{ $currentTeam && $currentTeam->id === $team->id
                                                    ? 'bg-yellow-500 text-slate-900 hover:bg-yellow-600'
                                                    : 'bg-slate-700 hover:bg-slate-600 text-white' }}"
                                                title="{{ $currentTeam && $currentTeam->id === $team->id ? 'Click to deselect' : 'Set as active team' }}">
                                            @if($currentTeam && $currentTeam->id === $team->id)
                                                <!-- Check Circle Icon (Active) -->
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @else
                                                <!-- User Icon (Set Active) -->
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                                </svg>
                                            @endif
                                        </button>

                                        <!-- Trigger Buzzer -->
                                        <button wire:click="triggerBuzzer({{ $team->id }})"
                                                class="cursor-pointer p-3 rounded-lg transition-all hover:scale-105 bg-purple-600 hover:bg-purple-700 text-white"
                                                title="Trigger buzzer sound for {{ $team->name }}">
                                            <!-- Bell Alert Icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                 stroke-width="2" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right Column: Game Board or Lightning Round Status -->
                <div class="xl:col-span-2 bg-slate-800/50 backdrop-blur-lg rounded-xl p-6 border border-slate-700">
                    @if($game->status === 'lightning_round')
                        <!-- Lightning Round Status -->
                        <div class="text-center mb-6">
                            <h1 class="text-4xl font-black text-purple-400 mb-2">⚡ LIGHTNING ROUND ⚡</h1>
                            <p class="text-xl text-purple-300">Control the rapid-fire round</p>
                        </div>

                        @if($game->lightningQuestions->count() > 0)
                            @php
                                $currentQuestion = $game->lightningQuestions->where('is_current', true)->first();
                                $questionsRemaining = $game->lightningQuestions->where('is_answered', false)->count();
                                $totalQuestions = $game->lightningQuestions->count();
                                $answeredQuestions = $game->lightningQuestions->where('is_answered', true)->count();
                                $currentQuestionNumber = $answeredQuestions + 1;
                            @endphp

                            @if($currentQuestion)
                                <div class="mb-6 p-6 bg-purple-900/30 rounded-xl border border-purple-500">
                                    <div class="flex justify-between items-center mb-2">
                                        <h3 class="text-lg text-purple-300">Current Question:</h3>
                                        <span class="text-lg font-bold text-yellow-400">Question {{ $currentQuestionNumber }} of {{ $totalQuestions }}</span>
                                    </div>
                                    <p class="text-2xl font-bold text-white mb-4">{{ $currentQuestion->question_text }}</p>
                                    <p class="text-lg text-gray-400">Answer: <span
                                            class="text-green-400">{{ $currentQuestion->answer_text }}</span></p>
                                </div>

                                <!-- Lightning Round Controls -->
                                @if($currentTeam)
                                    <!-- Team has buzzed in - show team name and correct/wrong buttons -->
                                    <div class="text-center mb-4">
                                        <p class="text-lg text-yellow-400">{{ $currentTeam->name }} buzzed in!</p>
                                    </div>
                                @endif

                                <div class="flex justify-center gap-4 mt-6">
                                    @if($currentTeam)
                                        <button wire:click="markLightningCorrect"
                                                class="cursor-pointer px-6 py-3 bg-green-600 hover:bg-green-700 rounded-lg font-bold text-white transition-all hover:scale-105">
                                            ✓ Correct (+$200)
                                        </button>
                                        <button wire:click="markLightningIncorrect"
                                                class="cursor-pointer px-6 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-bold text-white transition-all hover:scale-105">
                                            ✗ Wrong
                                        </button>
                                    @else
                                        <!-- No team has buzzed - show waiting message -->
                                        <p class="text-gray-400 self-center">Waiting for teams to buzz in...</p>
                                    @endif

                                    <!-- Skip/Next buttons always available -->
                                    <button wire:click="skipLightningQuestion"
                                            class="cursor-pointer px-6 py-3 bg-gray-600 hover:bg-gray-700 rounded-lg font-bold text-white transition-all">
                                        Skip Question
                                    </button>
                                    <button wire:click="nextLightningQuestion"
                                            class="cursor-pointer px-6 py-3 bg-purple-600 hover:bg-purple-700 rounded-lg font-bold text-white transition-all hover:scale-105">
                                        → Next Question
                                    </button>
                                </div>
                            @else
                                <div class="text-center p-6 bg-gray-800/50 rounded-xl">
                                    <p class="text-xl text-gray-400">Lightning Round Complete!</p>
                                </div>
                            @endif
                        @else
                            <div class="text-center p-6 bg-gray-800/50 rounded-xl">
                                <p class="text-xl text-gray-400">Setting up lightning round questions...</p>
                            </div>
                        @endif
                    @else
                        <!-- Regular Game Board -->
                        <!-- All 6 Categories in One Row -->
                        <div class="grid grid-cols-6 gap-3 mb-4">
                            @foreach($categories as $category)
                                <div class="text-base font-bold text-center p-4 bg-blue-800/50 rounded text-yellow-400">
                                    {{ $category->name }}
                                </div>
                            @endforeach
                        </div>

                        <!-- All Clues in 6 Columns -->
                        <div class="grid grid-cols-6 gap-3">
                            @foreach([100, 300, 500, 1000] as $value)
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
                    @endif
                </div>
            </div>

            <!-- Clue Display Modal - Full Width Below -->
            <div class="mt-6">
                @if($showClueModal && $selectedClue)
                    <!-- Daily Double Wager -->
                    @if($showDailyDoubleWager)
                        @if($currentTeam)
                            <div
                                class="bg-gradient-to-br from-yellow-600/20 to-orange-600/20 backdrop-blur-lg rounded-xl p-8 border-2 border-yellow-400">
                                <h2 class="text-3xl font-black text-yellow-400 mb-6">DAILY DOUBLE!</h2>
                                <div class="mb-6">
                                    <p class="text-lg text-gray-300">{{ $currentTeam->name }}'s Wager</p>
                                    <p class="text-sm text-gray-400 mt-1">
                                        Current Score: ${{ number_format($currentTeam->score) }}
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        Maximum Wager:
                                        ${{ number_format($currentTeam->score > 0 ? $currentTeam->score : 400) }}
                                    </p>
                                </div>
                                <div class="grid grid-cols-4 gap-4">
                                    @foreach($wagerOptions as $amount)
                                        <button wire:click="setDailyDoubleWager({{ $amount }})"
                                                class="cursor-pointer px-6 py-6 bg-blue-600 hover:bg-blue-700 rounded-lg font-bold text-2xl transition-all hover:scale-105
                                                {{ $amount == $currentTeam->score && $currentTeam->score > 0 ? 'ring-2 ring-yellow-400' : '' }}">
                                            ${{ number_format($amount) }}
                                            @if($amount == $currentTeam->score && $currentTeam->score > 0)
                                                <span class="block text-xs mt-1">True DD!</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div
                                class="bg-gradient-to-br from-yellow-600/20 to-orange-600/20 backdrop-blur-lg rounded-xl p-8 border-2 border-yellow-400">
                                <h2 class="text-3xl font-black text-yellow-400 mb-6 animate-pulse">DAILY DOUBLE!</h2>
                                <p class="text-2xl mb-6 text-red-400">Please select a team first before revealing Daily
                                    Double!</p>
                            </div>
                        @endif
                    @elseif($selectedClue)
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
                                        <p class="text-2xl font-semibold text-green-400">{{ $selectedClue->answer_text }}</p>
                                    </div>
                                </div>

                                <!-- Right Side: Controls -->
                                <div>

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
                                                class="cursor-pointer px-6 py-8 bg-green-600 hover:bg-green-700 rounded-lg font-bold text-2xl transition-all hover:scale-105">
                                            ✓ Correct
                                        </button>
                                        <button wire:click="markIncorrect"
                                                class="cursor-pointer px-6 py-8 bg-red-600 hover:bg-red-700 rounded-lg font-bold text-2xl transition-all hover:scale-105">
                                            ✗ Incorrect
                                        </button>
                                    </div>

                                    <!-- Skip/Close -->
                                    <div class="mt-4 grid grid-cols-2 gap-4">
                                        <button wire:click="skipClue"
                                                class="cursor-pointer px-6 py-4 bg-gray-600 hover:bg-gray-700 rounded-lg font-bold text-xl transition-all">
                                            Skip Clue
                                        </button>
                                        <button wire:click="closeClue"
                                                class="cursor-pointer px-6 py-4 bg-slate-600 hover:bg-slate-700 rounded-lg font-bold text-xl transition-all">
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
                        <div
                            class="flex items-center justify-between p-4 rounded-lg border border-slate-600 bg-slate-900/50">
                            <div>
                                <div class="font-bold text-lg">{{ $team->name }}</div>

                                <div
                                    class="text-3xl font-bold {{ ($team->score < 0) ? 'text-red-400' : 'text-green-400' }}">
                                    ${{ number_format($team->score) }}</div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <button wire:click="adjustScore({{ $team->id }}, 100)"
                                        class="cursor-pointer px-4 py-3 bg-green-600 hover:bg-green-700 rounded text-lg font-bold">
                                    +100
                                </button>
                                <button wire:click="adjustScore({{ $team->id }}, -100)"
                                        class="cursor-pointer px-4 py-3 bg-red-600 hover:bg-red-700 rounded text-lg font-bold">
                                    -100
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

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
