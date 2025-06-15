<div>
    @if($status === 'waiting')
        <!-- 参加者待ちのメッセージ（クリエイターのみ表示） -->
        @if($isCreator)
        <div class="mt-4 text-center">
            <div class="flex items-center justify-center space-x-2">
                <div class="w-4 h-4 border-2 border-blue-500 rounded-full animate-spin border-t-transparent">
                </div>
                <span class="text-blue-600 text-lg font-semibold">{{ __('messages.waiting_for_participants') }}</span>
                <p class="text-lg text-gray-500">{{ $room->users->count() }}/2</p>
            </div>
        </div>
        @endif
    @elseif($status === 'ready')
        <!-- 参加者が揃った時のメッセージ -->
        @if($isCreator)
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-0">
                <span class="text-green-500 text-lg font-semibold">{{ __('messages.debaters_ready') }}</span>
                <span class="text-green-500 text-lg font-semibold">{{ __('messages.please_start_debate') }}</span>
            </div>
        </div>
        @else
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-1 animate-pulse">
                <span class="text-blue-500 text-md font-semibold">{{ __('messages.wait_for_debate_start') }}</span>
                <span class="text-blue-500 text-md font-semibold">{{ __('messages.auto_redirect_on_start') }}</span>
            </div>
        </div>
        @endif
    @elseif($status === 'debating')
        <!-- ディベート中のメッセージ -->
        <div class="mt-4 text-center">
            <div class="flex flex-col items-center justify-center space-y-1">
                <span class="text-yellow-500 text-md font-semibold">{{ __('messages.debate_in_progress') }}</span>
                @if($room->debate)
                <a href="/debate/{{ $room->debate->id }}" class="text-blue-500 hover:underline">
                    <span>{{ __('messages.click_if_no_redirect') }}</span>
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
                <button type="submit"
                onclick="return confirm('{{ __('messages.confirm_start_debate') }}')"
                    class="bg-primary text-white text-lg px-6 py-2 m-4 rounded-md hover:bg-primary-dark transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-400 flex items-center {{ $status !== 'ready' ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $status !== 'ready' ? 'disabled' : '' }}>
                    <span class="material-icons-outlined">play_arrow</span>
                    {{ __('messages.start_debate') }}
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
