<div>
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="mb-4">
            <!-- 現在のターン名 -->
            <div class="flex items-center justify-center p-3 bg-primary-light rounded-lg">
                <span class="text-lg text-primary font-bold">{{ $currentTurnName }}</span>
            </div>
            <!-- 次のターン名 -->
            <div class="mt-0 p-1 flex justify-center">
                <span class="text-gray-600 mr-1">Next:</span>
                <span class="font-medium">{{ $nextTurnName }}</span>
            </div>

            {{-- <div class="px-4 py-2 bg-gray-50 rounded-lg">
                <!-- 準備時間の場合 -->
                @if($isPrepTime)
                @if(!$isMyTurn)
                <div class="text-lg text-blue-600 mt-0 text-center">相手の準備時間です</div>
                @else
                <div class="text-lg text-yellow-600 mt-0 text-center">あなたの準備時間です</div>
                @endif
                @elseif($isQuestioningTurn)
                <div class="text-xl text-green-700 mt-0 text-center">質疑応答</div>
                @elseif($isMyTurn)
                <!-- 発話権が自分の場合 -->
                <div class="text-xl text-red-700 mt-0 text-center">あなたのターンです</div>
                @else
                <!-- 発話者が相手の場合 -->
                <div class="text-lg text-blue-600 mt-0 text-center">相手のターンです</div>
                @endif
            </div> --}}

            <!-- カウントダウン -->
                <div class="mt-4 text-center">
                    <div class="text-3xl font-bold text-primary mb-1 flex justify-center items-center" id="countdownText" style="min-height: 2.5rem">
                        <div class="animate-spin h-8 w-8 border-4 border-primary rounded-full border-t-transparent"></div>
                    </div>
                    <p class="text-sm text-gray-600">残り時間</p>
                </div>
        </div>
        <!-- ターン終了ボタン（自分のターンのみ有効） -->
        <div class="flex justify-center mt-6">
            <!-- isMyTurn が true ならボタンを表示 -->
            @if($isMyTurn)
            <button wire:click="advanceTurnManually" wire:loading.attr="disabled"
                wire:confirm="{{$currentTurnName}}を終了し、{{$nextTurnName}}に進みます。\nターンを終了してよろしいですか?"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-3 m-2 mb-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                ターンを終了する
            </button>
            @else
            <div class="py-2 px-3 m-2 mb-4" style="height: 42px;"></div>
            @endif
        </div>

        <!-- ディベーター一覧表示 -->
        <div class="border-t border-gray-200 pt-4">
            <h3 class="text-lg font-medium text-gray-700 mb-3 flex items-center">
                <span class="material-icons-outlined mr-2">
                group
                </span>
                ディベーター
            </h3>
            <ul class="space-y-2">
                <li class="flex items-center py-1 px-2 justify-between @if($currentSpeaker === 'affirmative') bg-blue-100 rounded-lg @endif">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <div class="font-medium text-green-600">肯定側</div>
                    </div>
                    <div class="text-lg font-medium text-gray-600 ml-0 truncate lg:truncate" title="{{ $debate->affirmativeUser->name }}">
                        <span class="hidden lg:inline">{{ Str::limit($debate->affirmativeUser->name, 14, '...') }}</span>
                        <span class="lg:hidden">{{ $debate->affirmativeUser->name }}</span>
                    </div>
                </li>
                <li class="flex items-center py-1 px-2 justify-between @if($currentSpeaker === 'negative') bg-blue-100 rounded-lg @endif">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
                        <div class="font-medium text-red-600">否定側</div>
                    </div>
                    <div class="text-lg font-medium text-gray-600 ml-0 truncate lg:truncate" title="{{ $debate->negativeUser->name }}">
                        <span class="hidden lg:inline">{{ Str::limit($debate->negativeUser->name, 14, '...') }}</span>
                        <span class="lg:hidden">{{ $debate->negativeUser->name }}</span>
                    </div>
                </li>
            </ul>
        </div>
    </div>
        @script
        <script>
        const countdownTextElement = document.getElementById('countdownText');

        let turnEndTime = $wire.$get('turnEndTime');
        let countdownTimer = null;

        function startCountdown() {
            if (countdownTimer) {
                clearInterval(countdownTimer);
            }

             // ターン終了時刻が未設定ならカウントダウンを表示しない
            if (!turnEndTime) {
                countdownTextElement.textContent = "";
                return;
            }

            const endTime = turnEndTime * 1000; // ミリ秒に変換

            countdownTimer = setInterval(() => {
                const now = new Date().getTime();
                const distance = endTime - now;
                      // タイマーがゼロ以下になればターン終了とみなす
                if (distance <= 0) {
                    clearInterval(countdownTimer);
                    countdownTextElement.textContent = "ターン終了";
                    return;
                }

                const minutes = Math.floor((distance / 1000 / 60) % 60);
                const seconds = Math.floor((distance / 1000) % 60);

                countdownTextElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }, 1000);
        }
        // 初回表示時
        startCountdown();

        // ターンが進行したらカウントダウンを再スタート
        $wire.on('turn-advanced', (data) => {
            console.log('turn-advanced', data);
            turnEndTime = data.turnEndTime;
                startCountdown();
        });
        </script>
        @endscript
</div>
