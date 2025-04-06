<x-show-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto max-w-7xl p-4 sm:p-6">
            <!-- ヘッダー部分 -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-primary">
                    <span class="material-icons-outlined align-middle mr-1 sm:mr-2 text-base sm:text-xl">forum</span>
                    ルーム
                </h1>
                <div class="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto justify-end">
                    <livewire:rooms.status :room="$room" />
                    <form id="exit-form" action="{{ route('rooms.exit', $room) }}" method="POST"
                        onSubmit="return confirmExit(event, {{ $isCreator }});">
                        @csrf
                        <button type="submit" class="btn-danger flex items-center px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm rounded-lg transition-all hover:scale-105">
                            <span class="material-icons-outlined mr-1 text-sm">exit_to_app</span>退出する
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">
                <!-- 左側：ルーム情報 -->
                <div class="md:col-span-8 space-y-4 sm:space-y-6">
                    <!-- ルーム情報カード -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between mb-3 sm:mb-4 gap-2 sm:gap-0">
                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <span class="material-icons-outlined text-primary text-xl sm:text-2xl">meeting_room</span>
                                <div>
                                    <p class="text-xs sm:text-sm text-gray-500">ルーム名</p>
                                    <h2 class="text-base sm:text-lg font-semibold">{{ $room->name }}</h2>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <div class="px-1.5 py-0.5 sm:px-2 sm:py-1 bg-gray-100 rounded-md text-xs sm:text-sm text-gray-600 flex items-center">
                                    <span class="material-icons-outlined text-gray-400 mr-1 text-xs sm:text-sm">language</span>
                                    {{ $room->language === 'japanese' ? '日本語' : 'English' }}
                                </div>
                                <div class="flex items-center">
                                    <span class="material-icons-outlined text-gray-400 mr-1 text-xs sm:text-sm">person</span>
                                    <span class="text-xs sm:text-sm text-gray-500">ホスト: {{ $room->creator->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 sm:mt-5 mb-4 sm:mb-6">
                            <div class="bg-primary-light rounded-lg p-3 sm:p-4 border-l-4 border-primary">
                                <p class="text-xs sm:text-sm text-gray-500 mb-1">論題</p>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-800">{{ $room->topic }}</h3>
                            </div>
                        </div>

                        @if($room->remarks)
                        <div class="bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
                            <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2 flex items-center">
                                <span class="material-icons-outlined text-gray-400 mr-1 text-xs sm:text-sm">info</span>備考
                            </h4>
                            <p class="text-xs sm:text-sm text-gray-600 whitespace-pre-wrap">{{ $room->remarks }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- ディベート形式 -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <button type="button" class="w-full text-left focus:outline-none group transition-all" onclick="toggleFormat('format-content')">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 flex items-center justify-between">
                                <span class="flex items-center">
                                    <span class="material-icons-outlined text-primary mr-1.5 sm:mr-2 text-sm sm:text-base">format_list_numbered</span>
                                    フォーマット
                                </span>
                                <span class="material-icons-outlined text-gray-400 group-hover:text-primary transition-colors format-icon text-sm sm:text-base">expand_less</span>
                            </h3>
                        </button>

                        <div id="format-content" class="mt-3 sm:mt-4 transition-all duration-300 transform opacity-100">
                            <div class="pt-2 border-t border-gray-100">
                                <h4 class="text-sm sm:text-md font-medium text-gray-600 mb-2 sm:mb-3">
                                    {{ $room->getFormatName() }} 形式
                                </h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 border border-gray-100 rounded-lg">
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @php
                                                $formatData = $room->custom_format_settings ?? config('debate.formats')[$room->format_type] ?? [];
                                            @endphp

                                            @foreach($formatData as $index => $turn)
                                            <tr class="{{ $turn['speaker'] === 'affirmative' ? 'bg-green-50' : 'bg-red-50' }}">
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">{{ $index }}</td>
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">
                                                    <span class="px-1.5 py-0.5 sm:px-2 sm:py-1 rounded-full {{ $turn['speaker'] === 'affirmative' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $turn['speaker'] === 'affirmative' ? '肯定側' : '否定側' }}
                                                    </span>
                                                </td>
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">{{ $turn['name'] }}</td>
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">{{ floor($turn['duration'] / 60) }}分</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 右側：ディベーターとアクション -->
                <div class="md:col-span-4 space-y-4 sm:space-y-6">
                    <!-- ディベーターカード -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                            <span class="material-icons-outlined text-primary mr-1.5 sm:mr-2 text-sm sm:text-base">groups</span>
                            ディベーター
                        </h3>
                        <livewire:rooms.participants :room="$room" />
                    </div>

                    <!-- アクションセクション -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                            <span class="material-icons-outlined text-primary mr-1.5 sm:mr-2 text-sm sm:text-base">play_circle</span>
                            ディベート開始
                        </h3>
                        <livewire:rooms.start-debate-button :room="$room" :isCreator="$isCreator" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 接続状態アラート -->
    <livewire:connection-status :room="$room" />

    <!-- カウントダウンオーバーレイ -->
    <div id="countdown-overlay" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-5 sm:p-8 text-center max-w-md mx-auto">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-3 sm:mb-4">ディベートを開始します</h2>
            <div class="w-16 h-16 sm:w-20 sm:h-20 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4 sm:mb-6 mx-auto"></div>
            <p class="text-sm sm:text-base text-gray-600 mb-1.5 sm:mb-2">まもなくディベートページに移動します</p>
            <div class="text-xs sm:text-sm text-gray-500">5秒後にディベートページへ移動します...</div>
        </div>
    </div>

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

        // フォーマットアコーディオンの開閉処理
        function toggleFormat(contentId) {
            const content = document.getElementById(contentId);
            const icon = document.querySelector('.format-icon');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                content.classList.add('opacity-100');
                icon.textContent = 'expand_less';
            } else {
                content.classList.add('opacity-0');
                setTimeout(() => {
                    content.classList.add('hidden');
                    icon.textContent = 'expand_more';
                }, 200);
                content.classList.remove('opacity-100');
            }
        }

        window.roomData = {
            roomId: {{ Js::from($room->id) }},
            isCreator: {{ Js::from($isCreator) }},
            authUserId: {{ Js::from(auth()->id()) }},
            pusherKey: {{ Js::from(config('broadcasting.connections.pusher.key')) }},
            pusherCluster: {{ Js::from(config('broadcasting.connections.pusher.options.cluster')) }}
        };

    </script>
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
    @vite('resources/js/rooms-show.js')
</x-show-layout>
