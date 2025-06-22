<div>
    <div class="grid grid-cols-1 gap-6">
        <!-- 肯定側パネル -->
        <div
            class="rounded-lg shadow-sm p-1.5 w-full sm:w-auto flex-1 border-2 border-green-300 flex items-center justify-center relative">
            <div class="absolute top-2 left-4">
                <div class="text-md text-green-500 font-medium">{{ __('debates_ui.affirmative_side_label') }}</div>
            </div>
            @if($affirmativeDebater)
                <div class="{{ $affirmativeDebater === optional(auth()->user())->name ? 'bg-primary-light border-2 border-primary/20' : 'bg-gray-100 border-2 border-gray-300' }} rounded-lg shadow-md p-6 w-full sm:w-auto flex-1 min-w-0">
                    <div class="flex h-full items-center justify-center pt-0">
                        <div class="text-xl text-gray-900 font-medium text-center"
                             id="affirmative-user"
                             wire:key="affirmative-{{ $affirmativeDebater }}"
                             title="{{ $affirmativeDebater }}">
                            @php
                                $width = 0;
                                $chars = mb_str_split($affirmativeDebater);
                                $cutIndex = 0;
                                $maxWidth = 24; // 半角24文字分の幅

                                foreach ($chars as $index => $char) {
                                    $charWidth = mb_strwidth($char);
                                    if ($width + $charWidth > $maxWidth) {
                                        break;
                                    }
                                    $width += $charWidth;
                                    $cutIndex = $index + 1;
                                }

                                $displayName = $cutIndex < count($chars)
                                    ? mb_substr($affirmativeDebater, 0, $cutIndex) . '...'
                                    : $affirmativeDebater;
                            @endphp
                            <span class="block max-w-full overflow-hidden whitespace-nowrap">
                                {{ $displayName }}
                            </span>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex h-full items-center justify-center p-6">
                    <div class="text-md text-gray-900 font-medium animate-pulse">
                        {{ __('rooms.recruiting') }}
                    </div>
                </div>
            @endif
        </div>

        <!-- 否定側パネル -->
        <div
            class="rounded-lg shadow-sm p-1.5 w-full sm:w-auto flex-1 border-2 border-red-300 flex items-center justify-center relative">
            <div class="absolute top-2 left-4">
                <div class="text-md text-red-500 font-medium">{{ __('debates_ui.negative_side_label') }}</div>
            </div>
            @if($negativeDebater)
                <div class="{{ $negativeDebater === optional(auth()->user())->name ? 'bg-primary-light border-2 border-primary/20' : 'bg-gray-100 border-2 border-gray-300' }} rounded-lg shadow-md p-6 w-full sm:w-auto flex-1 min-w-0">
                    <div class="flex h-full items-center justify-center pt-0">
                        <div class="text-xl text-gray-900 font-medium text-center"
                             id="negative-user"
                             title="{{ $negativeDebater }}">
                            @php
                                $width = 0;
                                $chars = mb_str_split($negativeDebater);
                                $cutIndex = 0;
                                $maxWidth = 24; // 半角24文字分の幅

                                foreach ($chars as $index => $char) {
                                    $charWidth = mb_strwidth($char);
                                    if ($width + $charWidth > $maxWidth) {
                                        break;
                                    }
                                    $width += $charWidth;
                                    $cutIndex = $index + 1;
                                }

                                $displayName = $cutIndex < count($chars)
                                    ? mb_substr($negativeDebater, 0, $cutIndex) . '...'
                                    : $negativeDebater;
                            @endphp
                            <span class="block max-w-full overflow-hidden whitespace-nowrap">
                                {{ $displayName }}
                            </span>
                        </div>
                    </div>
                </div>
            @else
            <div class="flex h-full items-center justify-center p-6">
                <div class="text-md text-gray-900 font-medium animate-pulse">
                    {{ __('rooms.recruiting') }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
