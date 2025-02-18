<!-- メッセージ表示エリア -->
<div class="flex-1 p-4 bg-white" id="chat-messages">
    <div>
        @forelse ($messages as $message)
            @if(isset($turns[$message->turn]))
            <div class="text-md mb-1font-medium {{ $message->user_id === Auth::id() ? 'text-right text-primary' : 'text-left text-gray-600' }}">
                {{ $turns[$message->turn]['name'] }}
            </div>
            @endif
            <div class="flex {{ $message->user_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                <div class="inline-block max-w-full md:max-w-[95%] rounded-lg p-3 {{ $message->user_id === Auth::id() ? 'chat-bubble-right' : 'chat-bubble-left' }}">
                    <div class="flex flex-col">
                        <span>{{ $message->message }}</span>
                        <span class="text-xs text-gray-600 mt-1 {{ $message->user_id === Auth::id() ? 'text-right' : 'text-left' }}">{{ $message->user->name }}</span>
                    </div>
                </div>
            </div>
            <div class="text-xs text-gray-500 mx-1 {{ $message->user_id === Auth::id() ? 'text-right' : 'text-left' }} {{ $loop->last ? 'mb-4' : 'mb-4' }}">
                {{ $message->created_at->format('H:i') }}
            </div>
            @empty
            <div class="text-center text-gray-500 mt-4">
                {{-- メッセージがありません --}}
            </div>
            @endforelse
            <div class="h-[5.5rem] bg-transparent"></div>
    </div>
</div>
