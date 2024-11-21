<div x-data="chatComponent()" class="flex flex-1 flex-col">
    <!-- メッセージ表示エリア -->
    <div class="flex-1 overflow-y-auto p-4" id="chat-messages" x-ref="messages">
        @foreach ($messages as $message)
        <div class="mb-4 {{ $message->user_id === Auth::id() ? 'text-right' : '' }}">
            <div
                class="inline-block max-w-xs md:max-w-md rounded-lg p-3 {{ $message->user_id === Auth::id() ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-800' }}">
                <span class="font-medium">{{ $message->user->name }}</span>:
                <span>{{ $message->message }}</span>
                <span class="text-xs mt-1  opacity-70">{{ $message->created_at->format('H:i') }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <!-- メッセージ送信フォーム -->
    <div class="p-4 bg-white border-t border-gray-200" x-data="{ messageLength: @entangle('message').length }">
        <form wire:submit.prevent="sendMessage" class="flex items-center">
            <input type="text" wire:model.defer="message" x-model="messageLength" maxlength="3000"
                placeholder="メッセージを入力(最大3000文字)"
                class="flex-1 mr-2 border rounded px-4 py-2 focus:outline-none focus:ring-1 focus:ring-transparent">
            @if($isCurrentSpeaker)
            <button type="submit" x-bind:disabled="!messageLength"
                class="bg-gray-900 text-white px-4 py-2 rounded hover:bg-gray-800 disabled:bg-gray-400"><i
                    class="fas fa-paper-plane mr-3"></i>送信</button>
            @endif
        </form>
        {{-- @error('message') <span class="text-red-500">{{ $message }}</span> @enderror --}}
    </div>

    <!-- スクロールを自動で下に移動させるスクリプト -->
    <script>
        function chatComponent() {
            return {
                init() {
                    this.scrollToBottom();

                    const roomId = @json($debate->room_id);
                    const channel = Echo.channel(`debate.${roomId}`);

                    // メッセージ送信イベントをリッスン
                    channel.listen('DebateMessageSent', (e) => {
                        Livewire.dispatch('messageReceived', e.debateMessage);
                        this.scrollToBottom();
                    });

                    channel.listen('TurnAdvanced', (e) => {
                    Livewire.dispatch('TurnAdvanced', e);
                    this.scrollToBottom();
                    });

                    // メッセージ送信時にスクロール
                    Livewire.on('messageSent', () => {
                        this.scrollToBottom();
                    });

                    // メッセージ受信時にスクロール
                    Livewire.on('messageReceived', () => {
                        this.scrollToBottom();
                    });
                },
                scrollToBottom() {
                    const chatMessages = this.$refs.messages;
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            }
        }
    </script>
</div>
