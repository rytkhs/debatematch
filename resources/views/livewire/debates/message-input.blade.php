<div class="relative p-3">
    <form wire:submit.prevent="sendMessage" class="relative">
        <!-- ツールバー -->
        <div class="flex items-center mb-2 px-2">
            <!-- サイズ調整ハンドル -->
            <div id="resize-handle" class="mr-2 cursor-ns-resize text-gray-400 hover:text-gray-600">
                <span class="material-icons">drag_handle</span>
            </div>

            <!-- 入力エリア操作ボタン -->
            <div class="flex items-center space-x-1 mr-2">
                <button type="button" id="expand-input" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                    <span class="material-icons text-sm">unfold_more</span>
                </button>
                <button type="button" id="shrink-input" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                    <span class="material-icons text-sm">unfold_less</span>
                </button>
                <button type="button" id="toggle-input-visibility" class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100">
                    <span class="material-icons text-sm">visibility</span>
                </button>
            </div>

            <!-- 現在の入力者表示 -->
            @if($isPrepTime)
                <div class="text-sm text-gray-500 flex items-center">
                    <span class="material-icons text-sm mr-1">timer</span>
                    準備時間中
                </div>
            @elseif($isMyTurn)
                <div class="text-sm text-primary font-medium flex items-center">
                    <span class="material-icons text-sm mr-1">edit</span>
                    送信可能
                </div>
            @else
                <div class="text-sm text-gray-500 flex items-center">
                    {{ $isQuestioningTurn ? '質疑応答中' : '' }}
                </div>
            @endif

            <!-- 文字数カウンター -->
            <div class="ml-auto text-xs text-gray-500">
                <span x-text="$wire.newMessage.length"></span>/5000
            </div>
        </div>

        <!-- 入力エリア -->
        <div class="flex items-start" id="input-area">
            <textarea
                id="message-input"
                wire:model.live="newMessage"
                placeholder="メッセージを入力..."
                class="flex-1 border rounded-lg px-4 py-2 focus:ring-primary focus:border-primary resize-none overflow-y-auto bg-white"
                maxlength="5000"
                rows="3"
            ></textarea>

            <button
                type="submit"
                {{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime ? '' : 'disabled' }}
                class="ml-2 w-10 h-10 rounded-full flex items-center justify-center self-end
                       {{ ($isMyTurn || $isQuestioningTurn) && !$isPrepTime
                          ? 'bg-primary hover:bg-primary-dark text-white'
                          : 'bg-gray-300 text-gray-500 cursor-not-allowed' }}"
            >
                <span class="material-icons">send</span>
            </button>
        </div>
    </form>

    @script
    <script>
    document.addEventListener('livewire:initialized', function() {
        const messageInput = document.getElementById('message-input');
        const resizeHandle = document.getElementById('resize-handle');
        const expandInput = document.getElementById('expand-input');
        const shrinkInput = document.getElementById('shrink-input');
        const toggleInputVisibility = document.getElementById('toggle-input-visibility');
        const inputArea = document.getElementById('input-area');

        // 状態管理
        let state = {
            isResizing: false,
            startY: 0,
            startHeight: 0,
            isVisible: true,
            defaultHeight: 72,
            expandedHeight: window.innerHeight * 0.3, // 初期値は画面の30%
            isAnimating: false,
        };

        // 保存された高さを復元
        if (messageInput) {
            const savedHeight = localStorage.getItem('debate_messageInputHeight');
            if (savedHeight) {
                messageInput.style.height = savedHeight;
            }

            // 保存された表示状態を復元
            const savedVisibility = localStorage.getItem('debate_messageInputVisibility');
            if (savedVisibility === 'hidden') {
                inputArea.classList.add('hidden');
                toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility_off';
                state.isVisible = false;
            }
        }

        // リサイズハンドル機能
        if (resizeHandle && messageInput) {
            // マウスイベント
            resizeHandle.addEventListener('mousedown', function(e) {
                state.isResizing = true;
                state.startY = e.clientY;
                state.startHeight = parseInt(getComputedStyle(messageInput).height, 10);
                ensureInputVisible(); // 可視化を保証
                state.isAnimating = false;
                messageInput.style.transition = "none";

                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
                e.preventDefault();
            });

            // タッチイベント（モバイル用）
            resizeHandle.addEventListener('touchstart', function(e) {
                if (e.touches.length !== 1) return;

                state.isResizing = true;
                state.startY = e.touches[0].clientY;
                state.startHeight = parseInt(getComputedStyle(messageInput).height, 10);
                ensureInputVisible(); // 可視化を保証
                state.isAnimating = false;
                messageInput.style.transition = "none";

                document.addEventListener('touchmove', handleTouchMove);
                document.addEventListener('touchend', handleTouchEnd);
                e.preventDefault();
            });
        }

        // 入力エリア拡大ボタン
        if (expandInput && messageInput) {
            expandInput.addEventListener('click', function() {
                // デバイスに基づいて最大拡大率を変更
                const maxHeightPercentage = window.innerWidth < 768 ? 0.7 : 0.73; // モバイルなら70%、それ以外は73%
                state.expandedHeight = window.innerHeight * maxHeightPercentage;
                messageInput.style.transition = "height 0.2s ease";
                messageInput.style.height = `${state.expandedHeight}px`;
                saveInputHeight();
                ensureInputVisible(); // 可視化を保証
                state.isAnimating = true;
            });
        }

        // 入力エリア縮小ボタン
        if (shrinkInput && messageInput) {
            shrinkInput.addEventListener('click', function() {
                messageInput.style.transition = "height 0.2s ease";
                messageInput.style.height = `${state.defaultHeight}px`;
                saveInputHeight();
                ensureInputVisible(); // 可視化を保証
                state.isAnimating = true;
            });
        }

        // 入力エリア表示/非表示ボタン
        if (toggleInputVisibility && inputArea) {
            toggleInputVisibility.addEventListener('click', function() {
                if (state.isVisible) {
                    inputArea.classList.add('hidden');
                    toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility_off';
                    state.isVisible = false;
                } else {
                    inputArea.classList.remove('hidden');
                    toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility';
                    state.isVisible = true;
                }
                saveInputVisibility(state.isVisible);
            });
        }

        // マウス移動処理
        function handleMouseMove(e) {
            if (!state.isResizing) return;

            const deltaY = state.startY - e.clientY;
             // デバイスに基づいて最大拡大率を変更
            const maxHeightPercentage = window.innerWidth < 768 ? 0.6 : 0.73;
            const newHeight = Math.max(60, Math.min(window.innerHeight * maxHeightPercentage, state.startHeight + deltaY));

            messageInput.style.height = `${newHeight}px`;
        }

        // タッチ移動処理
        function handleTouchMove(e) {
            if (!state.isResizing || e.touches.length !== 1) return;

            const deltaY = state.startY - e.touches[0].clientY;
            // デバイスに基づいて最大拡大率を変更
            const maxHeightPercentage = window.innerWidth < 768 ? 0.6 : 0.73;
            const newHeight = Math.max(60, Math.min(window.innerHeight * maxHeightPercentage, state.startHeight + deltaY));

            messageInput.style.height = `${newHeight}px`;
        }

        // マウスアップ処理
        function handleMouseUp() {
            if (!state.isResizing) return;

            state.isResizing = false;
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
            messageInput.style.transition = "";
            state.isAnimating = false;

            saveInputHeight();
        }

        // タッチ終了処理
        function handleTouchEnd() {
            if (!state.isResizing) return;

            state.isResizing = false;
            document.removeEventListener('touchmove', handleTouchMove);
            document.removeEventListener('touchend', handleTouchEnd);
            messageInput.style.transition = "";
            state.isAnimating = false;

            saveInputHeight();
        }

        // 入力エリアの高さを保存
        function saveInputHeight() {
            if (messageInput) {
                localStorage.setItem('debate_messageInputHeight', messageInput.style.height);
            }
        }

        // 入力エリアの表示状態を保存
        function saveInputVisibility(isVisible) {
            localStorage.setItem('debate_messageInputVisibility', isVisible ? 'visible' : 'hidden');
        }

        // 入力エリアが非表示の場合に表示する関数
        function ensureInputVisible() {
            if (!state.isVisible) {
                inputArea.classList.remove('hidden');
                toggleInputVisibility.querySelector('.material-icons').textContent = 'visibility';
                state.isVisible = true;
                saveInputVisibility(state.isVisible);
            }
        }
    });
    </script>
    @endscript
</div>
