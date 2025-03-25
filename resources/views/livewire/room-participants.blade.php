<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 肯定側パネル -->
        <div
            class="rounded-lg shadow-sm p-1.5 w-full sm:w-auto flex-1 border-2 border-green-300 flex items-center justify-center relative">
            <div class="absolute top-2 left-4">
                <div class="text-sm text-green-500 font-medium">肯定側</div>
            </div>
            @if($affirmativeDebater)
                <div class="{{ $affirmativeDebater === auth()->user()->name ? 'bg-[#d8e2ff] border-2 border-[#abbbec]' : 'bg-white border-2 border-[#d6dcee]' }} rounded-lg shadow-md p-6 w-full sm:w-auto flex-1">
                    <div class="flex h-full items-center justify-center pt-0">
                        <div class="text-xl text-gray-900 font-medium" id="affirmative-user" wire:key="affirmative-{{ $affirmativeDebater }}">
                            {{ $affirmativeDebater }}
                        </div>
                    </div>
                </div>
            @else
                <div class="flex h-full items-center justify-center p-6">
                    <div class="text-md text-gray-900 font-medium animate-pulse">
                        募集中...
                    </div>
                </div>
            @endif
        </div>

        <!-- 否定側パネル -->
        <div
            class="rounded-lg shadow-sm p-1.5 w-full sm:w-auto flex-1 border-2 border-red-300 flex items-center justify-center relative">
            <div class="absolute top-2 left-4">
                <div class="text-sm text-red-500 font-medium">否定側</div>
            </div>
            @if($negativeDebater)
                <div class="{{ $negativeDebater === auth()->user()->name ? 'bg-[#d8e2ff] border-2 border-[#abbbec]' : 'bg-white border-2 border-[#d6dcee]' }} rounded-lg shadow-md p-6 w-full sm:w-auto flex-1">
                    <div class="flex h-full items-center justify-center pt-0">
                        <div class="text-xl text-gray-900 font-medium" id="negative-user">
                            {{ $negativeDebater }}
                        </div>
                    </div>
                </div>
            @else
            <div class="flex h-full items-center justify-center p-6">
                <div class="text-md text-gray-900 font-medium animate-pulse">
                    募集中...
                </div>
            </div>
            @endif
        </div>
    </div>
    <!-- 参加ボタンセクション -->
    @unless($room->users->contains(auth()->user()))
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mx-1 my-6">
        <form action="{{ route('rooms.join', $room) }}" method="POST" onSubmit="return confirmJoin(event);">
            @csrf
            <button type="submit" name="side" value="affirmative"
                class="w-full btn-outline-affirmative text-lg py-3 px-6 {{ $affirmativeDebater ? 'opacity-50 cursor-not-allowed' : '' }}"
                {{ $affirmativeDebater ? 'disabled' : '' }}>
                肯定側で参加する
            </button>
        </form>
        <form action="{{ route('rooms.join', $room) }}" method="POST" onSubmit="return confirmJoin(event);">
            @csrf
            <button type="submit" name="side" value="negative"
                class="w-full btn-outline-negative text-lg py-3 px-6 {{ $negativeDebater ? 'opacity-50 cursor-not-allowed' : '' }}"
                {{ $negativeDebater ? 'disabled' : '' }}>
                否定側で参加する
            </button>
        </form>
    </div>
    @endunless
</div>
