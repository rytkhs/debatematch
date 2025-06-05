<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" @click="show = false"
    class="fixed top-20 right-4 z-[100] max-w-sm transform transition-all duration-500 ease-out"
    x-transition:enter="transform ease-out duration-500 transition"
    x-transition:enter-start="translate-x-full opacity-0 scale-95"
    x-transition:enter-end="translate-x-0 opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95 translate-x-full">

    @if (session('success'))
        <div class="relative overflow-hidden bg-gradient-to-r from-emerald-500 to-green-600 rounded-xl shadow-2xl border border-emerald-400/20 backdrop-blur-sm">
            <!-- Background pattern -->
            <div class="absolute inset-0 bg-white/10 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>

            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 h-1 bg-white/30 w-full">
                <div class="h-full bg-white/60 animate-[shrink_3s_linear_forwards]"></div>
            </div>

            <div class="relative flex items-start p-4 text-white">
                <div class="flex-shrink-0 mr-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-white/20 rounded-full">
                        <i class="fa-solid fa-check text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold leading-tight">{{ session('success') }}</div>
                </div>
                <button type="button" @click="show = false"
                        class="flex-shrink-0 ml-3 p-1.5 rounded-lg hover:bg-white/20 focus:ring-2 focus:ring-white/50 transition-colors duration-200">
                    <span class="sr-only">{{ __('messages.close') }}</span>
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="relative overflow-hidden bg-gradient-to-r from-red-500 to-rose-600 rounded-xl shadow-2xl border border-red-400/20 backdrop-blur-sm">
            <!-- Background pattern -->
            <div class="absolute inset-0 bg-white/10 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>

            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 h-1 bg-white/30 w-full">
                <div class="h-full bg-white/60 animate-[shrink_3s_linear_forwards]"></div>
            </div>

            <div class="relative flex items-start p-4 text-white">
                <div class="flex-shrink-0 mr-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-white/20 rounded-full">
                        <i class="fa-solid fa-exclamation text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold leading-tight">{{ session('error') }}</div>
                </div>
                <button type="button" @click="show = false"
                        class="flex-shrink-0 ml-3 p-1.5 rounded-lg hover:bg-white/20 focus:ring-2 focus:ring-white/50 transition-colors duration-200">
                    <span class="sr-only">{{ __('messages.close') }}</span>
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>
    @endif

    @if (session('info'))
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-2xl border border-blue-400/20 backdrop-blur-sm">
            <!-- Background pattern -->
            <div class="absolute inset-0 bg-white/10 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>

            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 h-1 bg-white/30 w-full">
                <div class="h-full bg-white/60 animate-[shrink_3s_linear_forwards]"></div>
            </div>

            <div class="relative flex items-start p-4 text-white">
                <div class="flex-shrink-0 mr-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-white/20 rounded-full">
                        <i class="fa-solid fa-info text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold leading-tight">{{ session('info') }}</div>
                </div>
                <button type="button" @click="show = false"
                        class="flex-shrink-0 ml-3 p-1.5 rounded-lg hover:bg-white/20 focus:ring-2 focus:ring-white/50 transition-colors duration-200">
                    <span class="sr-only">{{ __('messages.close') }}</span>
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>
    @endif

    @if (session('warning'))
        <div class="relative overflow-hidden bg-gradient-to-r from-amber-500 to-orange-600 rounded-xl shadow-2xl border border-amber-400/20 backdrop-blur-sm">
            <!-- Background pattern -->
            <div class="absolute inset-0 bg-white/10 bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.1),transparent_50%)]"></div>

            <!-- Progress bar -->
            <div class="absolute bottom-0 left-0 h-1 bg-white/30 w-full">
                <div class="h-full bg-white/60 animate-[shrink_3s_linear_forwards]"></div>
            </div>

            <div class="relative flex items-start p-4 text-white">
                <div class="flex-shrink-0 mr-3">
                    <div class="flex items-center justify-center w-8 h-8 bg-white/20 rounded-full">
                        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold leading-tight">{{ session('warning') }}</div>
                </div>
                <button type="button" @click="show = false"
                        class="flex-shrink-0 ml-3 p-1.5 rounded-lg hover:bg-white/20 focus:ring-2 focus:ring-white/50 transition-colors duration-200">
                    <span class="sr-only">{{ __('messages.close') }}</span>
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>
    @endif
</div>

<style>
@keyframes shrink {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}
</style>
