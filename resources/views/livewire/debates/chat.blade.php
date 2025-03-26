<div class="h-full flex flex-col">
    <!-- フィルターバー -->
    <div class="sticky top-0 bg-white border-b border-gray-200 z-10">
        <div class="px-4 py-2 flex items-center justify-between">
            <div class="flex space-x-2 overflow-x-auto hide-scrollbar">
                <!-- 全てのタブ -->
                <button wire:click="$set('activeTab', 'all')"
                    class="px-3 py-1 text-sm rounded-full whitespace-nowrap focus:outline-none
                           {{ $activeTab === 'all' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    全て
                </button>

                <!-- 各ターンのタブ -->
                @foreach($filteredTurns as $key => $turn)
                <button wire:click="$set('activeTab', '{{ $key }}')"
                    class="px-3 py-1 text-sm rounded-full whitespace-nowrap focus:outline-none
                           {{ $activeTab === (string) $key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                           {{ $turn['speaker'] === 'affirmative' ? '肯定側' : '否定側' }}{{ $turn['name'] }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- メッセージ表示エリア -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-container" x-data="{ previousTurn: null }">
            @forelse($filteredMessages as $message)
                <!-- ターン区切り表示 -->
                @if($loop->first || $previousTurn !== $message->turn)
                    <div class="flex justify-center my-4">
                        <div class="px-3 py-1 bg-gray-100 rounded-full text-xs text-gray-600">
                            {{ ($turns[$message->turn]['speaker'] ?? '') === 'affirmative' ? '肯定側' : '否定側' }} {{ $turns[$message->turn]['name'] ?? '' }}
                        </div>
                    </div>
                    @php $previousTurn = $message->turn; @endphp
                @endif

                <!-- メッセージ -->
                <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }} group">
                    <!-- 相手のアバター（自分のメッセージでは非表示） -->
                    @if($message->user_id !== Auth::id())
                    <div class="flex-shrink-0 mr-2">
                        <div class="w-8 h-8 rounded-full {{ $message->user_id === $debate->affirmative_user_id ? 'bg-green-200 text-green-700' : 'bg-red-200 text-red-700' }} flex items-center justify-center text-sm">
                            {{ substr($message->user->name, 0, 1) }}
                        </div>
                    </div>
                    @endif

                    <!-- メッセージ本文 -->
                    <div class="max-w-[85%]">
                        <div class="rounded-lg p-3 {{ $message->user_id === Auth::id()
                            ? 'bg-primary-light text-gray-800'
                            : ($message->user_id === $debate->affirmative_user_id
                                ? 'bg-green-50 border border-green-200 text-gray-800'
                                : 'bg-red-50 border border-red-200 text-gray-800')
                        }}">
                            <div class="whitespace-pre-wrap break-words">{{ $message->message }}</div>
                        </div>

                        <!-- 送信者名と時間 -->
                        <div class="flex mt-1 text-xs text-gray-500 {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                            <span>{{ $message->user->name }}</span>
                            <span class="mx-1">·</span>
                            <span>{{ $message->created_at->format('H:i') }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center h-full">
                    <div class="text-center text-gray-500">
                        <div class="material-icons text-4xl mb-2">chat</div>
                        <p>メッセージはまだありません</p>
                    </div>
                </div>
            @endforelse

            <!-- 新メッセージ通知 -->
            <div id="new-message-notification" class="fixed bottom-16 left-1/2 transform -translate-x-1/2 bg-primary text-white px-4 py-2 rounded-full shadow-lg hidden">
                <div class="flex items-center">
                    <span class="material-icons mr-1">arrow_downward</span>
                    <span>新しいメッセージ</span>
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

            isUserScrolling = false;
        }

        // 初期表示時にスクロール
        scrollToBottom(false);

        // スクロールイベントハンドラ
        if (chatContainer) {
            chatContainer.addEventListener('scroll', function() {
                // スクロール中のユーザー操作判定
                const currentScrollTop = chatContainer.scrollTop;
                const maxScrollTop = chatContainer.scrollHeight - chatContainer.clientHeight;
                const isAtBottom = maxScrollTop - currentScrollTop < 20;

                if (!isAtBottom) {
                    isUserScrolling = true;
                } else if (currentScrollTop > lastScrollTop) {
                    // 下方向へのスクロールで最下部に到達した場合
                    isUserScrolling = false;
                    if (newMessageNotification) {
                        newMessageNotification.classList.add('hidden');
                    }
                }

                lastScrollTop = currentScrollTop;
            });
        }

        // 新メッセージが追加されたときの処理
        $wire.on('message-received', () => {
            console.log('Message received event triggered');
            // 少し遅延させてDOMの更新を待つ
            setTimeout(() => {
                if (isUserScrolling) {
                    // ユーザーがスクロール中なら通知を表示
                    if (newMessageNotification) {
                        newMessageNotification.classList.remove('hidden');

                        // 一定時間後に通知を消す
                        setTimeout(() => {
                            newMessageNotification.classList.add('hidden');
                        }, 5000);
                    }
                } else {
                    // 自動スクロール
                    scrollToBottom();
                }
            }, 100);
        });

        // 自分がメッセージを送信したときの処理
        $wire.on('message-sent', () => {
            console.log('Message sent event triggered');
            // 少し遅延させてDOMの更新を待つ
            setTimeout(() => {
                scrollToBottom();
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
