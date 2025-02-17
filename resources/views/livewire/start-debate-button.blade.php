<div>
    @if($status === 'waiting')
        <!-- 参加者待ちのメッセージ（クリエイターのみ表示） -->
        @if($isCreator)
        <div class="mt-4 text-center">
            <div class="flex items-center justify-center space-x-2">
                <div class="w-4 h-4 border-2 border-blue-500 rounded-full animate-spin border-t-transparent">
                </div>
                <span class="text-blue-600 text-lg font-semibold">参加者を待っています...</span>
                <p class="text-lg text-gray-500">{{ $room->users->count() }}/2</p>
            </div>
        </div>
        @endif
    @elseif($status === 'ready')
        <!-- 参加者が揃った時のメッセージ -->
        @if($isCreator)
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-0">
                <span class="text-green-500 text-lg font-semibold">ディベーターが揃いました</span>
                <span class="text-green-500 text-lg font-semibold">ディベートを開始してください</span>
            </div>
        </div>
        @else
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-1">
                <span class="text-blue-500 text-md font-semibold">ディベートが開始されるまでお待ちください</span>
                <span class="text-blue-500 text-md font-semibold">ディベートが開始されると自動的にディベートページへ移動します</span>
            </div>
        </div>
        @endif
    @endif

    <!-- ボタンセクション -->
    <div class="mt-4 p-8 bg-gray-50">
        @if($isCreator)
        <div class="flex justify-center">
            <form action="{{ route('debate.start', $room) }}" method="POST">
                @csrf
                <button type="submit"
                onclick="return confirm('ディベートを開始します。よろしいですか？')"
                    class="bg-primary text-white text-lg px-6 py-2 m-4 rounded-md hover:bg-primary-dark transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-400 flex items-center {{ $status !== 'ready' ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $status !== 'ready' ? 'disabled' : '' }}>
                    <span class="material-icons-outlined">play_arrow</span>
                    ディベート開始
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
