<div 
    x-data="{ 
        mounted: false,
        lightning: false,
        pulseEffect: false
    }" 
    x-init="
        mounted = true;
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
        <!-- Electric particles -->
        <div class="absolute inset-0">
            @for($i = 0; $i < 20; $i++)
                <div 
                    class="absolute w-1 h-1 bg-yellow-400 rounded-full animate-float-{{ $i % 5 }}"
                    style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ $i * 0.2 }}s">
                </div>
            @endfor
        </div>
        
        <!-- Lightning Strike Effect -->
        <div 
            x-show="lightning"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-gradient-to-b from-purple-400/20 via-yellow-400/10 to-transparent">
        </div>
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
                    <span class="absolute inset-0 blur-xl bg-gradient-to-r from-purple-400 via-pink-400 to-yellow-400 opacity-75 animate-pulse"></span>
                    <span class="relative bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-400 to-yellow-400">
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
                x-transition:enter-end="opacity-100 translate-y-0">
                <livewire:team-scoreboard :game-id="$game->id" />
            </div>
            
            
            <!-- Current Question with Electric Border -->
            <div 
                x-show="mounted"
                x-transition:enter="transition ease-out duration-700 delay-600"
                x-transition:enter-start="opacity-0 scale-95 rotate-3"
                x-transition:enter-end="opacity-100 scale-100 rotate-0"
                class="relative max-w-5xl mx-auto mb-8">
                
                <!-- Animated Border -->
                <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 via-pink-600 to-yellow-600 rounded-2xl blur opacity-75 animate-pulse"></div>
                
                <!-- Question Card -->
                <div class="relative backdrop-blur-xl bg-gradient-to-br from-purple-900/90 to-indigo-900/90 rounded-2xl p-12 border-2 border-white/20 shadow-2xl">
                    <div class="text-center">
                        <div class="text-lg text-purple-300 mb-4 uppercase tracking-wider">Quick! Answer this:</div>
                        <p class="text-3xl md:text-5xl font-bold text-white leading-tight animate-pulse">
                            {{ $currentQuestion->question_text }}
                        </p>
                    </div>
                    
                    <!-- Lightning Bolt Decorations -->
                    <div class="absolute -top-6 -left-6 text-4xl animate-bounce">⚡</div>
                    <div class="absolute -top-6 -right-6 text-4xl animate-bounce" style="animation-delay: 0.5s">⚡</div>
                    <div class="absolute -bottom-6 -left-6 text-4xl animate-bounce" style="animation-delay: 1s">⚡</div>
                    <div class="absolute -bottom-6 -right-6 text-4xl animate-bounce" style="animation-delay: 1.5s">⚡</div>
                </div>
            </div>
            
            <!-- Buzzer Order Display with Animation -->
            @if(count($buzzerOrder) > 0)
                <div 
                    x-show="mounted"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="max-w-4xl mx-auto mb-8">
                    <h3 class="text-xl text-center mb-4 text-purple-300">Buzzer Order:</h3>
                    <div class="flex justify-center gap-3 flex-wrap">
                        @foreach($buzzerOrder as $index => $teamId)
                            @php
                                $team = $game->teams->find($teamId);
                            @endphp
                            @if($team)
                                <div 
                                    class="relative group transform transition-all duration-300 hover:scale-110"
                                    style="animation-delay: {{ $index * 0.1 }}s">
                                    <div class="absolute -inset-0.5 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg blur opacity-50 group-hover:opacity-100 transition duration-200"></div>
                                    <div 
                                        class="relative px-4 py-2 rounded-lg backdrop-blur-lg bg-black/50 border"
                                        style="border-color: {{ $team->color_hex }}">
                                        <span class="font-bold" style="color: {{ $team->color_hex }}">
                                            {{ $index + 1 }}. {{ $team->name }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
            
            <!-- Current Answering Team with Spotlight Effect -->
            @if($currentAnsweringTeam)
                <div 
                    x-show="mounted"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 scale-0"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="text-center mb-8">
                    <div class="relative inline-block">
                        <div class="absolute inset-0 blur-3xl opacity-50" style="background-color: {{ $currentAnsweringTeam->color_hex }}"></div>
                        <div class="relative text-4xl font-black animate-pulse" style="color: {{ $currentAnsweringTeam->color_hex }}">
                            {{ $currentAnsweringTeam->name }} is answering!
                        </div>
                    </div>
                </div>
            @endif
            
        </div>
        
        <!-- Buzzer Listener -->
        <livewire:buzzer-listener :game-id="$game->id" />
        
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