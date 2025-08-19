@props(['show' => false])

@if($show)
    <div class="absolute top-4 right-4">
        <div class="relative">
            <!-- Glow effect -->
            <div class="absolute inset-0 bg-green-500 rounded-full blur-xl opacity-60 animate-pulse"></div>

            <!-- Bell icon container -->
            <div class="relative bg-gradient-to-br from-green-400 to-emerald-500 rounded-full p-3 shadow-2xl">
                <!-- Bell icon -->
                <svg class="w-8 h-8 text-white animate-[ring_2s_ease-in-out_infinite]"
                     fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                </svg>

                <!-- Animated ring waves -->
                <div class="absolute inset-0 rounded-full border-2 border-green-400 animate-ping"></div>
            </div>
        </div>
    </div>
@endif