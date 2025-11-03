<x-show-layout>
    <div class="min-h-screen bg-gray-50">
        <div class="container mx-auto max-w-7xl p-4 sm:p-6">
            <!-- ヘッダー部分 -->
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0 mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-primary">
                    <span class="material-icons-outlined align-middle mr-1 sm:mr-2 text-base sm:text-xl">forum</span>
                    {{ __('rooms.debate_room') }}
                </h1>
                <div class="flex items-center space-x-2 sm:space-x-4 w-full sm:w-auto justify-end">
                    <button
                    id="copy-room-url-btn"
                    type="button"
                    class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-primary-light hover:bg-primary hover:text-white text-primary text-xs sm:text-sm font-medium rounded-lg border border-primary/30 shadow-sm transition-all duration-150 ease-in-out"
                    data-original-text="{{ __('rooms.copy_room_url') }}"
                    data-copied-text="{{ __('rooms.room_url_copied') }}"
                    data-error-text="{{ __('rooms.room_url_copy_failed') }}"
                    data-room-url="{{ url()->route('rooms.show', $room) }}"
                    >
                        <span class="material-icons-outlined mr-1 text-sm copy-icon">content_copy</span>
                        <span class="button-text">{{ __('rooms.copy_room_url') }}</span>
                    </button>
                    <livewire:rooms.status :room="$room" />
                    <form id="exit-form" action="{{ route('rooms.exit', $room) }}" method="POST"
                        onSubmit="return confirmExit(event, {{ $isCreator }});">
                        @csrf
                        <button type="submit"
                            class="btn-danger flex items-center px-3 py-1.5 sm:px-4 sm:py-2 text-xs sm:text-sm rounded-lg transition-all hover:scale-105">
                            <span class="material-icons-outlined mr-1 text-sm">exit_to_app</span>{{
                            __('rooms.exit_room') }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">
                <!-- 左側：ルーム情報 -->
                <div class="md:col-span-8 space-y-4 sm:space-y-6">
                    <!-- ルーム情報カード -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
                        <div
                            class="flex flex-col sm:flex-row sm:items-start justify-between mb-3 sm:mb-4 gap-2 sm:gap-0">
                            <div class="flex items-center space-x-2 sm:space-x-3">
                                <span
                                    class="material-icons-outlined text-primary text-xl sm:text-2xl">meeting_room</span>
                                <div>
                                    <p class="text-xs sm:text-sm text-gray-500">{{ __('rooms.room_name') }}</p>
                                    <h2 class="text-base sm:text-lg font-semibold">{{ $room->name }}</h2>
                                </div>
                            </div>
                            <div
                                class="flex items-center space-x-2 sm:space-x-3">
                                <div
                                    class="px-1.5 py-0.5 sm:px-2 sm:py-1 bg-gray-100 rounded-md text-xs sm:text-sm text-gray-600 flex items-center">
                                    <span
                                        class="material-icons-outlined text-gray-400 mr-1 text-xs sm:text-sm">language</span>
                                    {{ $room->language === 'japanese' ? __('debates_format.japanese') : __('debates_format.english')
                                    }}
                                </div>
                                <!-- 証拠資料の有無を表示 -->
                                <div
                                    class="px-1.5 py-0.5 sm:px-2 sm:py-1 {{ $room->evidence_allowed ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600' }} rounded-md text-xs sm:text-sm flex items-center">
                                    <span
                                        class="material-icons-outlined mr-1 text-xs sm:text-sm">{{ $room->evidence_allowed ? 'fact_check' : 'no_sim' }}</span>
                                    {{ $room->evidence_allowed ? __('rooms.evidence_allowed') : __('rooms.evidence_not_allowed') }}
                                </div>
                                <div class="flex items-center">
                                    <span
                                        class="material-icons-outlined text-gray-400 mr-1 text-xs sm:text-sm">person</span>
                                    <span class="text-xs sm:text-sm text-gray-500">{{ __('rooms.host') }}: {{
                                        $room->creator->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 sm:mt-5 mb-4 sm:mb-6">
                            <div class="bg-primary-light rounded-lg p-3 sm:p-4 border-l-4 border-primary">
                                <p class="text-xs sm:text-sm text-gray-500 mb-1">{{ __('rooms.topic') }}</p>
                                <h3 class="text-lg sm:text-xl font-bold text-gray-800">{{ $room->topic }}</h3>
                            </div>
                        </div>

                        @if($room->remarks)
                        <div class="bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
                            <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-1.5 sm:mb-2 flex items-center">
                                <span
                                    class="material-icons-outlined text-gray-400 mr-1 text-xs sm:text-sm">info</span>{{
                                __('rooms.remarks') }}
                            </h4>
                            <p class="text-xs sm:text-sm text-gray-600 whitespace-pre-wrap">{{ $room->remarks }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- ディベート形式 -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <button type="button" class="w-full text-left focus:outline-none group transition-all"
                            onclick="toggleFormat('format-content')">
                            <h3
                                class="text-base sm:text-lg font-semibold text-gray-800 flex items-center justify-between">
                                <span class="flex items-center">
                                    <span
                                        class="material-icons-outlined text-primary mr-1.5 sm:mr-2 text-sm sm:text-base">format_list_numbered</span>
                                    {{ __('debates_format.format') }}
                                </span>
                                <span
                                    class="material-icons-outlined text-gray-400 group-hover:text-primary transition-colors format-icon text-sm sm:text-base">expand_less</span>
                            </h3>
                        </button>

                        <div id="format-content" class="mt-3 sm:mt-4 transition-all duration-300 transform opacity-100">
                            <div class="pt-2 border-t border-gray-100">
                                <h4 class="text-sm sm:text-md font-medium text-gray-600 mb-2 sm:mb-3">
                                    {{ $room->getFormatName() }} {{ __('debates_format.format_suffix') }}
                                </h4>
                                <div class="overflow-x-auto">
                                    <table
                                        class="min-w-full divide-y divide-gray-200 border border-gray-100 rounded-lg">
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            {{-- @php
                                            $formatData = $room->custom_format_settings ??
                                            config('debate.formats')[$room->format_type] ?? [];
                                            @endphp --}}

                                            @foreach($format as $index => $turn)
                                            @php
                                                $bgColorClass = 'bg-gray-50';
                                                if ($turn['speaker'] === 'affirmative') {
                                                    $bgColorClass = 'bg-green-50';
                                                } elseif ($turn['speaker'] === 'negative') {
                                                    $bgColorClass = 'bg-red-50';
                                                }
                                            @endphp
                                            <tr class="{{ $bgColorClass }}">
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">{{ $index }}
                                                </td>
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">
                                                     @php
                                                        $badgeClass = 'bg-gray-100 text-gray-800';
                                                        if ($turn['speaker'] === 'affirmative') {
                                                            $badgeClass = 'bg-green-100 text-green-800';
                                                        } elseif ($turn['speaker'] === 'negative') {
                                                            $badgeClass = 'bg-red-100 text-red-800';
                                                        }

                                                        $speakerName = $turn['speaker'];
                                                        if ($turn['speaker'] === 'affirmative') {
                                                            $speakerName = __('rooms.affirmative_side');
                                                        } elseif ($turn['speaker'] === 'negative') {
                                                            $speakerName = __('rooms.negative_side');
                                                        }
                                                    @endphp
                                                    <span
                                                        class="px-1.5 py-0.5 sm:px-2 sm:py-1 rounded-full {{ $badgeClass }}">
                                                        {{ $speakerName }}
                                                    </span>
                                                </td>
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">{{
                                                    $turn['name'] }}</td>
                                                <td class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm">{{
                                                    floor($turn['duration'] / 60) }}{{ __('debates_format.minute_suffix') }}
                                                </td>
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
                            <span
                                class="material-icons-outlined text-primary mr-1.5 sm:mr-2 text-sm sm:text-base">groups</span>
                            {{ __('debates_ui.debaters') }}
                        </h3>
                        <livewire:rooms.participants :room="$room" />
                    </div>

                    <!-- アクションセクション -->
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
                            <span
                                class="material-icons-outlined text-primary mr-1.5 sm:mr-2 text-sm sm:text-base">play_circle</span>
                            {{ __('rooms.start_debate') }}
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
    <div id="countdown-overlay"
        class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-5 sm:p-8 text-center max-w-md mx-auto">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-3 sm:mb-4">{{ __('rooms.starting_debate_title')
                }}</h2>
            <div
                class="w-16 h-16 sm:w-20 sm:h-20 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4 sm:mb-6 mx-auto">
            </div>
            <p class="text-sm sm:text-base text-gray-600 mb-1.5 sm:mb-2">{{ __('rooms.redirecting_to_debate_soon') }}
            </p>
            <div class="text-xs sm:text-sm text-gray-500">{{ __('rooms.redirecting_in_seconds', ['seconds' => 5]) }}
            </div>
        </div>
    </div>

    <script>
        // 翻訳文字列の設定
        window.translations = {
            confirmExitCreator: "{{ __('rooms.confirm_exit_creator') }}",
            confirmExitParticipant: "{{ __('rooms.confirm_exit_participant') }}",
            user_joined_room_title: "{{ __('rooms.user_joined_room_title') }}",
            user_left_room_title: "{{ __('rooms.user_left_room_title') }}",
            host_left_room_closed: "{{ __('rooms.host_left_room_closed') }}",
            debate_starting: "{{ __('rooms.debate_starting') }}",
            redirecting_in_seconds: "{{ __('rooms.redirecting_in_seconds', ['seconds' => ':seconds']) }}",
            connection_restored: "{{ __('rooms.connection_restored') }}",
            rooms: {
                user_joined_room: "{{ __('rooms.user_joined_room', ['name' => ':name']) }}",
                user_left_room: "{{ __('rooms.user_left_room', ['name' => ':name']) }}",
                host_left_room_closed: "{{ __('rooms.host_left_room_closed') }}",
                debate_starting_message: "{{ __('rooms.debate_starting_message') }}",
                redirecting_in_seconds: "{{ __('rooms.redirecting_in_seconds', ['seconds' => ':seconds']) }}",
            }
        };

        // ルームデータの設定
        window.roomData = {
            roomId: {{ Js::from($room->id) }},
            isCreator: {{ Js::from($isCreator) }},
            authUserId: {{ Js::from(auth()->id()) }},
            pusherKey: {{ Js::from(config('broadcasting.connections.pusher.key')) }},
            pusherCluster: {{ Js::from(config('broadcasting.connections.pusher.options.cluster')) }}
        };
    </script>
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
    @vite('resources/js/pages/room-show.js')
</x-show-layout>
