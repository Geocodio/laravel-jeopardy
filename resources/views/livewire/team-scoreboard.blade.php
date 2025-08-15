<div class="flex flex-wrap justify-center gap-4 mb-8" wire:poll.3s="refreshScores">
    @foreach($teams as $index => $team)
        <div class="relative group">
            @php
                $isActive = $activeTeamId == $team['id'];
                $hasRecentChange = isset($recentScoreChanges[$team['id']]);
                $changeData = $hasRecentChange ? $recentScoreChanges[$team['id']] : null;
                $isCorrect = $changeData ? $changeData['correct'] : true;
                $changeAmount = $changeData ? $changeData['points'] : 0;
            @endphp

            <!-- Glow effect for active team -->
            <div
                class="absolute -inset-1 rounded-2xl blur-lg transition-all duration-500 {{ $isActive ? 'opacity-75' : 'opacity-0' }}"
                style="background: linear-gradient(45deg, {{ $team['color_hex'] }}, transparent)">
            </div>

            <!-- Team Card -->
            <div
                class="relative backdrop-blur-lg rounded-2xl p-6 border-2 transition-all duration-500 transform
                    {{ $isActive ? 'bg-black/50 scale-110 shadow-2xl' : 'bg-black/30 hover:bg-black/40 hover:scale-105' }}
                    {{ $team['score'] < 0 ? 'border-red-500/50' : 'border-white/20' }}"
                style="border-color: {{ $isActive ? $team['color_hex'] : '' }}">

                <!-- Team Name -->
                <h3
                    class="text-lg font-bold mb-3 tracking-wider uppercase transition-all duration-300"
                    style="color: {{ $team['color_hex'] }}">
                    {{ $team['name'] }}
                </h3>

                <!-- Score Display -->
                <div class="relative">
                    <!-- Score Value -->
                    <div
                        class="text-3xl font-black transition-all duration-300
                            {{ $team['score'] < 0 ? 'text-red-400' : 'text-white' }}
                            {{ $hasRecentChange && $isCorrect ? 'animate-pulse' : '' }}">
                        <span class="text-sm">$</span>
                        <span>{{ number_format(abs($team['score'])) }}</span>
                        @if($team['score'] < 0)
                            <span class="text-red-400">-</span>
                        @endif
                    </div>

                    <!-- Score Change Indicator -->
                    @if($hasRecentChange && $changeAmount !== 0)
                        <div
                            class="absolute -top-8 left-1/2 transform -translate-x-1/2 text-2xl font-black animate-bounce
                                {{ $isCorrect ? 'text-green-400' : 'text-red-400' }}"
                            wire:key="change-{{ $team['id'] }}-{{ $changeData['timestamp'] }}">
                            {{ ($changeAmount > 0 ? '+' : '') . '$' . number_format(abs($changeAmount)) }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
    // Listen for the clear animation event
    document.addEventListener('livewire:init', function () {
        Livewire.on('clear-score-animation-js', (event) => {
            setTimeout(() => {
                @this.clearScoreAnimation(event.teamId);
            }, 3000);
        });
    });
</script>
@endpush
