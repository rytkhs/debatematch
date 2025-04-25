<div class="h-full flex flex-col">
    <!-- フィルターバー -->
    <div class="sticky top-0 bg-white border-b border-gray-200 z-10">
        <div class="px-4 py-2 flex items-center justify-between">
            <div class="flex space-x-2 overflow-x-auto hide-scrollbar">
                <!-- 全てのタブ -->
                <button wire:click="$set('activeTab', 'all')"
                    class="px-3 py-1 text-sm rounded-full whitespace-nowrap focus:outline-none
                           {{ $activeTab === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    {{ __('messages.all') }}
                </button>

                <!-- 各ターンのタブ -->
                @foreach($filteredTurns as $key => $turn)
                <button wire:click="$set('activeTab', '{{ $key }}')"
                    class="px-3 py-1 text-sm rounded-full whitespace-nowrap focus:outline-none
                           {{ $activeTab === (string) $key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                           {{ $turn['speaker'] === 'affirmative' ? __('messages.affirmative_side_label') : __('messages.negative_side_label') }}{{ $turn['name'] }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- メッセージ表示エリア -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-container" x-data="{ previousTurn: null }">
        @php $aiUserId = (int)config('app.ai_user_id', 1); @endphp
        @forelse($filteredMessages as $message)
            <!-- ターン区切り表示 -->
            @if($loop->first || $previousTurn !== $message->turn)
                <div class="flex justify-center my-4">
                    <div class="px-3 py-1 bg-gray-100 rounded-full text-xs text-gray-600">
                        {{ ($turns[$message->turn]['speaker'] ?? '') === 'affirmative' ? __('messages.affirmative_side_label') : __('messages.negative_side_label') }} {{ $turns[$message->turn]['name'] ?? '' }}
                    </div>
                </div>
                @php $previousTurn = $message->turn; @endphp
            @endif

            <!-- メッセージ -->
            @php $isAIMessage = $message->user_id === $aiUserId; @endphp {{-- AIメッセージか判定 --}}
            <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }} group">
                <!-- 相手のアバター -->
                @if($message->user_id !== Auth::id())
                <div class="flex-shrink-0 mr-2">
                    <div class="w-8 h-8 rounded-full {{
                        $isAIMessage ? 'bg-blue-200 text-blue-700' : (
                        $message->user_id === $debate->affirmative_user_id ? 'bg-green-200 text-green-700' : 'bg-red-200 text-red-700'
                        ) }} flex items-center justify-center text-sm">
                        {{-- AI用アイコン --}}
                        @if($isAIMessage)
                            <span class="material-icons-outlined text-lg">smart_toy</span>
                        @else
                            {{ mb_substr($message->user->name, 0, 1) }}
                        @endif
                    </div>
                </div>
                @endif

                <!-- メッセージ本文 -->
                <div class="max-w-[95%]">
                    <div class="rounded-lg p-3 {{ $message->user_id === Auth::id()
                        ? 'bg-primary-light text-gray-800'
                        : ($isAIMessage // AIメッセージのスタイル
                            ? 'bg-blue-50 border border-blue-200 text-gray-800'
                            : ($message->user_id === $debate->affirmative_user_id // 肯定側ユーザー
                                ? 'bg-green-50 border border-green-200 text-gray-800'
                                : 'bg-red-50 border border-red-200 text-gray-800' // 否定側ユーザー
                              )
                          )
                    }}">
                        <div class="whitespace-pre-wrap break-words">{{ $message->message }}</div>
                    </div>

                    <!-- 送信者名と時間 -->
                    <div class="flex mt-1 text-xs text-gray-500 {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                        <span>{{ $message->user->name }}</span>
                        {{-- AIラベル --}}
                        @if($isAIMessage)
                            <span class="ml-1.5 px-1 py-0 bg-blue-100 text-blue-800 text-[9px] rounded-full font-semibold">{{ __('messages.ai_label') }}</span>
                        @endif
                        <span class="mx-1">·</span>
                        <span>{{ $message->created_at->format('H:i') }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex items-center justify-center h-full">
                <div class="text-center text-gray-500">
                    <div class="material-icons text-4xl mb-2">chat</div>
                    <p>{{ __('messages.no_messages_yet') }}</p>
                </div>
            </div>
        @endforelse

        <!-- 新メッセージ通知 -->
        <div id="new-message-notification" class="fixed bottom-16 left-1/2 transform -translate-x-1/2 bg-primary text-white px-4 py-2 rounded-full shadow-lg hidden cursor-pointer">
            <div class="flex items-center">
                <span class="material-icons mr-1">arrow_downward</span>
                <span>{{ __('messages.new_message') }}</span>
            </div>
        </div>
    </div>
</div>

@script
<script>
document.addEventListener('livewire:initialized', function() {
    // チャットコンテナ取得
    const chatContainer = document.getElementById('chat-container');
    const newMessageNotification = document.getElementById('new-message-notification');

    // スクロール状態の管理
    let isUserScrolling = false;
    let lastScrollTop = 0;
    let scrollTimeout = null;

    // 画面下部までスクロール
    function scrollToBottom(smooth = true) {
        if (!chatContainer) return;

        // console.log('Scrolling to bottom, height:', chatContainer.scrollHeight);

        if (smooth) {
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: 'smooth'
            });
        } else {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // スクロール直後はユーザー操作フラグをリセット
        isUserScrolling = false;
        if (newMessageNotification) {
            newMessageNotification.classList.add('hidden');
        }
    }

    // 初期表示時にスクロール
    // Livewireの初期化後に少し待ってから実行
    setTimeout(() => scrollToBottom(false), 150);


    // スクロールイベントハンドラ
    if (chatContainer) {
        chatContainer.addEventListener('scroll', function() {
            // スクロール中のユーザー操作判定
            const currentScrollTop = chatContainer.scrollTop;
            const maxScrollTop = chatContainer.scrollHeight - chatContainer.clientHeight;
            const isAtBottom = maxScrollTop - currentScrollTop < 30; // 判定閾値

            // ユーザーが手動でスクロールしたと判定（最下部付近でない場合）
            if (!isAtBottom && Math.abs(currentScrollTop - lastScrollTop) > 5) { // 少し動いただけではフラグを立てない
                 isUserScrolling = true;
                 // console.log('User scrolling detected');
            }

            // スクロール停止を検出するためのタイマー
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                 // スクロールが停止し、かつ最下部にいる場合，自動スクロールを有効にする
                 if (chatContainer.scrollTop >= maxScrollTop - 30) {
                     isUserScrolling = false;
                     if (newMessageNotification) {
                         newMessageNotification.classList.add('hidden');
                     }
                     // console.log('Scrolled to bottom, auto-scroll enabled');
                 }
            }, 150); // 150ms スクロールがなければ停止とみなす

            lastScrollTop = currentScrollTop;
        });
    }

    // 新メッセージが追加されたときの処理
    $wire.on('message-received', () => {
        // console.log('Livewire message-received event triggered');
        // DOM更新を待つため少し遅延させる
        setTimeout(() => {
            if (isUserScrolling) {
                // ユーザーがスクロール中なら通知を表示
                if (newMessageNotification) {
                    newMessageNotification.classList.remove('hidden');
                    // console.log('Showing new message notification');

                    // 一定時間後に通知を消すタイマー（すでにあればクリア）
                    clearTimeout(newMessageNotification.timer);
                    newMessageNotification.timer = setTimeout(() => {
                        newMessageNotification.classList.add('hidden');
                    }, 5000);
                }
            } else {
                // 自動スクロール
                // console.log('Auto-scrolling to bottom');
                scrollToBottom();
            }
        }, 100); // 遅延
    });

    // 自分がメッセージを送信したときの処理
    $wire.on('message-sent', () => {
        // console.log('Livewire message-sent event triggered');
        // DOM更新を待つため少し遅延させる
        setTimeout(() => {
            // console.log('Scrolling to bottom after sending message');
            scrollToBottom(); // 自分の送信後は常にスクロール
        }, 100);
    });

    // 通知クリックでスクロール
    if (newMessageNotification) {
        newMessageNotification.addEventListener('click', () => {
            scrollToBottom();
            newMessageNotification.classList.add('hidden');
        });
    }
});
</script>
@endscript
