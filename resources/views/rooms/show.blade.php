<x-show-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto max-w-7xl p-6">
            <!-- ヘッダー部分 -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary">
                    <span class="material-icons-outlined align-middle mr-2">forum</span>
                    ルーム
                </h1>
                <div class="flex items-center space-x-4">
                    <livewire:rooms.status :room="$room" />
                    <form id="exit-form" action="{{ route('rooms.exit', $room) }}" method="POST"
                        onSubmit="return confirmExit(event, {{ $isCreator }});">
                        @csrf
                        <button type="submit" class="btn-danger flex items-center px-4 py-2 rounded-lg transition-all hover:scale-105">
                            <span class="material-icons-outlined mr-1">exit_to_app</span>退出する
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                <!-- 左側：ルーム情報 -->
                <div class="md:col-span-8 space-y-6">
                    <!-- ルーム情報カード -->
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <span class="material-icons-outlined text-primary text-2xl">meeting_room</span>
                                <div>
                                    <p class="text-sm text-gray-500">ルーム名</p>
                                    <h2 class="text-lg font-semibold">{{ $room->name }}</h2>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="px-2 py-1 bg-gray-100 rounded-md text-sm text-gray-600 flex items-center">
                                    <span class="material-icons-outlined text-gray-400 mr-1 text-sm">language</span>
                                    {{ $room->language === 'japanese' ? '日本語' : 'English' }}
                                </div>
                                <div class="flex items-center">
                                    <span class="material-icons-outlined text-gray-400 mr-1">person</span>
                                    <span class="text-sm text-gray-500">ホスト: {{ $room->creator->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 mb-6">
                            <div class="bg-primary-light rounded-lg p-4 border-l-4 border-primary">
                                <p class="text-sm text-gray-500 mb-1">論題</p>
                                <h3 class="text-xl font-bold text-gray-800">{{ $room->topic }}</h3>
                            </div>
                        </div>

                        @if($room->remarks)
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                                <span class="material-icons-outlined text-gray-400 mr-1 text-sm">info</span>備考
                            </h4>
                            <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ $room->remarks }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- ディベート形式 -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <button type="button" class="w-full text-left focus:outline-none group transition-all" onclick="toggleFormat('format-content')">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center justify-between">
                                <span class="flex items-center">
                                    <span class="material-icons-outlined text-primary mr-2">format_list_numbered</span>
                                    フォーマット
                                </span>
                                <span class="material-icons-outlined text-gray-400 group-hover:text-primary transition-colors format-icon">expand_less</span>
                            </h3>
                        </button>

                        <div id="format-content" class="mt-4 transition-all duration-300 transform opacity-100">
                            <div class="pt-2 border-t border-gray-100">
                                <h4 class="text-md font-medium text-gray-600 mb-3">
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
                                                <td class="px-4 py-2 text-sm">{{ $index }}</td>
                                                <td class="px-4 py-2 text-sm">
                                                    <span class="px-2 py-1 rounded-full {{ $turn['speaker'] === 'affirmative' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $turn['speaker'] === 'affirmative' ? '肯定側' : '否定側' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-2 text-sm">{{ $turn['name'] }}</td>
                                                <td class="px-4 py-2 text-sm">{{ floor($turn['duration'] / 60) }}分</td>
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
                <div class="md:col-span-4 space-y-6">
                    <!-- ディベーターカード -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <span class="material-icons-outlined text-primary mr-2">groups</span>
                            ディベーター
                        </h3>
                        <livewire:rooms.participants :room="$room" />
                    </div>

                    <!-- アクションセクション -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <span class="material-icons-outlined text-primary mr-2">play_circle</span>
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
        <div class="bg-white rounded-lg p-8 text-center max-w-md mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">ディベートを開始します</h2>
            <div class="w-20 h-20 border-4 border-primary border-t-transparent rounded-full animate-spin mb-6 mx-auto"></div>
            <p class="text-gray-600 mb-2">まもなくディベートページに移動します</p>
            <div class="text-sm text-gray-500">5秒後にディベートページへ移動します...</div>
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
