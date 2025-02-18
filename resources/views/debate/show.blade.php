<x-app-layout>
    <div class="flex flex-col h-screen bg-gray-50 text-gray-800 overflow-hidden">
        <!-- ヘッダー：ルーム名とトピックを表示 -->
        <header class="debate-header bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
                <div class="flex items-center">
                    <div>
                        <h1 class="text-lg font-medium text-gray-800">{{ $debate->room->name }}</h1>
                        <p class="text-lg font-semibold text-gray-800 mt-0">
                            {{ $debate->room->topic }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <!-- メインコンテンツエリア -->
        <div class="flex flex-col lg:flex-row flex-1 overflow-hidden h-full">
            <!-- 左サイドバー：ディベート情報表示 -->
            <div class="w-full lg:w-1/5 lg:max-w-xs p-3 border-gray-200 flex flex-col overflow-auto h-full">
                <livewire:debate-info :debate="$debate" />
            </div>

            <!-- メインコンテンツ：チャットと入力欄 -->
            <div class="bg-white w-full lg:w-5/6 relative rounded-lg mt-1 lg:mt-0 overflow-hidden flex flex-col h-full">
                <!-- タブ表示エリア -->
                <div class="bg-white rounded-lg flex-shrink-0">
                    <livewire:debate-tab :debate="$debate" />
                </div>

                <!-- チャットメインエリア -->
                <div class="flex flex-col h-full">
                    <!-- チャットコンテンツ表示エリア -->
                    <div id="chat-container" class="flex-1 overflow-auto h-full">
                        <livewire:debate-chat :debate="$debate" />
                    </div>

                    <!-- 下部スペースで辻褄を合わせる -->
                    <div class="h-[3rem] bg-transparent"></div>

                    <!-- メッセージ入力欄 -->
                    <div id="input-container" class="flex-none lg:absolute lg:bottom-0 lg:left-0 lg:right-0">
                        <livewire:debate-message-input :debate="$debate" />
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        @vite('resources/js/debate-show.js')
    @endpush
</x-app-layout>
