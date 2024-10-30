<x-app-layout>
    <div
        class="font-sans min-h-screen bg-gradient-to-br from-blue-100 to-purple-100 p-4 flex items-center justify-center">
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="p-8 border-b border-gray-200">
                <h1 class="text-3xl font-bold mb-6 text-gray-800">{{ $room->name }}</h1>
                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <p class="text-lg text-gray-700">
                        <span class="font-semibold text-blue-600">テーマ:</span> {{ $room->topic }}
                    </p>
                </div>
                <div class="mb-6">
                    <p class="font-semibold text-gray-700 mb-3">参加者:</p>
                    <div class="space-y-2">
                        @foreach($room->users as $participant)
                        <div class="flex items-center bg-gray-50 rounded-lg p-3">
                            <span
                                class="w-24 text-sm font-medium {{ $participant->pivot->side === 'affirmative' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $participant->pivot->side === 'affirmative' ? '肯定側' : '否定側' }}
                            </span>
                            <span class="text-gray-700">{{ $participant->name }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @livewire('room-status', ['room' => $room, 'isCreator' => $isCreator])

            <div class="p-8 flex justify-between items-center bg-gray-50">
                @if($isCreator && $room->users->count() === 2 && $room->status === 'waiting')
                <form action="{{ route('rooms.startDebate', $room) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition duration-300 ease-in-out transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        ディベート開始
                    </button>
                </form>
                @endif

                @if(!$room->users->contains(auth()->user()) && $room->users->count() < 2) <form
                    action="{{ route('rooms.joinRoom', $room) }}" method="POST">
                    @csrf
                    <button type="submit" name="side" value="affirmative"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        肯定側で参加
                    </button>
                    <button type="submit" name="side" value="negative"
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        否定側で参加
                    </button>
                    </form>
                    @endif

                    <form action="{{ route('rooms.exitRoom', $room) }}" method="POST"
                        onSubmit="return confirmExit(event, {{ $isCreator }});">
                        @csrf
                        <button type="submit"
                            class="bg-red-500 text-white px-8 py-3 rounded-lg hover:bg-red-600 transition duration-300 ease-in-out transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <i class="fas fa-sign-out-alt mr-2"></i>退出
                        </button>
                    </form>
            </div>
        </div>
    </div>
</x-app-layout>
<script>
    function confirmExit(event, isCreator) {
        let message = isCreator
            ? 'ルームを退出しますか？ルームは削除されます。'
            : 'ルームを退出しますか？';

        if (!confirm(message)) {
            event.preventDefault();
            return false;
        }
        return true;
    }
</script>
