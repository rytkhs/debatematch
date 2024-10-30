<x-app-layout>

    <div class="container mx-auto px-4 py-12 font-sans bg-gradient-to-br from-indigo-100 to-purple-100 min-h-screen">
        <h1 class="text-4xl font-extrabold mb-12 text-center text-indigo-800">
            ディベートルーム
        </h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            @foreach ($rooms as $room)
            <div
                class="bg-white shadow-lg rounded-xl p-6 transition-all duration-300 hover:shadow-xl transform hover:-translate-y-1">
                <h2 class="text-2xl font-bold mb-3 text-indigo-700">{{ $room->name }}</h2>
                <p class="text-gray-700 mb-3 font-medium">テーマ: {{ $room->topic }}</p>
                <p class="text-sm mb-4 font-semibold {{ $room->status === 'waiting' ? 'text-gray-500' : ($room->status === 'ready' ? 'text-yellow-500' : ($room->status === 'debating' ? 'text-green-500' : 'text-blue-500')) }}">
                    @if ($room->status === 'waiting')
                        待機中
                    @elseif ($room->status === 'ready')
                        準備中
                    @elseif ($room->status === 'debating')
                        ディベート中
                    @else
                        終了
                    @endif
                <a href="{{ route('rooms.show', $room) }}"
                    class="w-full py-3 px-4 rounded-lg transition-colors duration-300 {{ $room->status === 'waiting' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-200 text-gray-500 cursor-not-allowed' }}"
                    {{ $room->status !== 'waiting' ? 'disabled' : '' }}>
                    {{ $room->status === 'waiting' ? '参加する' : 'ディベート中' }}
                </a>
            </div>
            @endforeach
        </div>

        <div class="text-center">
            <a href="{{ route('rooms.create') }}"
                class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-full transition-colors duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                新しいルームを作成
            </a>
        </div>
    </div>
</x-app-layout>
