@props(['targetInputId' => 'topic'])

<!-- 論題カタログモーダル -->
<div x-data="topicCatalog()"
     x-show="isOpen"
     @open-topic-catalog.window="openModal($event.detail)"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     x-cloak>

    <!-- 背景オーバーレイ -->
    <div x-show="isOpen"
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
         @click="closeModal()"></div>

    <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
        <!-- モーダルパネル -->
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-white rounded-xl shadow-xl transform transition-all sm:max-w-4xl sm:w-full w-full max-h-[90vh] flex flex-col">

            <!-- ヘッダー -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center">
                        <span class="material-icons-outlined text-indigo-600 mr-2"
                              x-text="viewMode === 'suggestion' ? 'tips_and_updates' : (viewMode === 'catalog' ? 'library_books' : 'auto_awesome')"></span>
                        <h3 class="text-lg font-bold text-gray-900"
                            x-text="viewMode === 'suggestion' ? '{{ __('topic_catalog.suggestion_title') }}' : (viewMode === 'catalog' ? '{{ __('topic_catalog.title') }}' : '{{ __('topic_catalog.ai.section_title') }}')"></h3>
                        <template x-if="viewMode === 'ai'">
                            <span class="ml-2 px-1 py-0.5 text-[10px] font-bold leading-none text-white bg-indigo-500 rounded-full uppercase">Beta</span>
                        </template>
                    </div>
                    <button @click="closeModal()" type="button" class="text-gray-400 hover:text-gray-500 transition-colors">
                        <span class="material-icons-outlined">close</span>
                    </button>
                </div>

                <!-- タブ切り替え -->
                <div class="flex gap-1 border-b border-gray-200 -mb-4 pb-0">
                    <button @click="viewMode = 'suggestion'" type="button"
                            :class="viewMode === 'suggestion' ? 'border-indigo-500 text-indigo-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                            class="px-4 py-2 text-sm font-medium border-b-2 rounded-t-lg transition-colors">
                        <span class="material-icons-outlined text-sm align-middle mr-1">tips_and_updates</span>
                        {{ __('topic_catalog.tab_suggestion') }}
                    </button>
                    <button @click="viewMode = 'catalog'" type="button"
                            :class="viewMode === 'catalog' ? 'border-indigo-500 text-indigo-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                            class="px-4 py-2 text-sm font-medium border-b-2 rounded-t-lg transition-colors">
                        <span class="material-icons-outlined text-sm align-middle mr-1">library_books</span>
                        {{ __('topic_catalog.tab_catalog') }}
                    </button>
                    <button @click="viewMode = 'ai'" type="button"
                            :class="viewMode === 'ai' ? 'border-indigo-500 text-indigo-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-100'"
                            class="px-4 py-2 text-sm font-medium border-b-2 rounded-t-lg transition-colors flex items-center">
                        <span class="material-icons-outlined text-sm align-middle mr-1">auto_awesome</span>
                        {{ __('topic_catalog.ai.tab_title') }}
                        <span class="ml-1.5 px-1 py-0.5 text-[8px] font-bold leading-none text-white bg-indigo-500 rounded-full uppercase">Beta</span>
                    </button>
                </div>
            </div>

            <!-- コンテンツエリア -->
            <div class="flex-1 overflow-y-auto px-6 py-4 bg-gray-50">

                <!-- モード: おすすめ提案 -->
                <div x-show="viewMode === 'suggestion'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="(topic, index) in suggestions" :key="index">
                            <button @click="selectTopic(topic.text)"
                                    class="text-left w-full group bg-white p-5 rounded-lg border border-gray-200 hover:border-indigo-400 hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex flex-col h-full">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="getCategoryColor(topic.category)">
                                        <span x-text="getCategoryLabel(topic.category)"></span>
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="getDifficultyColor(topic.difficulty)">
                                        <span x-text="getDifficultyLabel(topic.difficulty)"></span>
                                    </span>
                                </div>
                                <h4 class="text-base font-medium text-gray-800 group-hover:text-indigo-700 transition-colors flex-grow" x-text="topic.text"></h4>
                            </button>
                        </template>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-2">
                        <button @click="shuffleSuggestions()" type="button" class="flex items-center text-sm text-gray-600 hover:text-indigo-600 transition-colors">
                            <span class="material-icons-outlined mr-1 spin-on-hover">cached</span>
                            {{ __('topic_catalog.refresh_suggestion') }}
                        </button>
                    </div>
                </div>

                <!-- モード: カタログ -->
                <div x-show="viewMode === 'catalog'" class="flex flex-col h-full">
                    <!-- フィルターエリア -->
                    <div class="mb-4 pb-4 border-b border-gray-200 space-y-4">
                        <!-- カテゴリタブ -->
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="filterCategory('all')"
                                    :class="{'bg-indigo-100 text-indigo-700 border-indigo-200': currentCategory === 'all', 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50': currentCategory !== 'all'}"
                                    class="px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors">
                                {{ __('topic_catalog.filter_all') }}
                            </button>
                            @foreach (__('topic_catalog.categories') as $key => $label)
                                <button type="button" @click="filterCategory('{{ $key }}')"
                                        :class="{'bg-indigo-100 text-indigo-700 border-indigo-200': currentCategory === '{{ $key }}', 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50': currentCategory !== '{{ $key }}'}"
                                        class="px-3 py-1.5 rounded-full text-xs sm:text-sm font-medium border transition-colors">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <!-- 難易度フィルター -->
                        <div class="flex items-center flex-wrap gap-2">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider mr-2">LEVEL:</span>
                            <button type="button" @click="filterDifficulty('all')"
                                    :class="{'bg-indigo-100 text-indigo-700 border-indigo-200': currentDifficulty === 'all', 'bg-white text-gray-500 border-gray-200 hover:border-gray-300': currentDifficulty !== 'all'}"
                                    class="px-3 py-1 rounded-full text-xs font-medium border transition-colors duration-150 inline-flex items-center gap-1.5">
                                {{ __('topic_catalog.filter_all') }}
                            </button>
                            <button type="button"
                                    @click="filterDifficulty('easy')"
                                    :class="currentDifficulty === 'easy'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold'
                                        : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'"
                                    class="px-3 py-1 rounded-full text-xs font-medium border transition-colors duration-150 inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                {{ __('topic_catalog.difficulties.easy') }}
                            </button>
                            <button type="button"
                                    @click="filterDifficulty('normal')"
                                    :class="currentDifficulty === 'normal'
                                        ? 'bg-amber-50 text-amber-700 border-amber-200 font-bold'
                                        : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'"
                                    class="px-3 py-1 rounded-full text-xs font-medium border transition-colors duration-150 inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                {{ __('topic_catalog.difficulties.normal') }}
                            </button>
                            <button type="button"
                                    @click="filterDifficulty('hard')"
                                    :class="currentDifficulty === 'hard'
                                        ? 'bg-rose-50 text-rose-700 border-rose-200 font-bold'
                                        : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'"
                                    class="px-3 py-1 rounded-full text-xs font-medium border transition-colors duration-150 inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                {{ __('topic_catalog.difficulties.hard') }}
                            </button>
                        </div>
                    </div>

                    <!-- リストエリア -->
                    <div class="grid grid-cols-1 gap-3">
                        <template x-if="filteredTopics.length === 0">
                            <div class="text-center py-10 text-gray-500">
                                <span class="material-icons-outlined text-4xl mb-2 text-gray-300">search_off</span>
                                <p>該当する論題が見つかりませんでした。</p>
                            </div>
                        </template>

                        <template x-for="(topic, index) in filteredTopics" :key="index">
                            <button @click="selectTopic(topic.text)"
                                    class="text-left w-full group bg-white p-4 rounded-lg border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                              :class="getCategoryColor(topic.category)">
                                            <span x-text="getCategoryLabel(topic.category)"></span>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                              :class="getDifficultyColor(topic.difficulty)">
                                            <span x-text="getDifficultyLabel(topic.difficulty)"></span>
                                        </span>
                                    </div>
                                    <h4 class="text-sm sm:text-base font-medium text-gray-800 group-hover:text-indigo-700 transition-colors" x-text="topic.text"></h4>
                                </div>
                                <div class="mt-2 sm:mt-0 sm:ml-4 text-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity flex items-center text-xs font-medium">
                                    {{ __('common.input') }} <span class="material-icons-outlined text-sm ml-1">arrow_forward</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- モード: AI生成 -->
                <div x-show="viewMode === 'ai'" class="space-y-4">
                    <!-- AI機能の説明 -->
                    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4 border border-indigo-100">
                        <p class="text-sm text-gray-600">{{ __("topic_catalog.ai.section_description") }}</p>
                    </div>

                    <!-- AI生成フォーム -->
                    <div class="space-y-4">

                        <div class="bg-white rounded-lg p-5 border border-gray-200 space-y-4">
                            <!-- キーワード入力 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('topic_catalog.ai.keywords_label') }}
                                </label>
                                <input type="text"
                                       x-model="aiKeywords"
                                       placeholder="{{ __('topic_catalog.ai.keywords_placeholder') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            </div>

                            <!-- カテゴリと難易度 -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('topic_catalog.ai.category_label') }}
                                    </label>
                                    <select x-model="aiCategory"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="all">{{ __('topic_catalog.filter_all') }}</option>
                                        @foreach (__('topic_catalog.categories') as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __('topic_catalog.ai.difficulty_label') }}
                                    </label>
                                    <select x-model="aiDifficulty"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                        <option value="all">{{ __('topic_catalog.filter_all') }}</option>
                                        @foreach (__('topic_catalog.difficulties') as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- 生成ボタン -->
                            <div class="flex justify-center pt-2">
                                <button @click="generateTopics()"
                                        :disabled="aiLoading"
                                        class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                    <template x-if="!aiLoading">
                                        <span class="flex items-center">
                                            <span class="material-icons-outlined mr-2">auto_awesome</span>
                                            {{ __('topic_catalog.ai.generate_btn') }}
                                        </span>
                                    </template>
                                    <template x-if="aiLoading">
                                        <span class="flex items-center">
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ __('topic_catalog.ai.generating') }}
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- エラー表示 -->
                    <div x-show="aiError" x-cloak class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <span class="material-icons-outlined text-red-500 mr-2">error_outline</span>
                            <p class="text-sm text-red-700" x-text="aiError"></p>
                        </div>
                    </div>

                    <!-- 生成結果 -->
                    <div x-show="aiResults.length > 0" x-cloak class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-gray-700">{{ __('topic_catalog.ai.results_title') }}</h4>
                            <button @click="generateTopics()" type="button"
                                    :disabled="aiLoading"
                                    class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center disabled:opacity-50">
                                <span class="material-icons-outlined text-sm mr-1">refresh</span>
                                {{ __('topic_catalog.ai.try_again') }}
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            <template x-for="(topic, index) in aiResults" :key="index">
                                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                    <button @click="selectTopic(topic.text)"
                                            class="text-left w-full group p-4 hover:bg-indigo-50 transition-colors">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700 border border-indigo-200">
                                                <span class="material-icons-outlined text-xs mr-1">auto_awesome</span>
                                                AI生成
                                            </span>
                                            <span x-show="topic.category"
                                                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                  :class="getCategoryColor(topic.category)">
                                                <span x-text="getCategoryLabel(topic.category)"></span>
                                            </span>
                                            <span x-show="topic.difficulty"
                                                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                  :class="getDifficultyColor(topic.difficulty)">
                                                <span x-text="getDifficultyLabel(topic.difficulty)"></span>
                                            </span>
                                        </div>
                                        <h4 class="text-base font-medium text-gray-800 group-hover:text-indigo-700 transition-colors" x-text="topic.text"></h4>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- 結果なし -->
                    <div x-show="aiResults.length === 0 && !aiLoading && aiHasSearched" x-cloak class="text-center py-8">
                        <span class="material-icons-outlined text-4xl text-gray-300 mb-2">search_off</span>
                        <p class="text-gray-500">{{ __('topic_catalog.ai.no_results') }}</p>
                    </div>

                    <!-- AIに関する注意書き -->
                    <div class="mt-8 pt-4 border-t border-gray-100">
                        <div class="flex items-center gap-1.5 text-gray-400">
                            <span class="material-icons-outlined text-[14px]">info</span>
                            <p class="text-[11px]">{{ __('topic_catalog.ai.caution') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- フッター -->
            <div class="px-6 py-3 border-t border-gray-200 bg-white rounded-b-xl flex justify-end">
                <button @click="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                    {{ __('common.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function topicCatalog() {
        return {
            isOpen: false,
            viewMode: 'suggestion', // 'suggestion', 'catalog', or 'ai'
            currentCategory: 'all',
            currentDifficulty: 'all',
            topics: @json(__('topic_catalog.topics')),
            categories: @json(__('topic_catalog.categories')),
            difficulties: @json(__('topic_catalog.difficulties')),
            targetInputId: '{{ $targetInputId }}',
            suggestions: [],
            processedTopics: [],

            // AI機能用のstate
            aiKeywords: '',
            aiCategory: 'all',
            aiDifficulty: 'all',
            aiResults: [],
            aiLoading: false,
            aiError: null,
            aiHasSearched: false,
            aiAbortController: null, // リクエストキャンセル用

            init() {
                // 初期状態
                this.currentDifficulty = 'all';
                // データをオブジェクト形式に整形して保持
                this.processedTopics = this.topics;
                this.shuffleSuggestions();
            },

            openModal(detail) {
                this.isOpen = true;
                // モーダルを開くたびに提案モード・初期状態にリセットしたい場合は以下を有効化
                this.viewMode = 'suggestion';
                this.shuffleSuggestions();
                // AI関連のstateをリセット
                this.aiError = null;

                if (detail && detail.targetId) {
                    this.targetInputId = detail.targetId;
                    console.log('Target ID set to:', this.targetInputId);
                }
            },

            closeModal() {
                // 進行中のAIリクエストをキャンセル
                if (this.aiAbortController) {
                    this.aiAbortController.abort();
                    this.aiAbortController = null;
                    this.aiLoading = false;
                }
                this.isOpen = false;
            },

            filterCategory(category) {
                this.currentCategory = category;
            },

            filterDifficulty(difficulty) {
                this.currentDifficulty = difficulty;
            },

            shuffleSuggestions() {
                // 配列をシャッフルして最初の4つを取得
                const shuffled = [...this.processedTopics].sort(() => 0.5 - Math.random());
                this.suggestions = shuffled.slice(0, 4);
            },

            get filteredTopics() {
                return this.processedTopics.filter(topic => {
                    const categoryMatch = this.currentCategory === 'all' || topic.category === this.currentCategory;
                    const difficultyMatch = this.currentDifficulty === 'all' || topic.difficulty === this.currentDifficulty;
                    return categoryMatch && difficultyMatch;
                });
            },

            getCategoryLabel(key) {
                return this.categories[key] || key;
            },

            getDifficultyLabel(key) {
                return this.difficulties[key] || key;
            },

            getCategoryColor(key) {
                const colors = {
                    'politics': 'bg-red-50 text-red-700 border-red-200 border',
                    'business': 'bg-emerald-50 text-emerald-700 border-emerald-200 border',
                    'technology': 'bg-indigo-50 text-indigo-700 border-indigo-200 border',
                    'education': 'bg-blue-50 text-blue-700 border-blue-200 border',
                    'philosophy': 'bg-purple-50 text-purple-700 border-purple-200 border',
                    'entertainment': 'bg-pink-50 text-pink-700 border-pink-200 border',
                    'lifestyle': 'bg-cyan-50 text-cyan-700 border-cyan-200 border',
                    'other': 'bg-gray-50 text-gray-700 border-gray-200 border',
                };
                return colors[key] || 'bg-gray-50 text-gray-600 border-gray-200 border';
            },

            getDifficultyColor(key) {
                const colors = {
                    'easy': 'bg-emerald-50 text-emerald-700 border-emerald-200 border',
                    'normal': 'bg-amber-50 text-amber-700 border-amber-200 border',
                    'hard': 'bg-rose-50 text-rose-700 border-rose-200 border',
                };
                return colors[key] || 'bg-gray-100 text-gray-700 border-gray-200 border';
            },

            selectTopic(text) {
                const input = document.getElementById(this.targetInputId);
                console.log('Selecting topic:', text, 'for input:', this.targetInputId);
                if (input) {
                    input.value = text;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    console.error('Input element not found:', this.targetInputId);
                }
                this.closeModal();
            },

            // AI論題生成
            async generateTopics() {
                // ダブルクリック防止: 既にローディング中なら無視
                if (this.aiLoading) {
                    return;
                }

                // 前回のリクエストをキャンセル
                if (this.aiAbortController) {
                    this.aiAbortController.abort();
                }

                this.aiLoading = true;
                this.aiError = null;
                this.aiHasSearched = true;

                // 新しいAbortController作成
                this.aiAbortController = new AbortController();

                try {
                    // 言語を判定
                    const language = document.documentElement.lang === 'en' ? 'english' : 'japanese';

                    const response = await fetch('{{ route("api.ai.topics.generate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            keywords: this.aiKeywords || null,
                            category: this.aiCategory !== 'all' ? this.aiCategory : null,
                            difficulty: this.aiDifficulty !== 'all' ? this.aiDifficulty : null,
                            language: language,
                        }),
                        signal: this.aiAbortController.signal,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || '{{ __("topic_catalog.ai.generation_failed") }}');
                    }

                    if (data.success && data.topics) {
                        this.aiResults = data.topics;
                    } else {
                        this.aiResults = [];
                    }
                } catch (error) {
                    // AbortErrorは無視（ユーザーがキャンセルした場合）
                    if (error.name === 'AbortError') {
                        console.log('Topic generation request was cancelled');
                        return;
                    }
                    console.error('Topic generation error:', error);
                    this.aiError = error.message || '{{ __("topic_catalog.ai.generation_failed") }}';
                    this.aiResults = [];
                } finally {
                    this.aiLoading = false;
                    this.aiAbortController = null;
                }
            },
        }
    }
</script>

<style>
.spin-on-hover:hover {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
