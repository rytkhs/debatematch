<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto max-w-7xl p-4 sm:p-6">
            <!-- ヘッダー部分 -->
            <div class="flex justify-between items-center mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-primary">
                    <span class="material-icons-outlined align-middle mr-1 sm:mr-2 text-base sm:text-normal">preview</span>
                    ルーム詳細
                </h1>
                <a href="{{ route('rooms.index') }}" class="flex items-center text-sm sm:text-base text-gray-600 hover:text-primary transition-colors">
                    <span class="material-icons-outlined mr-1 text-sm sm:text-normal">arrow_back</span>
                    ルーム一覧へ戻る
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">
                <!-- 左側：ルーム情報 -->
                <div class="md:col-span-8 space-y-4 sm:space-y-6">
                    <!-- ルーム情報カード -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
                        <div class="flex items-start justify-between mb-3 sm:mb-4">
                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <span class="material-icons-outlined text-primary text-xl sm:text-2xl">meeting_room</span>
                                <div>
                                    <p class="text-xs sm:text-sm text-gray-500">ルーム名</p>
                                    <h2 class="text-base sm:text-lg font-semibold">{{ $room->name }}</h2>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <div class="px-1.5 sm:px-2 py-0.5 sm:py-1 bg-gray-100 rounded-md text-xs sm:text-sm text-gray-600 flex items-center">
                                    <span class="material-icons-outlined text-gray-400 mr-0.5 sm:mr-1 text-xs sm:text-sm">language</span>
                                    {{ $room->language === 'japanese' ? '日本語' : 'English' }}
                                </div>
                                <div class="flex items-center">
                                    <span class="material-icons-outlined text-gray-400 mr-0.5 sm:mr-1 text-xs sm:text-sm">person</span>
                                    <span class="text-xs sm:text-sm text-gray-500">ホスト: {{ $room->creator->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 sm:mt-5 mb-4 sm:mb-6">
                            <div class="bg-primary-light rounded-lg p-3 sm:p-4 border-l-4 border-primary">
                                <p class="text-xs sm:text-sm text-gray-500 mb-1">論題</p>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-800">{{ $room->topic }}</h3>
                            </div>
                        </div>

                        @if($room->remarks)
                        <div class="bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
                            <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2 flex items-center">
                                <span class="material-icons-outlined text-gray-400 mr-1 text-xs sm:text-sm">info</span>備考
                            </h4>
                            <p class="text-xs sm:text-sm text-gray-600 whitespace-pre-wrap">{{ $room->remarks }}</p>
                        </div>
                        @endif

                        <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-200 flex justify-end">
                            <livewire:rooms.status :room="$room" />
                        </div>
                    </div>

                    <!-- ディベート形式 -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <button type="button" class="w-full text-left focus:outline-none group transition-all" onclick="toggleFormat('preview-format-content')">
                            <h3 class="text-base sm:text-lg font-semibold text-gray-800 flex items-center justify-between">
                                <span class="flex items-center">
                                    <span class="material-icons-outlined text-primary mr-1 sm:mr-2 text-sm sm:text-normal">format_list_numbered</span>
                                    フォーマット
                                </span>
                                <span class="material-icons-outlined text-gray-400 group-hover:text-primary transition-colors format-preview-icon text-sm sm:text-normal">expand_less</span>
                            </h3>
                        </button>

                        <div id="preview-format-content" class="mt-3 sm:mt-4 transition-all duration-300 transform opacity-100">
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
                                                    <span class="px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-full {{ $turn['speaker'] === 'affirmative' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
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

                <!-- 右側：ディベーターと参加アクション -->
                <div class="md:col-span-4 space-y-4 sm:space-y-6">
                    <!-- ディベーターカード -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                            <span class="material-icons-outlined text-primary mr-1 sm:mr-2 text-sm sm:text-normal">groups</span>
                            ディベーター
                        </h3>
                        <livewire:rooms.participants :room="$room" />
                    </div>

                    <!-- 参加セクション -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                            <span class="material-icons-outlined text-primary mr-1 sm:mr-2 text-sm sm:text-normal">how_to_reg</span>
                            ディベートに参加
                        </h3>

                        @if($room->status === 'waiting')
                            <div class="p-3 sm:p-4 bg-blue-50 rounded-lg border border-blue-200 mb-3 sm:mb-4">
                                <div class="flex items-center text-blue-700 text-xs sm:text-sm">
                                    <span class="material-icons-outlined mr-1 sm:mr-2 text-xs sm:text-sm">info</span>
                                    <p>ディベートに参加するにはサイドを選択してください</p>
                                </div>
                            </div>

                            <div class="space-y-2 sm:space-y-3">
                                <form action="{{ route('rooms.join', $room) }}" method="POST" onSubmit="return confirmJoin(event);">
                                    @csrf
                                    <button type="submit" name="side" value="affirmative"
                                        class="w-full flex items-center justify-center py-2 sm:py-3 px-3 sm:px-4 rounded-lg transition-all hover:scale-105 text-xs sm:text-sm
                                        {{ $room->users->where('pivot.side', 'affirmative')->count() ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-500' : 'bg-green-100 text-green-800 hover:bg-green-200' }}"
                                        {{ $room->users->where('pivot.side', 'affirmative')->count() ? 'disabled' : '' }}>
                                        <span class="material-icons-outlined mr-1 sm:mr-2 text-xs sm:text-sm">add_circle</span>
                                        肯定側で参加する
                                    </button>
                                </form>

                                <form action="{{ route('rooms.join', $room) }}" method="POST" onSubmit="return confirmJoin(event);">
                                    @csrf
                                    <button type="submit" name="side" value="negative"
                                        class="w-full flex items-center justify-center py-2 sm:py-3 px-3 sm:px-4 rounded-lg transition-all hover:scale-105 text-xs sm:text-sm
                                        {{ $room->users->where('pivot.side', 'negative')->count() ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-500' : 'bg-red-100 text-red-800 hover:bg-red-200' }}"
                                        {{ $room->users->where('pivot.side', 'negative')->count() ? 'disabled' : '' }}>
                                        <span class="material-icons-outlined mr-1 sm:mr-2 text-xs sm:text-sm">add_circle</span>
                                        否定側で参加する
                                    </button>
                                </form>
                            </div>
                        @elseif($room->status === 'ready' || $room->status === 'debating')
                            <div class="p-3 sm:p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                <div class="flex items-center text-yellow-700 justify-center text-xs sm:text-sm">
                                    <span class="material-icons-outlined mr-1 sm:mr-2 text-xs sm:text-sm">info</span>
                                    <p>このルームはすでに満員です</p>
                                </div>
                            </div>
                        @else
                            <div class="p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center text-gray-700 justify-center text-xs sm:text-sm">
                                    <span class="material-icons-outlined mr-1 sm:mr-2 text-xs sm:text-sm">block</span>
                                    <p>このルームには参加できません</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmJoin(event) {
            if (!confirm('このサイドでディベートに参加しますか？')) {
                event.preventDefault();
                return false;
            }
            return true;
        }

        // フォーマットアコーディオンの開閉処理
        function toggleFormat(contentId) {
            const content = document.getElementById(contentId);
            const icon = document.querySelector('.format-preview-icon');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                content.classList.add('opacity-100');
                icon.textContent = 'expand_less';
            } else {
                content.classList.remove('opacity-100');
                content.classList.add('opacity-0');
                setTimeout(() => {
                    content.classList.add('hidden');
                    icon.textContent = 'expand_more';
                }, 200);
            }
        }
    </script>
</x-app-layout>
