<div>
    @if($status === 'waiting')
        <!-- 参加者待ちのメッセージ（クリエイターのみ表示） -->
        @if($isCreator)
        <div class="mt-4 text-center">
            <div class="flex items-center justify-center space-x-2">
                <div class="w-4 h-4 border-2 border-blue-500 rounded-full animate-spin border-t-transparent">
                </div>
                <span class="text-blue-600 text-lg font-semibold">{{ __('rooms.waiting_for_participants') }}</span>
                <p class="text-lg text-gray-500">{{ $room->users->count() }}/2</p>
            </div>
        </div>
        @endif
    @elseif($status === 'ready')
        <!-- 参加者が揃った時のメッセージ -->
        @if($isCreator)
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-2">
                <span class="text-green-500 text-lg font-semibold">{{ __('rooms.debaters_ready') }}</span>

                <!-- オンライン状態表示 -->
                <div class="flex items-center space-x-4 text-sm">
                    @foreach($room->users as $user)
                        <div class="flex items-center space-x-1">
                            <div class="w-2 h-2 rounded-full {{ ($onlineUsers[$user->id] ?? false) ? 'bg-green-500' : 'bg-red-500' }}"></div>
                            <span class="text-gray-600">{{ $user->name }}</span>
                        </div>
                    @endforeach
                </div>

                @php
                    $allOnline = true;
                    foreach($room->users as $user) {
                        if (!($onlineUsers[$user->id] ?? false)) {
                            $allOnline = false;
                            break;
                        }
                    }
                @endphp

                @if($allOnline)
                    <span class="text-green-500 text-lg font-semibold">{{ __('rooms.please_start_debate') }}</span>
                @else
                    <span class="text-orange-500 text-sm">{{ __('rooms.waiting_for_all_online') }}</span>
                @endif
            </div>
        </div>
        @else
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-1 animate-pulse">
                <span class="text-blue-500 text-md font-semibold">{{ __('rooms.wait_for_debate_start') }}</span>
                <span class="text-blue-500 text-md font-semibold">{{ __('rooms.auto_redirect_on_start') }}</span>
            </div>
        </div>
        @endif
    @elseif($status === 'debating')
        <!-- ディベート中のメッセージ -->
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-1">
                <span class="text-yellow-500 text-md font-semibold">{{ __('rooms.debate_in_progress') }}</span>
                @if($room->debate)
                <a href="/debate/{{ $room->debate->id }}" class="text-blue-500 hover:underline">
                    <span>{{ __('rooms.click_if_no_redirect') }}</span>
                </a>
                @endif
            </div>
        </div>
    @endif

    <!-- ボタンセクション -->
    <div class="mt-4 p-8">
        @if($isCreator)
        <div class="flex justify-center">
            <form wire:submit="startDebate">
                @csrf
                @php
                    $allOnline = true;
                    foreach($room->users as $user) {
                        if (!($onlineUsers[$user->id] ?? false)) {
                            $allOnline = false;
                            break;
                        }
                    }
                    $canStart = $status === 'ready' && $allOnline;
                @endphp

                <button type="submit"
                onclick="return confirm('{{ __('rooms.confirm_start_debate') }}')"
                    class="bg-primary text-white text-lg px-6 py-2 m-4 rounded-md hover:bg-primary-dark transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-400 flex items-center {{ !$canStart ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ !$canStart ? 'disabled' : '' }}>
                    <span class="material-icons-outlined">play_arrow</span>
                    {{ __('rooms.start_debate') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
