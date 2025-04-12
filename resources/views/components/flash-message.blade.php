<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" @click="show = false"
    class="fixed top-16 right-4 z-[100] max-w-sm transform transition-all duration-300 ease-out"
    x-transition:enter="transform ease-out duration-300 transition" x-transition:enter-start="translate-y-2 opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

    @if (session('success'))
    <div class="flex items-center p-4 mb-4 text-white bg-emerald-500 rounded-lg shadow-md">
        <span class="mr-2 text-lg"><i class="fa-solid fa-circle-check"></i></span>
        <div class="ml-2 text-sm font-medium">{{ session('success') }}</div>
        <button type="button" @click="show = false"
            class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-emerald-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
            <span class="sr-only">{{ __('messages.close') }}</span>
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    @if (session('error'))
    <div class="flex items-center p-4 mb-4 text-white bg-red-500 rounded-lg shadow-md">
        <span class="mr-2 text-lg"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <div class="ml-2 text-sm font-medium">{{ session('error') }}</div>
        <button type="button" @click="show = false"
            class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-red-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
            <span class="sr-only">{{ __('messages.close') }}</span>
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    @if (session('info'))
    <div class="flex items-center p-4 mb-4 text-white bg-sky-600 rounded-lg shadow-md">
        <span class="mr-2 text-lg"><i class="fa-solid fa-circle-info"></i></span>
        <div class="ml-2 text-sm font-medium">{{ session('info') }}</div>
        <button type="button" @click="show = false"
            class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-blue-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
            <span class="sr-only">{{ __('messages.close') }}</span>
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    @if (session('warning'))
    <div class="flex items-center p-4 mb-4 text-white bg-yellow-500 rounded-lg shadow-md">
        <span class="mr-2 text-lg"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <div class="ml-2 text-sm font-medium">{{ session('warning') }}</div>
        <button type="button" @click="show = false"
            class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-yellow-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
            <span class="sr-only">{{ __('messages.close') }}</span>
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif
</div>
