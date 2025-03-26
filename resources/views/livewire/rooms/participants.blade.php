<div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 肯定側パネル -->
        <div
            class="rounded-lg shadow-sm p-1.5 w-full sm:w-auto flex-1 border-2 border-green-300 flex items-center justify-center relative">
            <div class="absolute top-2 left-4">
                <div class="text-md text-green-500 font-medium">肯定側</div>
            </div>
            @if($affirmativeDebater)
                <div class="{{ $affirmativeDebater === optional(auth()->user())->name ? 'bg-primary-light border-2 border-primary/20' : 'bg-gray-100 border-2 border-gray-300' }} rounded-lg shadow-md p-6 w-full sm:w-auto flex-1">
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
                <div class="text-md text-red-500 font-medium">否定側</div>
            </div>
            @if($negativeDebater)
                <div class="{{ $negativeDebater === optional(auth()->user())->name ? 'bg-primary-light border-2 border-primary/20' : 'bg-gray-100 border-2 border-gray-300' }} rounded-lg shadow-md p-6 w-full sm:w-auto flex-1">
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
</div>
