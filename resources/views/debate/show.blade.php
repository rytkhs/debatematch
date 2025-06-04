<x-show-layout>
    <div class="flex flex-col h-screen w-full overflow-hidden">
        <!-- ヘッダー -->
        <livewire:debates.header :debate="$debate" />

        <!-- メインコンテンツ: 2カラムレイアウト (モバイルとタブレットでは単一カラム) -->
        <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
            <!-- 左サイドバー: ディベート情報 -->
            <div class="w-full md:w-1/4 xl:w-1/5 md:border-r border-gray-200 bg-white md:overflow-y-auto shadow-md z-20 md:flex-shrink-0" id="left-sidebar">
                <!-- タブナビゲーション -->
                <div class="flex border-b border-gray-200">
                    <button id="participants-tab" class="flex-1 py-3 px-4 text-center border-b-2 border-primary text-primary font-medium">
                        {{ __('messages.debate_information_tab') }}
                    </button>
                    <button id="timeline-tab" class="flex-1 py-3 px-4 text-center text-gray-500 hover:text-gray-700">
                        {{ __('messages.timeline_tab') }}
                    </button>
                </div>

                <!-- 参加者パネル -->
                <div id="participants-panel" class="block">
                    <livewire:debates.participants :debate="$debate" />
                </div>
                <!-- タイムラインパネル -->
                <div id="timeline-panel" class="hidden">
                    <livewire:debates.timeline :debate="$debate" />
                </div>
            </div>

            <!-- メインコンテンツ: チャットエリア -->
            <div class="flex-1 flex flex-col relative h-full w-full md:w-3/4">
                <!-- チャットメッセージ表示エリア -->
                <div class="flex-1 overflow-y-auto bg-pattern" id="chat-container">
                    <livewire:debates.chat :debate="$debate" />
                </div>

                <!-- メッセージ入力エリア - 常に下部に固定 -->
                <div class="flex-none border-t border-gray-200 bg-white shadow-md z-10 sticky bottom-0">
                    <livewire:debates.message-input :debate="$debate" />
                </div>
            </div>
        </div>

        <!-- ハンバーガーメニューオーバーレイ (モバイルとタブレット用) -->
        <div id="mobile-sidebar-overlay" class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
            <div id="mobile-sidebar-content" class="w-80 h-full bg-white shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out">
                <!-- サイドバーヘッダー -->
                <div class="p-4 border-b border-gray-200">
                    <div class="border-gray-200 p-0 flex justify-between items-center">
                        <h4 class="text-sm text-gray-500 mb-1">{{ $debate->room->name }}</h4>
                        <button id="close-mobile-sidebar" class="text-gray-500 p-0 rounded-full hover:bg-gray-100">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                    <p class="text-base font-bold text-gray-900">{{ $debate->room->topic }}</p>
                </div>

                <!-- サイドバーコンテンツ -->
                <div class="overflow-y-auto" style="max-height: calc(100vh - 60px);">
                    <!-- タブナビゲーション -->
                    <div class="flex border-b border-gray-200">
                        <button id="mobile-participants-tab" class="flex-1 py-3 px-4 text-center border-b-2 border-primary text-primary font-medium">
                            {{ __('messages.debate_information_tab') }}
                        </button>
                        <button id="mobile-timeline-tab" class="flex-1 py-3 px-4 text-center text-gray-500 hover:text-gray-700">
                            {{ __('messages.timeline_tab') }}
                        </button>
                    </div>

                    <!-- モバイル参加者パネル -->
                    <div id="mobile-participants-panel" class="block">
                        <livewire:debates.participants :debate="$debate" />
                    </div>
                    <!-- モバイルタイムラインパネル -->
                    <div id="mobile-timeline-panel" class="hidden">
                        <livewire:debates.timeline :debate="$debate" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 接続状態アラート -->
        <livewire:connection-status :room="$debate->room" />

        <!-- ヘルプモーダル -->
        {{-- <div id="help-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg p-6 max-w-lg w-full shadow-xl m-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">{{ __('messages.debate_help_modal_title') }}</h3>
                    <button id="close-help" class="text-gray-500 hover:text-gray-700">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <!-- ヘルプコンテンツ -->
                </div>
            </div>
        </div> --}}

        <!-- メッセージ通知音 -->
        <audio id="messageNotification" preload="auto">
            <source src="{{ asset('sounds/notification.mp3') }}" type="audio/mp3">
        </audio>

        <!-- パート更新通知音 -->
        <audio id="turnAdvancedNotification" preload="auto">
            <source src="{{ asset('sounds/turnadvanced.mp3') }}" type="audio/mp3">
        </audio>
    </div>

    <script>
    window.debateData = {
        debateId: {{ Js::from($debate->id) }},
        roomId: {{ Js::from($debate->room->id) }},
        authUserId: {{ Js::from(auth()->id()) }},
        pusherKey: {{ Js::from(config('broadcasting.connections.pusher.key')) }},
        pusherCluster: {{ Js::from(config('broadcasting.connections.pusher.options.cluster')) }},
    };

    const translations = {
        debate_finished_title: "{{ __('messages.debate_finished_title') }}",
        evaluating_message: "{{ __('messages.evaluating_message') }}",
        evaluation_complete_title: "{{ __('messages.evaluation_complete_title') }}",
        redirecting_to_results: "{{ __('messages.redirecting_to_results') }}",
        host_left_terminated: "{{ __('messages.host_left_terminated') }}",
        debate_finished_overlay_title: "{{ __('messages.debate_finished_overlay_title') }}",
        evaluating_overlay_message: "{{ __('messages.evaluating_overlay_message') }}",
        go_to_results_page: "{{ __('messages.go_to_results_page') }}",
        connection_restored: "{{ __('messages.connection_restored') }}",
        connection_lost_title: "{{ __('messages.connection_lost_title') }}",
        connection_lost_message: "{{ __('messages.connection_lost_message') }}",
        reconnecting_message: "{{ __('messages.reconnecting_message') }}",
        reconnecting_failed_message: "{{ __('messages.reconnecting_failed_message') }}",
        redirecting_after_termination: "{{ __('messages.redirecting_after_termination') }}",
        early_termination_agreed: "{{ __('messages.early_termination_agreed') }}",
        early_termination_declined: "{{ __('messages.early_termination_declined') }}",
        early_termination_proposal: "{{ __('messages.early_termination_proposal', ['name' => ':name']) }}",
    };
    window.translations = translations;

    </script>
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
    @vite([
        'resources/js/debate/ui.js',
        'resources/js/debate/presence.js',
        'resources/js/debate/countdown.js',
        'resources/js/debate/event-listener.js',
        'resources/js/debate/notification.js',
        'resources/js/debate/scroll.js',
    ])

</x-show-layout>
