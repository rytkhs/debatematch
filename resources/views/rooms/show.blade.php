<x-app-layout>
    <div class="bg-gray-50 min-h-screen">
        <div class="container mx-auto p-6">
            <div class="flex justify-end mb-2">
                <form id="exit-form" action="{{ route('rooms.exit', $room) }}" method="POST"
                    onSubmit="return confirmExit(event, {{ $isCreator }});">
                    @csrf
                    <button type="submit" class="btn-danger">
                        <i class="fas fa-sign-out-alt mr-2"></i>退出する
                    </button>
                </form>
            </div>

            <!-- ルーム情報 -->
            <div class="bg-white rounded-xl shadow-lg px-8 py-5 mb-12 border border-gray-100">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-lg font-semibold text-gray-600 flex items-center mb-4">
                            <span class="material-icons-outlined text-primary">
                                chair
                            </span>
                            <span class="px-4 py-1 rounded-lg bg-gray-50">
                                {{ $room->name }}
                            </span>
                        </p>
                        <h1 class="text-2xl font-bold text-gray-900 mb-6 px-2 py-2 rounded-xl">
                            {{ $room->topic }}
                        </h1>
                    </div>
                </div>
                @if($room->remarks)
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">備考</h4>
                    <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $room->remarks }}</p>
                </div>
                @endif
                <!-- ボーダーとホスト情報 -->
                <div class="border-t border-gray-200 mt-4 pt-4">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <p class="text-md text-gray-600 flex items-center px-1 py-1 rounded-lg">
                                <span class="material-icons mr-2 text-[1.3rem] text-gray-500">
                                    person_outline
                                </span>
                                <span class="font-medium">
                                    {{ $room->creator->name }}
                                </span>
                            </p>
                        </div>
                        <div class="ml-4">
                            @livewire('room-status', ['room' => $room])
                        </div>
                    </div>
                </div>
            </div>

            <!-- ディベーターセクション -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <span class="material-icons-outlined mr-2">group</span>
                    ディベーター
                </h2>
                @livewire('room-participants', ['room' => $room])
            </div>
            @livewire('start-debate-button', ['room' => $room, 'isCreator' => $isCreator])
        </div>
    </div>
        @push('scripts')
        <script>
            const confirmExit = (event, isCreator) => {
                const message = isCreator
                    ? 'ルームを退出しますか？ルームは削除されます。'
                    : 'ルームを退出しますか？';
                if (!confirm(message)) {
                    event.preventDefault();
                    return false;
                }
                return true;
            };
        </script>
        @endpush
</x-app-layout>
