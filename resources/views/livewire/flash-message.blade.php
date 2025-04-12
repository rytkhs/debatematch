<div x-data="{ show: @entangle('show').live, timeout: null }"
     x-show="show"
     x-init="$watch('show', value => {
         if (value) {
             clearTimeout(timeout);
             timeout = setTimeout(() => {
                 show = false;
                 $wire.hideFlashMessage();
             }, 5000);
         }
     });
     $wire.on('start-flash-message-timeout', () => {
         clearTimeout(timeout);
         timeout = setTimeout(() => {
             show = false;
             $wire.hideFlashMessage();
         }, 5000);
     })"
     class="fixed top-16 right-4 z-50 max-w-sm transform transition-all duration-300 ease-out"
     x-transition:enter="transform ease-out duration-300 transition"
     x-transition:enter-start="translate-y-2 opacity-0"
     x-transition:enter-end="translate-y-0 opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    @if ($show && $type === 'success')
        <div class="flex items-center p-4 mb-4 text-white bg-emerald-500 rounded-lg shadow-md">
            <span class="mr-2 text-lg"><i class="fa-solid fa-circle-check"></i></span>
            <div class="ml-2 text-sm font-medium">{{ $message }}</div>
            <button type="button" wire:click="hideFlashMessage" class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-emerald-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
                <span class="sr-only">{{ __('messages.close') }}</span>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if ($show && $type === 'error')
        <div class="flex items-center p-4 mb-4 text-white bg-red-500 rounded-lg shadow-md">
            <span class="mr-2 text-lg"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="ml-2 text-sm font-medium">{{ $message }}</div>
            <button type="button" wire:click="hideFlashMessage" class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-red-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
                <span class="sr-only">{{ __('messages.close') }}</span>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if ($show && $type === 'info')
        <div class="flex items-center p-4 mb-4 text-white bg-blue-500 rounded-lg shadow-md">
            <span class="mr-2 text-lg"><i class="fa-solid fa-circle-info"></i></span>
            <div class="ml-2 text-sm font-medium">{{ $message }}</div>
            <button type="button" wire:click="hideFlashMessage" class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-blue-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
                <span class="sr-only">{{ __('messages.close') }}</span>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if ($show && $type === 'warning')
        <div class="flex items-center p-4 mb-4 text-white bg-yellow-500 rounded-lg shadow-md">
            <span class="mr-2 text-lg"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="ml-2 text-sm font-medium">{{ $message }}</div>
            <button type="button" wire:click="hideFlashMessage" class="ml-auto -mx-1.5 -my-1.5 text-white hover:text-white focus:ring-2 focus:ring-yellow-400 p-1.5 inline-flex h-8 w-8 rounded-lg">
                <span class="sr-only">{{ __('messages.close') }}</span>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif
</div>
