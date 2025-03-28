<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="max-w-7xl min-h-screen mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 統計概要 -->
        {{-- <div class="mb-8 bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">あなたの戦績</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-primary">
                    <p class="text-sm text-gray-500">総ディベート数</p>
                    <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-success">
                    <p class="text-sm text-gray-500">勝利数</p>
                    <p class="text-2xl font-bold">{{ $stats['wins'] }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-danger">
                    <p class="text-sm text-gray-500">敗北数</p>
                    <p class="text-2xl font-bold">{{ $stats['losses'] }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-warning">
                    <p class="text-sm text-gray-500">勝率</p>
                    <p class="text-2xl font-bold">{{ $stats['win_rate'] }}%</p>
                </div>
            </div>
        </div> --}}

        <!-- フィルターとソート -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                {{-- <h2 class="text-lg font-semibold text-gray-900">フィルターと検索</h2> --}}
            </div>

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
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="論題を検索..."
                            class="filter-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="resetFilters" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        リセット
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md shadow-sm hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        適用
                    </button>
                </div>
            </form>
        </div>

        <!-- 表示件数と検索情報 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
            <p class="text-sm text-gray-600 mb-2 sm:mb-0">
                {{ $debates->total() }}件中 {{ $debates->firstItem() ?? 0 }}〜{{ $debates->lastItem() ?? 0 }}件表示
                @if(request('keyword') || request('side') != 'all' || request('result') != 'all')
                    <span class="font-semibold">（フィルター適用中）</span>
                @endif
            </p>
            <div class="flex items-center">
                <span class="mr-2 text-sm text-gray-600">表示形式:</span>
                <button id="viewList" class="p-2 text-gray-600 hover:text-primary focus:outline-none view-button active">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <button id="viewGrid" class="p-2 text-gray-600 hover:text-primary focus:outline-none view-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- 履歴一覧 (グリッドビュー) -->
        <div id="gridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 hidden">
            @forelse($debates as $debate)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300 flex flex-col">
                    <div class="p-4 border-b border-gray-100">
                        @php
                            $resultText = '不明';
                            $resultClass = 'bg-gray-200 text-gray-700';
                            $resultIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';

                            if ($debate->evaluations->winner === 'affirmative' || $debate->evaluations->winner === 'negative') {
                                $isWinner = ($debate->evaluations->winner === 'affirmative' && $debate->affirmative_user_id === Auth::id()) ||
                                           ($debate->evaluations->winner === 'negative' && $debate->negative_user_id === Auth::id());
                                if ($isWinner) {
                                    $resultText = '勝利';
                                    $resultClass = 'bg-success-light text-success';
                                    $resultIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
                                } else {
                                    $resultText = '敗北';
                                    $resultClass = 'bg-danger-light text-danger';
                                    $resultIcon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
                                }
                            }

                            $side = '不明';
                            $sideClass = 'text-gray-500';
                            $opponent = '不明';

                            if ($debate->affirmative_user_id === Auth::id()) {
                                $side = '肯定側';
                                $sideClass = 'text-success';
                                $opponent = $debate->negativeUser->name ?? '不明';
                            } elseif ($debate->negative_user_id === Auth::id()) {
                                $side = '否定側';
                                $sideClass = 'text-danger';
                                $opponent = $debate->affirmativeUser->name ?? '不明';
                            }
                        @endphp
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $resultClass }}">
                                {!! $resultIcon !!}
                                <span class="ml-1">{{ $resultText }}</span>
                            </span>
                            <span class="text-xs text-gray-500">{{ $debate->created_at->format('Y/m/d') }}</span>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 mb-1 line-clamp-2">{{ $debate->room->topic }}</h3>
                        <p class="text-xs text-gray-500 mb-2">Room: {{ $debate->room->name }}</p>
                    </div>
                    <div class="p-4 bg-gray-50 flex-grow">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">あなたの立場</p>
                                <p class="text-sm font-medium {{ $sideClass }}">{{ $side }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">対戦相手</p>
                                <p class="text-sm font-medium">{{ $opponent }}</p>
                            </div>
                        </div>
                        @if($debate->evaluations)
                            <div class="text-xs text-gray-600 mb-3 line-clamp-2">
                                <span class="font-medium">評価: </span>
                                {{ Str::limit($debate->evaluations->feedback_for_affirmative ?? $debate->evaluations->feedback_for_negative, 80) }}
                            </div>
                        @endif
                    </div>
                    <div class="p-3 bg-gray-50 border-t border-gray-100">
                        <a href="{{ route('records.show', $debate) }}" class="w-full btn-primary-outline text-xs flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            詳細を見る
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-lg shadow-sm p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">ディベート履歴がありません</h3>
                    {{-- <p class="text-gray-500 mb-4">新しいディベートに参加して、あなたの議論スキルを磨きましょう！</p> --}}
                    <a href="{{ route('rooms.index') }}" class="btn-primary inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        ディベートルームを探す
                    </a>
                </div>
            @endforelse
        </div>

        <!-- 履歴一覧 (リストビュー) -->
        <div id="listView" class="space-y-4 mb-6">
            @forelse($debates as $debate)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <div class="p-6">
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

                            $side = '不明';
                            $sideClass = 'text-gray-500';

                            if ($debate->affirmative_user_id === Auth::id()) {
                                $side = '肯定側';
                                $sideClass = 'text-success';
                                $opponent = $debate->negativeUser->name ?? '不明';
                            } elseif ($debate->negative_user_id === Auth::id()) {
                                $side = '否定側';
                                $sideClass = 'text-danger';
                                $opponent = $debate->affirmativeUser->name ?? '不明';
                            }
                        @endphp
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
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
                                <p class="mb-1">
                                    <span class="font-medium {{ $sideClass }}">{{ $side }}</span>として参加
                                </p>
                                <p>vs. <span class="font-medium">{{ $opponent }}</span></p>
                            </div>
                            <div class="flex-1">
                                <p class="mb-1">Room: <span class="font-medium">{{ $debate->room->name }}</span></p>
                                <p>Host: <span class="font-medium">{{ $debate->room->creator->name }}</span></p>
                            </div>
                            @if($debate->evaluations)
                                <div class="flex-1">
                                    <p class="mb-1">評価:</p>
                                    <p class="text-sm line-clamp-1">{{ Str::limit($debate->evaluations->feedback_for_affirmative ?? $debate->evaluations->feedback_for_negative, 60) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">ディベート履歴がありません</h3>
                    {{-- <p class="text-gray-500 mb-4">新しいディベートに参加して、あなたの議論スキルを磨きましょう！</p> --}}
                    <a href="{{ route('rooms.index') }}" class="btn-primary inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        ディベートルームを探す
                    </a>
                </div>
            @endforelse
        </div>

        <!-- ページネーション -->
        <div class="mt-6">
            {{ $debates->links() }}
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>

    <script>
        const form = document.getElementById('filterForm');
        const filterInputs = document.querySelectorAll('.filter-select');
        const viewGridButton = document.getElementById('viewGrid');
        const viewListButton = document.getElementById('viewList');
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');

        // フィルター入力変更時にフォームを送信
        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                form.submit();
            });
        });

        // フィルターリセットボタンの処理
        document.getElementById('resetFilters').addEventListener('click', function() {
            // 選択されているフィルターをクリア
            document.querySelector('select[name="side"]').value = 'all';
            document.querySelector('select[name="result"]').value = 'all';
            document.querySelector('select[name="sort"]').value = 'newest';
            document.querySelector('input[name="keyword"]').value = '';

            // フォームを送信
            form.submit();
        });

        // ビュー切り替えボタンのイベントリスナー
        viewListButton.addEventListener('click', function() {
            listView.classList.remove('hidden');
            gridView.classList.add('hidden');
            viewListButton.classList.add('active');
            viewGridButton.classList.remove('active');
        });

        viewGridButton.addEventListener('click', function() {
            gridView.classList.remove('hidden');
            listView.classList.add('hidden');
            viewGridButton.classList.add('active');
            viewListButton.classList.remove('active');
        });
    </script>
</x-app-layout>
