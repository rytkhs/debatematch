<div class="p-3 pb-1 border-b border-gray-200 shadow-sm">
    <div class="flex space-x-4 overflow-auto">
        <!-- 全てのメッセージタブ -->
        <button wire:click="$set('activeTab', 'all')"
            class="px-1.5 mx-2 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap transition duration-200 focus:outline-none focus:text-primary focus:border-primary {{ $activeTab === 'all' ? 'text-primary border-b-2 border-primary' : '' }}"
        >
            全て
        </button>

        <!-- 各ターンのタブ -->
        @foreach ($turns as $turnNumber => $turnInfo)
        <button wire:click="$set('activeTab', '{{ $turnNumber }}')"
            class="px-1.5 mx-2 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap transition duration-300 focus:outline-none focus:text-primary focus:border-primary {{ $activeTab === (string) $turnNumber ? 'text-primary border-b-2 border-primary' : '' }}"
        >
            {{ $turnInfo['name'] }}
        </button>
        @endforeach
    </div>
</div>
