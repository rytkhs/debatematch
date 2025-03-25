<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="min-h-screen bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- ディベート情報&対戦カードセクション -->
            <div class="bg-white rounded-xl shadow-sm mb-8">
                <div class="p-6">
                    <!-- ディベートトピックと基本情報 -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div>
                            <div>
                                <!-- トピック情報 -->
                                <h2 class="text-xl font-bold text-gray-900 mb-2">{{ $debate->room->topic }}</h2>
                                <p class="text-sm text-gray-600 mb-2">
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        ルーム: {{ $debate->room->name }}
                                    </span>
                                    <span class="inline-flex items-center ml-4">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        ホスト: {{ $debate->room->creator->name }}
                                    </span>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <span class="inline-flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $debate->created_at->format('Y年m月d日 H:i') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 対戦カード -->
                    <div class="flex flex-col md:flex-row gap-6 mb-6 relative">
                        <!-- 中央の対戦VS表示 -->
                        <div class="absolute left-1/2 top-1/2 transform -translate-x-1/2 -translate-y-1/2 z-10 hidden md:flex items-center justify-center w-12 h-12 bg-primary text-white rounded-full font-bold text-lg">VS</div>

                        <!-- 肯定側 -->
                        <div class="p-5 rounded-xl flex-1 {{ $debate->affirmative_user_id === Auth::id() ? 'bg-primary-light border-l-4 border-primary' : 'bg-gray-100' }} relative overflow-hidden">
                            @if($evaluations && $evaluations->winner && $evaluations->winner === 'affirmative')
                                <div class="absolute top-0 right-0 w-16 h-16">
                                    <div class="absolute transform rotate-45 bg-success text-white text-xs font-bold py-1 right-[-35px] top-[10px] w-[140px] text-center">勝者</div>
                                </div>
                            @endif
                            <h3 class="text-lg font-semibold text-primary mb-4 flex items-center">
                                肯定側
                            </h3>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-lg font-semibold">
                                        {{ substr($debate->affirmativeUser->name, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $debate->affirmativeUser->name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- 否定側 -->
                        <div class="p-5 rounded-xl flex-1 {{ $debate->negative_user_id === Auth::id() ? 'bg-primary-light border-l-4 border-primary' : 'bg-gray-100' }} relative overflow-hidden">
                            @if($evaluations && $evaluations->winner && $evaluations->winner === 'negative')
                                <div class="absolute top-0 right-0 w-16 h-16">
                                    <div class="absolute transform rotate-45 bg-success text-white text-xs font-bold py-1 right-[-35px] top-[10px] w-[140px] text-center">勝者</div>
                                </div>
                            @endif
                            <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center">
                                否定側
                            </h3>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-700 flex items-center justify-center text-lg font-semibold">
                                        {{ substr($debate->negativeUser->name, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $debate->negativeUser->name }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- タブナビゲーション -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button
                            class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 ease-in-out"
                            onclick="showTab('result')"
                            id="tab-result"
                            data-active="true"
                        >
                            <span class="material-icons align-middle mr-1">analytics</span> 結果
                        </button>
                        <button
                            class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 ease-in-out"
                            onclick="showTab('debate')"
                            id="tab-debate"
                            data-active="false"
                        >
                            <span class="material-icons align-middle mr-1">chat</span> ディベート内容
                        </button>
                    </nav>
                </div>
            </div>

            <!-- 結果タブコンテンツ -->
            <div id="content-result">
                <!-- 評価コンテンツ -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
                    <div class="p-6">
                        @if($evaluations)
                            <div class="space-y-6">
                                <!-- 論点の分析 -->
                                @if($evaluations->analysis)
                                <div class="relative">
                                    <div class="flex items-center mb-4">
                                        <span class="material-icons-outlined text-primary mr-2">psychology</span>
                                        <h3 class="text-lg font-semibold text-gray-900">論点の分析</h3>
                                    </div>
                                    <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm">
                                        <div class="leading-relaxed text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->analysis) !!}</div>
                                    </div>
                                </div>
                                @endif

                                <!-- 判定結果 -->
                                @if($evaluations->reason)
                                <div class="relative">
                                    <div class="flex items-center mb-4">
                                        <span class="material-icons-outlined text-primary mr-2">gavel</span>
                                        <h3 class="text-lg font-semibold text-gray-900">判定結果</h3>
                                    </div>
                                    <div class="bg-primary-light rounded-xl p-5 border-l-4 border-primary">
                                        <div class="leading-relaxed text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->reason) !!}</div>
                                        @if ($evaluations->winner)
                                        <div class="bg-white p-4 rounded-lg mt-4 flex items-center justify-between shadow-sm">
                                            <p class="font-medium text-gray-700">勝者:</p>
                                            <p class="font-semibold {{ $evaluations->winner === 'affirmative' ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $evaluations->winner === 'affirmative' ? '肯定側' : '否定側' }}
                                            </p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- フィードバック -->
                                @if($evaluations->feedback_for_affirmative || $evaluations->feedback_for_negative)
                                <div class="relative">
                                    <div class="flex items-center mb-4">
                                        <span class="material-icons-outlined text-primary mr-2">feedback</span>
                                        <h3 class="text-lg font-semibold text-gray-900">フィードバック</h3>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- 肯定側へのフィードバック -->
                                        @if($evaluations->feedback_for_affirmative)
                                        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm {{ $debate->affirmative_user_id === Auth::id() ? 'border-l-4 border-l-primary' : '' }}">
                                            <h4 class="text-base font-semibold {{ $debate->affirmative_user_id === Auth::id() ? 'text-primary' : 'text-gray-700' }} mb-3 flex items-center">
                                                <span class="material-icons-outlined mr-2 text-sm">person</span>肯定側へのフィードバック
                                            </h4>
                                            <div class="text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->feedback_for_affirmative) !!}</div>
                                        </div>
                                        @endif

                                        <!-- 否定側へのフィードバック -->
                                        @if($evaluations->feedback_for_negative)
                                        <div class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm {{ $debate->negative_user_id === Auth::id() ? 'border-l-4 border-l-primary' : '' }}">
                                            <h4 class="text-base font-semibold {{ $debate->negative_user_id === Auth::id() ? 'text-primary' : 'text-gray-700' }} mb-3 flex items-center">
                                                <span class="material-icons-outlined mr-2 text-sm">person</span>否定側へのフィードバック
                                            </h4>
                                            <div class="text-gray-700 prose max-w-none">{!! Str::markdown($evaluations->feedback_for_negative) !!}</div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 text-center">
                                <div class="flex items-center justify-center mb-4">
                                    <span class="material-icons-outlined text-gray-500 mr-2">sentiment_dissatisfied</span>
                                    <h3 class="text-lg font-semibold text-gray-700">評価がありません</h3>
                                </div>

                            </div>
                        @endif


                        <!-- アクションボタン -->
                        <div class="border-t border-gray-200 mt-8 pt-6 flex justify-end gap-4">
                            <a href="{{ route('records.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition duration-150 ease-in-out">
                                <i class="fas fa-history mr-2"></i>
                                ディベート履歴
                            </a>
                            <a href="{{ route('welcome') }}" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-medium rounded-lg transition duration-150 ease-in-out">
                                <i class="fas fa-home mr-2"></i>
                                ホーム
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ディベート内容タブ -->
            <div id="content-debate" class="hidden">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden h-screen flex flex-col">
                    <div class="px-3 flex-1 flex flex-col min-h-0">
                        <livewire:debates.chat :debate="$debate" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>

    <script>
        function showTab(tabId) {
            // タブの表示/非表示を切り替え
            document.getElementById('content-result').classList.toggle('hidden', tabId !== 'result');
            document.getElementById('content-debate').classList.toggle('hidden', tabId !== 'debate');

            // タブのスタイルを更新
            const tabs = {
                result: document.getElementById('tab-result'),
                debate: document.getElementById('tab-debate')
            };

            // 各タブのアクティブ状態を更新
            Object.entries(tabs).forEach(([id, tab]) => {
                const isActive = id === tabId;
                tab.setAttribute('data-active', isActive ? 'true' : 'false');
                updateTabStyle(tab);
            });
        }

        // タブスタイルを更新する関数
        function updateTabStyle(tab) {
            const isActive = tab.getAttribute('data-active') === 'true';

            if (isActive) {
                tab.classList.add('text-primary', 'border-primary');
                tab.classList.remove('text-gray-500', 'border-transparent');
            } else {
                tab.classList.add('text-gray-500', 'border-transparent');
                tab.classList.remove('text-primary', 'border-primary');
            }
        }

        // 初期化時にタブスタイルを設定
        document.addEventListener('DOMContentLoaded', function() {
            // タブスタイルの初期化
            document.querySelectorAll('.tab-button').forEach(tab => {
                updateTabStyle(tab);

                // ホバーエフェクトの追加
                tab.addEventListener('mouseenter', function() {
                    if (this.getAttribute('data-active') !== 'true') {
                        this.classList.add('text-gray-700', 'border-gray-300');
                        this.classList.remove('text-gray-500');
                    }
                });

                tab.addEventListener('mouseleave', function() {
                    if (this.getAttribute('data-active') !== 'true') {
                        this.classList.remove('text-gray-700', 'border-gray-300');
                        this.classList.add('text-gray-500');
                    }
                });
            });
        });
    </script>
</x-app-layout>
