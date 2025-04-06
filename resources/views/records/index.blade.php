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
        <div class="mb-5 sm:mb-6">
            <div class="flex items-center justify-between mb-3 sm:mb-4">
                {{-- <h2 class="text-lg font-semibold text-gray-900">フィルターと検索</h2> --}}
            </div>

            <form id="filterForm" action="{{ route('records.index') }}" method="GET" class="mb-5 sm:mb-6 flex flex-col sm:flex-row gap-3 sm:gap-4 items-start sm:items-center justify-between">
                <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                    <div class="relative">
                        <select name="side" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-3 sm:px-4 py-1.5 sm:py-2 pr-7 sm:pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
                            <option value="all" {{ request('side') == 'all' ? 'selected' : '' }}>すべての立場</option>
                            <option value="affirmative" {{ request('side') == 'affirmative' ? 'selected' : '' }}>肯定側</option>
                            <option value="negative" {{ request('side') == 'negative' ? 'selected' : '' }}>否定側</option>
                        </select>
                    </div>
                    <div class="relative">
                        <select name="result" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-3 sm:px-4 py-1.5 sm:py-2 pr-7 sm:pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
                            <option value="all" {{ request('result') == 'all' ? 'selected' : '' }}>すべての結果</option>
                            <option value="win" {{ request('result') == 'win' ? 'selected' : '' }}>勝利</option>
                            <option value="lose" {{ request('result') == 'lose' ? 'selected' : '' }}>敗北</option>
                        </select>
                    </div>
                    <div class="relative">
                        <select name="sort" class="filter-select appearance-none bg-white border border-gray-300 rounded-lg px-3 sm:px-4 py-1.5 sm:py-2 pr-7 sm:pr-8 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
                            <option value="newest" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>新しい順</option>
                            <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>古い順</option>
                        </select>
                    </div>
                    <div class="relative flex-1 max-w-xs">
                        <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="論題を検索..."
                            class="filter-input w-full px-3 sm:px-4 py-1.5 sm:py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-xs sm:text-sm">
                    </div>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="resetFilters" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        リセット
                    </button>
                    <button type="submit" class="px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-white bg-primary border border-transparent rounded-md shadow-sm hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        適用
                    </button>
                </div>
            </form>
        </div>

        <!-- 表示件数と検索情報 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-3 sm:mb-4">
            <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-0">
                {{ $debates->total() }}件中 {{ $debates->firstItem() ?? 0 }}〜{{ $debates->lastItem() ?? 0 }}件表示
                @if(request('keyword') || request('side') != 'all' || request('result') != 'all')
                    <span class="font-semibold">（フィルター適用中）</span>
                @endif
            </p>
            <div class="hidden md:flex items-center">
                <span class="mr-2 text-xs sm:text-sm text-gray-600">表示形式:</span>
                <button id="viewGrid" class="p-1.5 sm:p-2 text-gray-600 hover:text-primary focus:outline-none view-button active">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                </button>
                <button id="viewList" class="p-1.5 sm:p-2 text-gray-600 hover:text-primary focus:outline-none view-button">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- 履歴一覧 (グリッドビュー) -->
        <div id="gridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">
            @forelse($debates as $debate)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-all duration-200">
                    <!-- カードヘッダー -->
                    <div class="p-3 sm:p-4 border-b border-gray-100">
                        @php
                            $isAffirmative = $debate->affirmative_user_id === Auth::id();
                            $isWinner = ($debate->evaluations->winner === 'affirmative' && $isAffirmative) ||
                                       ($debate->evaluations->winner === 'negative' && !$isAffirmative);

                            $resultClass = $isWinner
                                ? 'bg-success-light text-success border-success/30'
                                : 'bg-danger-light text-danger border-danger/30';
                            $resultText = $isWinner ? '勝利' : '敗北';
                            $resultIcon = $isWinner
                                ? '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>'
                                : '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';

                            $side = $isAffirmative ? '肯定側' : '否定側';
                            $sideClass = $isAffirmative ? 'text-success' : 'text-danger';
                            $opponent = $isAffirmative ? $debate->negativeUser->name ?? '不明' : $debate->affirmativeUser->name ?? '不明';
                        @endphp

                        <!-- 状態と日付 -->
                        <div class="flex justify-between items-center mb-2 sm:mb-3">
                            <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full text-xs font-medium border {{ $resultClass }}">
                                {!! $resultIcon !!}{{ $resultText }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $debate->created_at->format('Y/m/d') }}</span>
                        </div>

                        <!-- 論題 -->
                        <h3 class="text-sm sm:text-base font-medium text-gray-900 mb-1 line-clamp-2">{{ $debate->room->topic }}</h3>
                        <p class="text-xs text-gray-500">Room: {{ $debate->room->name }}</p>
                    </div>

                    <!-- カード情報 -->
                    <div class="p-3 sm:p-4 bg-gray-50 flex-grow">
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-2 sm:mb-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5 sm:mb-1">あなたの立場</p>
                                <p class="text-xs sm:text-sm font-medium {{ $sideClass }}">{{ $side }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5 sm:mb-1">対戦相手</p>
                                <p class="text-xs sm:text-sm font-medium">{{ $opponent }}</p>
                            </div>
                        </div>

                        @if($debate->evaluations)
                            <div class="text-xs text-gray-600 mb-0.5 sm:mb-1">
                                <span class="font-medium">評価: </span>
                            </div>
                            <p class="text-xs text-gray-600 line-clamp-2">
                                {{ Str::limit($isAffirmative ? $debate->evaluations->feedback_for_affirmative : $debate->evaluations->feedback_for_negative, 80) }}
                            </p>
                        @endif
                    </div>

                    <!-- カードフッター -->
                    <div class="p-2 sm:p-3 bg-gray-50 border-t border-gray-100">
                        <a href="{{ route('records.show', $debate) }}" class="w-full inline-flex justify-center items-center px-3 sm:px-4 py-1.5 sm:py-2 border border-primary rounded-md shadow-sm text-xs sm:text-sm font-medium text-primary bg-white hover:bg-primary hover:text-white transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            詳細を見る
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-lg shadow-sm p-6 sm:p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 sm:h-12 sm:w-12 mx-auto text-gray-400 mb-3 sm:mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">ディベート履歴がありません</h3>
                    <a href="{{ route('rooms.index') }}" class="btn-primary inline-flex items-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        ディベートルームを探す
                    </a>
                </div>
            @endforelse
        </div>

        <!-- 履歴一覧 (リストビュー) -->
        <div id="listView" class="space-y-3 sm:space-y-4 mb-5 sm:mb-6 hidden">
            @forelse($debates as $debate)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 hover:shadow-md transition-all duration-200">
                    <div class="p-4 sm:p-5">
                        @php
                            $isAffirmative = $debate->affirmative_user_id === Auth::id();
                            $isWinner = ($debate->evaluations->winner === 'affirmative' && $isAffirmative) ||
                                       ($debate->evaluations->winner === 'negative' && !$isAffirmative);

                            $resultClass = $isWinner
                                ? 'bg-success-light text-success border-success/30'
                                : 'bg-danger-light text-danger border-danger/30';
                            $resultText = $isWinner ? '勝利' : '敗北';

                            $side = $isAffirmative ? '肯定側' : '否定側';
                            $sideClass = $isAffirmative ? 'text-success' : 'text-danger';
                            $opponent = $isAffirmative ? $debate->negativeUser->name ?? '不明' : $debate->affirmativeUser->name ?? '不明';
                        @endphp

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 mb-3 sm:mb-4">
                            <div>
                                <div class="flex items-center gap-2 sm:gap-3 mb-2">
                                    <span class="inline-flex items-center px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full text-xs font-medium border {{ $resultClass }}">
                                        @if($isWinner)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        @endif
                                        {{ $resultText }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $debate->created_at->format('Y/m/d') }}</span>
                                </div>
                                <h2 class="text-base sm:text-lg font-medium text-gray-900 mb-1">{{ $debate->room->topic }}</h2>
                            </div>
                            <a href="{{ route('records.show', $debate) }}" class="sm:self-start inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 border border-primary rounded-md shadow-sm text-xs sm:text-sm font-medium text-primary bg-white hover:bg-primary hover:text-white transition-colors duration-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                詳細を見る
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 text-xs sm:text-sm text-gray-600">
                            <div>
                                <p class="mb-1">
                                    <span class="font-medium {{ $sideClass }}">{{ $side }}</span>として参加
                                </p>
                                <p>vs. <span class="font-medium">{{ $opponent }}</span></p>
                            </div>
                            <div>
                                <p class="mb-1">Room: <span class="font-medium">{{ $debate->room->name }}</span></p>
                                <p>Host: <span class="font-medium">{{ $debate->room->creator->name }}</span></p>
                            </div>
                            @if($debate->evaluations)
                                <div>
                                    <p class="mb-1">評価:</p>
                                    <p class="text-xs sm:text-sm line-clamp-2">{{ Str::limit($isAffirmative ? $debate->evaluations->feedback_for_affirmative : $debate->evaluations->feedback_for_negative, 100) }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg shadow-sm p-6 sm:p-8 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 sm:h-12 sm:w-12 mx-auto text-gray-400 mb-3 sm:mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-2">ディベート履歴がありません</h3>
                    <a href="{{ route('rooms.index') }}" class="btn-primary inline-flex items-center text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

        // デフォルトでグリッドビューを表示
        window.addEventListener('DOMContentLoaded', function() {
            gridView.classList.remove('hidden');
            listView.classList.add('hidden');
            viewGridButton.classList.add('active');
            viewListButton.classList.remove('active');
        });
    </script>
</x-app-layout>
