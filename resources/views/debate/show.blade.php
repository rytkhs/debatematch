<x-app2-layout>
    <div class="flex flex-col h-screen w-full overflow-hidden">
        <!-- ヘッダー -->
        <livewire:debates.header :debate="$debate" />

        <!-- メインコンテンツ: 2カラムレイアウト (モバイルでは単一カラム) -->
        <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
            <!-- 左サイドバー: ディベート情報 -->
            <div class="w-full lg:w-1/4 xl:w-1/5 lg:border-r border-gray-200 bg-white lg:overflow-y-auto shadow-md z-20 lg:flex-shrink-0" id="left-sidebar">
                <!-- タブナビゲーション -->
                <div class="flex border-b border-gray-200">
                    <button id="participants-tab" class="flex-1 py-3 px-4 text-center border-b-2 border-primary text-primary font-medium">
                        ディベート情報
                    </button>
                    <button id="timeline-tab" class="flex-1 py-3 px-4 text-center text-gray-500 hover:text-gray-700">
                        タイムライン
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
            <div class="flex-1 flex flex-col relative h-full w-full lg:w-3/4">
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

        <!-- ハンバーガーメニューオーバーレイ (モバイル用) -->
        <div id="mobile-sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
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
                            ディベート情報
                        </button>
                        <button id="mobile-timeline-tab" class="flex-1 py-3 px-4 text-center text-gray-500 hover:text-gray-700">
                            タイムライン
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
                    <h3 class="text-xl font-bold text-gray-900">ディベートヘルプ</h3>
                    <button id="close-help" class="text-gray-500 hover:text-gray-700">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                <div class="space-y-4">
                    <!-- ヘルプコンテンツ -->
                </div>
            </div>
        </div> --}}
    </div>

    <script>
    window.debateData = {
        debateId: {{ Js::from($debate->id) }},
        roomId: {{ Js::from($debate->room->id) }},
        authUserId: {{ Js::from(auth()->id()) }},
        pusherKey: {{ Js::from(config('broadcasting.connections.pusher.key')) }},
        pusherCluster: {{ Js::from(config('broadcasting.connections.pusher.options.cluster')) }}
    };
    </script>
    <script src="https://js.pusher.com/8.3.0/pusher.min.js"></script>
    @vite(['resources/js/debate/ui.js', 'resources/js/debate/presence.js', 'resources/js/debate/countdown.js', 'resources/js/debate/event-listener.js'])

</x-app2-layout>
