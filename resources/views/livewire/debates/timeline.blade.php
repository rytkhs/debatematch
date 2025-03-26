<div class="h-full flex flex-col p-4 overflow-auto">
    <h2 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
        <span class="material-icons mr-2">timeline</span>進行状況
    </h2>

    <div class="space-y-1 relative">
        <!-- タイムラインの垂直線 -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

        @foreach($format as $index => $turn)
            <div class="flex items-start relative ml-2">
                <!-- ステータスマーカー -->
                <div class="absolute left-2 w-4 h-4 rounded-full transform -translate-x-1/2 {{ $index < $currentTurn ? 'bg-primary' : ($index == $currentTurn ? 'bg-yellow-500 animate-pulse' : 'bg-gray-300') }} z-10"></div>

                <!-- ターン内容 -->
                <div class="ml-6 pb-5 pt-0">
                    <div class="flex items-center">
                        <span class="text-sm font-medium {{ $index == $currentTurn ? 'text-yellow-700' : ($index < $currentTurn ? 'text-gray-500' : 'text-gray-400') }}">
                            {{ $turn['name'] }}
                        </span>

                        <!-- 話者表示 -->
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $turn['speaker'] === 'affirmative' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $turn['speaker'] === 'affirmative' ? '肯定側' : '否定側' }}
                        </span>
                    </div>

                    <!-- ターン説明 -->
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $turn['duration'] / 60 }}分
                        @if($turn['is_prep_time'] ?? false)
                            · 準備時間
                        @endif
                        @if($turn['is_questions'] ?? false)
                            · 質疑可
                        @endif
                    </div>

                    <!-- 完了マーク -->
                    @if($index < $currentTurn)
                    <div class="text-xs text-primary mt-1 flex items-center">
                        <span class="material-icons text-sm mr-1">check_circle</span>
                        完了
                    </div>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- 終了 -->
        <div class="flex items-start relative ml-2">
            <!-- ステータスマーカー -->
            <div class="absolute left-2 w-4 h-4 rounded-full transform -translate-x-1/2 bg-gray-300 z-10"></div>

            <!-- ターン内容 -->
            <div class="ml-6 pb-5 pt-0">
                <div class="flex items-center">
                    <span class="text-sm font-medium text-gray-400">
                        終了
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
