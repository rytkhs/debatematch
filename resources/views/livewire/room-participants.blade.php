<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 肯定側パネル -->
        <div
            class="rounded-lg shadow-xs p-1.5 w-full sm:w-auto flex-1 border-2 border-green-200 flex items-center justify-center relative">
            <div class="absolute top-2 left-4">
                <div class="text-sm text-green-700 font-medium">肯定側</div>
            </div>
            @if($affirmativeDebater)
                <div class="bg-green-100 rounded-lg shadow-md p-6 w-full sm:w-auto flex-1">
                    <div class="flex h-full items-center justify-center pt-0">
                        <div class="text-xl text-gray-900 font-medium" id="affirmative-user" wire:key="affirmative-{{ $affirmativeDebater }}">
                            {{ $affirmativeDebater }}
                        </div>
                    </div>
                </div>
            @else
                <div class="flex h-full items-center justify-center p-6">
                    @unless($room->users->contains(auth()->user()))
                        <form action="{{ route('rooms.join', $room) }}" method="POST" class="w-full flex justify-center p-0" onSubmit="return confirmJoin(event);">
                            @csrf
                            <button type="submit" name="side" value="affirmative" class="btn-outline-affirmative">
                                肯定側で参加する
                            </button>
                        </form>
                    @else
                        <div class="text-md text-gray-900 font-medium animate-pulse">
                            募集中...
                        </div>
                    @endunless
                </div>
            @endif
        </div>

        <!-- 否定側パネル -->
        <div
            class="rounded-lg shadow-xs p-1.5 w-full sm:w-auto flex-1 border-2 border-red-200 flex items-center justify-center relative">
            <div class="absolute top-2 left-4">
                <div class="text-sm text-red-700 font-medium">否定側</div>
            </div>
            @if($negativeDebater)
                <div class="bg-red-100 rounded-lg shadow-md p-6 w-full sm:w-auto flex-1">
                    <div class="flex h-full items-center justify-center pt-0">
                        <div class="text-xl text-gray-900 font-medium" id="negative-user">
                            {{ $negativeDebater }}
                        </div>
                    </div>
                </div>
            @else
            <div class="flex h-full items-center justify-center p-6">
                @unless($room->users->contains(auth()->user()))
                <form action="{{ route('rooms.join', $room) }}" method="POST"
                class="w-full flex justify-center p-0" onSubmit="return confirmJoin(event);">
                @csrf
                <button type="submit" name="side" value="negative" class="btn-outline-negative">
                    否定側で参加する
                </button>
            </form>
                @else
                    <div class="text-md text-gray-900 font-medium animate-pulse">
                        募集中...
                    </div>
                @endunless
            </div>
            @endif
        </div>
    </div>
</div>
