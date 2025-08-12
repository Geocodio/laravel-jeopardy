<div class="flex flex-wrap justify-center gap-4 mb-8" x-data="{ scores: @entangle('teams') }">
    @foreach($teams as $index => $team)
        <div 
            class="relative group"
            x-data="{ 
                isActive: {{ $activeTeamId == $team['id'] ? 'true' : 'false' }},
                score: {{ $team['score'] }},
                prevScore: {{ $team['score'] }}
            }"
            x-init="
                $watch('scores', (newScores) => {
                    const newTeam = newScores.find(t => t.id === {{ $team['id'] }});
                    if (newTeam && newTeam.score !== score) {
                        prevScore = score;
                        score = newTeam.score;
                        // Trigger animation
                        $el.querySelector('.score-change').classList.add('animate-ping');
                        setTimeout(() => {
                            $el.querySelector('.score-change').classList.remove('animate-ping');
                        }, 1000);
                    }
                })
            ">
            
            <!-- Glow effect for active team -->
            <div 
                class="absolute -inset-1 rounded-2xl blur-lg transition-all duration-500"
                :class="isActive ? 'opacity-75' : 'opacity-0'"
                :style="`background: linear-gradient(45deg, {{ $team['color_hex'] }}, transparent)`">
            </div>
            
            <!-- Team Card -->
            <div 
                class="relative backdrop-blur-lg rounded-2xl p-6 border-2 transition-all duration-500 transform"
                :class="[
                    isActive 
                        ? 'bg-white/20 scale-110 shadow-2xl' 
                        : 'bg-black/30 hover:bg-black/40 hover:scale-105',
                    score < 0 ? 'border-red-500/50' : 'border-white/20'
                ]"
                :style="`border-color: ${isActive ? '{{ $team['color_hex'] }}' : ''}`">
                
                <!-- Team Name -->
                <h3 
                    class="text-lg font-bold mb-3 tracking-wider uppercase transition-all duration-300"
                    :style="`color: {{ $team['color_hex'] }}`">
                    {{ $team['name'] }}
                    
                    <!-- Active Indicator -->
                    <span 
                        x-show="isActive"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-0"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="ml-2 inline-block w-2 h-2 bg-green-400 rounded-full animate-pulse">
                    </span>
                </h3>
                
                <!-- Score Display -->
                <div class="relative">
                    <!-- Score Change Animation -->
                    <div class="score-change absolute inset-0 rounded-lg opacity-0"></div>
                    
                    <!-- Score Value -->
                    <div 
                        class="text-3xl font-black transition-all duration-300"
                        :class="[
                            score < 0 
                                ? 'text-red-400' 
                                : score > prevScore 
                                    ? 'text-green-400' 
                                    : 'text-white'
                        ]">
                        <span class="text-sm">$</span>
                        <span x-text="Math.abs(score).toLocaleString()"></span>
                        <span x-show="score < 0" class="text-red-400">-</span>
                    </div>
                    
                    <!-- Score Change Indicator -->
                    <div 
                        x-show="score !== prevScore"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-500"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute -top-6 right-0 text-sm font-bold"
                        :class="score > prevScore ? 'text-green-400' : 'text-red-400'">
                        <span x-text="score > prevScore ? '+' : '-'"></span>
                        <span x-text="Math.abs(score - prevScore)"></span>
                    </div>
                </div>
                
                <!-- Position Badge -->
                @if($loop->first)
                    <div class="absolute -top-3 -right-3 bg-gradient-to-r from-yellow-400 to-orange-400 text-black text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                        👑 1st
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>