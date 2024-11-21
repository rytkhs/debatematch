<div x-data="debateInfoComponent()">

    <div class="mb-10">
        @php
        $currentUserId = auth()->id();
        @endphp

        <h2 class="text-3xl font-semibold mb-2"><span x-text="turnName"></span></h2>
        @if($next_turn_name)
        <p class="text-gray-600 mb-2">Next : <span x-text="nextTurnName"></span></p>

        @endif

        @if(
            ($current_speaker === 'affirmative' && $affirmativeUser->id === $currentUserId) ||
            ($current_speaker === 'negative' && $negativeUser->id === $currentUserId)
            )
        <div class="text-xl text-red-600 mt-3">あなたのターンです</div>
        @else
        <div class="text-lg text-blue-500 mt-3">相手のターンです</div>
        @endif

        <div class="text-3xl font-bold mb-2 mt-4" x-text="countdownText"></div>


        @if(
        ($current_speaker === 'affirmative' && $affirmativeUser->id === $currentUserId) ||
        ($current_speaker === 'negative' && $negativeUser->id === $currentUserId)
        )
        <div>

            <button
                x-on:click="if(confirm(`{{$turn_name}}を終了し、{{$next_turn_name}}に進みます。\nターンを終了してよろしいですか?`)) { @this.advanceTurnManually() }"
                class="mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                ターンを終了
            </button>
            <div wire:loading>通信中</div>
        </div>
        @endif

    <h3 class="text-lg font-semibold mb-1 mt-6">ディベーター</h3>
    <ul>
        <li class="flex items-center mb-2 p-2 rounded @if($current_speaker === 'affirmative') bg-blue-100 @endif">
            <div>
                <div class="font-medium">{{ $affirmativeUser->name }}</div>
                <div class="text-gray-600 ml-2">肯定側</div>
            </div>
        </li>
        <li class="flex items-center mb-2 p-2 rounded @if($current_speaker === 'negative') bg-blue-100 @endif">
            <div>
                <div class="font-semibold">{{ $negativeUser->name }}</div>
                <div class="text-gray-600 ml-2">否定側</div>
            </div>
        </li>
    </ul>
</div>

<script>
    function debateInfoComponent() {
        return {
            turnName: @json($turn_name),
            nextTurnName: @json($next_turn_name),
            turnEndTime: @json($turn_end_time),
            roomId: @json($debate->room_id),
            countdownText: '',
            timer: null,
            init() {
                this.startCountdown(this.turnEndTime);

                const channel = Echo.channel(`debate.${this.roomId}`);

                channel.listen('TurnAdvanced', (e) => {
                    this.turnName = e.turn_name;
                    this.nextTurnName = e.next_turn_name;
                    this.turnEndTime = e.turn_end_time;
                    this.startCountdown(this.turnEndTime);
                });
            },
            startCountdown(endTimestamp) {
                if (this.timer) {
                    clearInterval(this.timer);
                }
                const endTime = endTimestamp * 1000; // ミリ秒に変換

                this.timer = setInterval(() => {
                    const now = new Date().getTime();
                    const distance = endTime - now;

                    if (distance <= 0) {
                        clearInterval(this.timer);
                        this.countdownText = "ターン終了";
                        return;
                    }

                    const minutes = Math.floor((distance / 1000 / 60) % 60);
                    const seconds = Math.floor((distance / 1000) % 60);

                    this.countdownText = `${minutes}:${seconds}`;
                }, 1000);
            },
        }
    }
</script>
