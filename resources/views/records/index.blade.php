<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- フィルターとソート -->
        <form id="filterForm" action="{{ route('records.index') }}" method="GET" class="mb-6 flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
            <div class="flex flex-wrap items-center gap-4">
                <div class="relative">
                    <select name="side" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="all" {{ request('side') == 'all' ? 'selected' : '' }}>すべての立場</option>
                        <option value="affirmative" {{ request('side') == 'affirmative' ? 'selected' : '' }}>肯定側</option>
                        <option value="negative" {{ request('side') == 'negative' ? 'selected' : '' }}>否定側</option>
                    </select>
                </div>
                <div class="relative">
                    <select name="result" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="all" {{ request('result') == 'all' ? 'selected' : '' }}>すべての結果</option>
                        <option value="win" {{ request('result') == 'win' ? 'selected' : '' }}>勝利</option>
                        <option value="lose" {{ request('result') == 'lose' ? 'selected' : '' }}>敗北</option>
                    </select>
                </div>
                <div class="relative">
                    <select name="sort" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>新しい順</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>古い順</option>
                    </select>
                </div>
            </div>
            <div class="relative flex-1 max-w-xs">
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="論題を検索..."
                    class="filter-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
        </form>

        <!-- 履歴一覧 -->
        <div class="space-y-4">
            @foreach($debates as $debate)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    @php
                                        $resultText = '不明';
                                        $resultClass = 'bg-gray-200 text-gray-700';

                                        if ($debate->winner === 'affirmative' || $debate->winner === 'negative') {
                                            $isWinner = ($debate->winner === 'affirmative' && $debate->affirmative_user_id === Auth::id()) ||
                                                       ($debate->winner === 'negative' && $debate->negative_user_id === Auth::id());
                                            if ($isWinner) {
                                                $resultText = '勝利';
                                                $resultClass = 'bg-success-light text-success';
                                            } else {
                                                $resultText = '敗北';
                                                $resultClass = 'bg-danger-light text-danger';
                                            }
                                        }
                                    @endphp
                                    <span class="px-3 py-1 {{ $resultClass }} text-sm font-medium rounded-full">
                                        {{ $resultText }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ $debate->created_at->format('Y/m/d') }}
                                    </span>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900">{{ $debate->room->topic }}</h2>
                            </div>
                            <a href="{{ route('records.show', $debate) }}" class="btn-primary text-sm whitespace-nowrap">
                                詳細を見る
                            </a>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4 text-sm text-gray-600">
                            <div class="flex-1">
                                @php
                                    $side = '不明';
                                    $sideClass = 'text-gray-500';

                                    if ($debate->affirmative_user_id === Auth::id()) {
                                        $side = '肯定側';
                                        $sideClass = 'text-success';
                                    } elseif ($debate->negative_user_id === Auth::id()) {
                                        $side = '否定側';
                                        $sideClass = 'text-danger';
                                    }

                                    $opponent = $debate->affirmative_user_id === Auth::id()
                                        ? $debate->negativeUser->name
                                        : $debate->affirmativeUser->name;
                                @endphp
                                <p class="mb-1">
                                    <span class="font-medium {{ $sideClass }}">{{ $side }}</span>として参加
                                </p>
                                <p>vs. <span class="font-medium">{{ $opponent }}</span></p>
                            </div>
                            <div class="flex-1">
                                <p class="mb-1">Room: <span class="font-medium">{{ $debate->room->name }}</span></p>
                                <p>Host: <span class="font-medium">{{ $debate->room->creator->name }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- ページネーション -->
        <div class="mt-6">
            {{ $debates->links() }}
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filterForm');
            const filterInputs = document.querySelectorAll('.filter-input, .filter-select');

            // 入力変更時にフォームを送信
            filterInputs.forEach(input => {
                input.addEventListener('change', function() {
                    form.submit();
                });
            });

            // キーワード入力時にデバウンス処理
            const keywordInput = document.querySelector('input[name="keyword"]');
            let timeoutId;

            keywordInput.addEventListener('input', function() {
                clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    form.submit();
                }, 500);
            });
        });
    </script>
    @endpush
</x-app-layout>
