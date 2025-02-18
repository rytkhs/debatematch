<div class="relative">
    <div class="relative" id="message-input-container">
        <!-- メッセージ送信フォーム -->
        <div class="pb-2 pt-1 pr-2 pl-1 lg:pb-4 lg:pt-3 bg-white border-t border-gray-200">
            <form wire:submit.prevent="sendMessage" class="flex items-end">
                <!-- 最大化・最小化ボタンのコンテナを左側に配置 -->
                <div class="flex flex-col items-center mr-2 space-y-0 mb-3">
                    <!-- 最大化ボタン -->
                    <button type="button" id="maximize-btn" aria-label="最大化" class=" text-gray-500 rounded-md p-0 hover:bg-gray-200 focus:outline-none transform hover:scale-105">
                        <span class="material-icons text-md font-bold leading-none">
                            fullscreen
                        </span>
                    </button>
                    <!-- 最小化ボタン -->
                    <button type="button" id="minimize-btn" aria-label="最小化" class=" text-gray-500 rounded-md p-0 hover:bg-gray-200 focus:outline-none transform hover:scale-105">
                        <span class="material-icons text-md font-bold">
                            fullscreen_exit
                        </span>
                    </button>
                </div>
                <div class="relative flex-1 mr-2">
                    <div id="resizeHandle" class="absolute top-0 left-1/2 transform -translate-x-1/2 -mt-3 w-8 h-4 bg-gray-300 border border-gray-400 rounded-lg shadow-md cursor-row-resize flex items-center justify-center hover:bg-gray-400 focus:outline-none hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                        </svg>
                    </div>
                    <textarea type="text" wire:model.live="newMessage" maxlength="5000" placeholder="(最大5000文字)"
                        id="message-input"
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 resize-none overflow-y-scroll transition-all duration-200"></textarea>
                </div>


                    <button type="submit"
                        class="text-white bg-primary px-4 py-2 mx-1 rounded-md hover:bg-primary-dark hover:text-gray-100 disabled:bg-gray-400 mb-4 outline-3 outline-2 outline outline-gray-400 flex items-center">
                        <span class="hidden md:inline">送信</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:ml-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
            </form>
            <div class="absolute bottom-0 right-20 lg:right-24 text-gray-600 text-xs lg:text-sm p-1">
                <span x-text="$wire.newMessage.length"></span>/5000
            </div>
        </div>
    </div>

    <!-- ビジブルボタン -->
    <button type="button" id="visible-btn" aria-label="visible"
        class="text-gray-400 rounded-md p-0 hover:bg-gray-200 focus:outline-none transform hover:scale-105 absolute left-0 -bottom-0 ml-1">
        <span class="material-icons text-md font-thin" id="visible-icon">
            visibility_off
        </span>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const resizeHandle = document.getElementById('resizeHandle');
        const textarea = document.getElementById('message-input');
        const maximizeBtn = document.getElementById('maximize-btn');
        const minimizeBtn = document.getElementById('minimize-btn');
        const visibleBtn = document.getElementById('visible-btn');
        const visibleIcon = document.getElementById('visible-icon');
        const form = document.querySelector('form');
        const messageInputContainer = document.getElementById('message-input-container');
        let isResizing = false;
        let startY = 0;
        let originalHeight = 0;
        let maxHeight = window.innerHeight * 0.85; // 画面の高さの85%に設定
        const minHeight = 64; // 最小高さを設定
        let isManuallyResized = false; // 手動リサイズされたかどうかを追跡するフラグ

        // 自動リサイズ関数
        const adjustTextareaHeight = () => {
            if (isManuallyResized) return; // 手動リサイズ後は自動リサイズしない
            textarea.style.height = 'auto'; // 一旦 auto に戻す
            textarea.style.height = `${Math.min(textarea.scrollHeight, maxHeight)}px`; // 最大 height を考慮
        };

        // 初期化時に自動リサイズ
        adjustTextareaHeight();

        // input イベントで自動リサイズ
        textarea.addEventListener('input', adjustTextareaHeight);

        resizeHandle.addEventListener('mousedown', (e) => {
            textarea.classList.remove('transition-all', 'duration-200');
            isResizing = true;
            startY = e.clientY;
            originalHeight = textarea.offsetHeight;
            isManuallyResized = true; // 手動リサイズ開始時にフラグを true に設定
            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });

        function handleMouseMove(e) {
            if (!isResizing) return;
            const deltaY = e.clientY - startY;
            let newHeight = originalHeight - deltaY;
            newHeight = Math.min(newHeight, maxHeight); // 手動リサイズ時も最大 height を考慮
            newHeight = Math.max(newHeight, minHeight); // 最小 height を考慮
            textarea.style.height = `${newHeight}px`;
        }

        function handleMouseUp() {
            textarea.classList.add('transition-all', 'duration-200');
            isResizing = false;
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
            // 手動リサイズ終了時に自動リサイズを再適用しない
        }

        // 最大化ボタンのクリックイベント
        maximizeBtn.addEventListener('click', () => {
            textarea.style.height = `${maxHeight}px`;
            isManuallyResized = true; // 手動リサイズとして扱う
        });

        // 最小化ボタンのクリックイベント
        minimizeBtn.addEventListener('click', () => {
            textarea.style.height = `${minHeight}px`;
            isManuallyResized = true; // 手動リサイズとして扱う
        });

        // ビジブルボタンのクリックイベント
        visibleBtn.addEventListener('click', () => {
            if (messageInputContainer.style.display === 'none') {
                messageInputContainer.style.display = 'block';
                visibleIcon.textContent = 'visibility_off';
            } else {
                messageInputContainer.style.display = 'none';
                visibleIcon.textContent = 'visibility';
            }
        });

        // 送信ボタンが押されたときにリセット
        form.addEventListener('submit', () => {
            isManuallyResized = false; // 自動リサイズを再度有効化
            adjustTextareaHeight(); // テキストエリアの高さを再調整
        });

        // ウィンドウサイズが変更された場合に maxHeight を更新
        window.addEventListener('resize', () => {
            maxHeight = window.innerHeight * 0.9;
            if (!isManuallyResized) {
                adjustTextareaHeight();
            }
        });
    });
</script>
