<div>
    <!-- オフライン通知 -->
    <div
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"
        wire:offline.class="absolute block"
        wire:offline.class.remove="hidden">
        <div class="bg-white rounded-lg p-8 max-w-md w-full shadow-xl">
            <div class="text-center">
                <div class="text-red-500 mb-4">
                    <span class="material-icons text-6xl">wifi_off</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('rooms.network_disconnected') }}</h3>
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
            </div>
        </div>
    </div>

    <!-- 相手の切断通知 -->
    <div
        class="fixed bottom-4 left-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded shadow-md z-30 {{ $isPeerOffline ? '' : 'hidden' }}">
        <div class="flex">
            <div class="flex-shrink-0">
                <span class="material-icons">warning</span>
            </div>
            <div class="ml-3">
                <p class="font-bold">{{ __('rooms.peer_connection_unstable') }}</p>
                <p>{{ __('rooms.waiting_for_reconnection') }}</p>
            </div>
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-yellow-100 text-yellow-500 rounded-lg focus:ring-2 focus:ring-yellow-400 p-1.5 hover:bg-yellow-200 inline-flex items-center justify-center h-8 w-8" wire:click="$set('isPeerOffline', false)">
                <span class="sr-only">{{ __('common.close') }}</span>
                <span class="material-icons text-sm">close</span>
            </button>
        </div>
    </div>
</div>
