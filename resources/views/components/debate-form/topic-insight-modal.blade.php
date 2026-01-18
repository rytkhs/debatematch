@props(['targetInputId' => 'topic'])

<!-- 論題インサイト（背景情報）モーダル -->
<div x-data="topicInsight()"
     x-show="isOpen"
     @open-topic-insight.window="openModal($event.detail)"
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
             class="bg-white rounded-xl shadow-xl transform transition-all sm:max-w-3xl sm:w-full w-full max-h-[90vh] flex flex-col">

            <!-- ヘッダー -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-xl flex items-center justify-between">
                <div class="flex items-center">
                    <span class="material-icons-outlined text-purple-600 mr-2">analytics</span>
                    <h3 class="text-lg font-bold text-gray-900">{{ __('topic_catalog.ai.info_title') ?? '論題分析・インサイト' }}</h3>
                </div>
                <button @click="closeModal()" type="button" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <span class="material-icons-outlined">close</span>
                </button>
            </div>

            <!-- コンテンツエリア -->
            <div class="flex-1 overflow-y-auto px-6 py-4 bg-white">

                <!-- 論題表示・再入力 -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('topic_catalog.ai.info_topic') }}
                    </label>
                    <div class="flex gap-2">
                        <input type="text"
                               x-model="topic"
                               @keydown.enter="getTopicInfo()"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                               placeholder="{{ __('rooms.placeholder_topic') }}">
                        <button @click="getTopicInfo()"
                                :disabled="isLoading || !topic.trim()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="material-icons-outlined text-sm mr-1">refresh</span>
                            {{ __('topic_catalog.ai.analyze_btn') ?? '分析' }}
                        </button>
                    </div>
                </div>

                <!-- ローディング表示 -->
                <div x-show="isLoading" class="py-12 flex flex-col items-center justify-center text-gray-500">
                    <svg class="animate-spin h-8 w-8 text-indigo-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm">{{ __('topic_catalog.ai.getting_info') }}</p>
                </div>

                <!-- エラー表示 -->
                <div x-show="error" x-cloak class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center">
                        <span class="material-icons-outlined text-red-500 mr-2">error_outline</span>
                        <p class="text-sm text-red-700" x-text="error"></p>
                    </div>
                </div>

                <!-- 分析結果表示 -->
                <div x-show="!isLoading && result" x-cloak class="space-y-6 animate-fade-in">

                    <!-- 解説 -->
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <h5 class="text-sm font-bold text-gray-800 mb-2 flex items-center">
                            <span class="material-icons-outlined text-sm mr-1 text-gray-600">description</span>
                            {{ __('topic_catalog.ai.info_description_label') }}
                        </h5>
                        <p class="text-sm text-gray-700 leading-relaxed" x-text="result?.description"></p>
                    </div>

                    <!-- 肯定側・否定側の論点 -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-100">
                            <h5 class="text-sm font-bold text-emerald-800 mb-3 flex items-center">
                                <span class="material-icons-outlined text-sm mr-1">thumb_up</span>
                                {{ __('topic_catalog.ai.info_affirmative_points') }}
                            </h5>
                            <ul class="space-y-2">
                                <template x-for="(point, idx) in (result?.key_points?.affirmative || [])" :key="idx">
                                    <li class="flex items-start text-sm text-gray-700">
                                        <span class="text-emerald-500 mr-2 flex-shrink-0 mt-0.5">•</span>
                                        <span x-text="point"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div class="bg-rose-50 rounded-lg p-4 border border-rose-100">
                            <h5 class="text-sm font-bold text-rose-800 mb-3 flex items-center">
                                <span class="material-icons-outlined text-sm mr-1">thumb_down</span>
                                {{ __('topic_catalog.ai.info_negative_points') }}
                            </h5>
                            <ul class="space-y-2">
                                <template x-for="(point, idx) in (result?.key_points?.negative || [])" :key="idx">
                                    <li class="flex items-start text-sm text-gray-700">
                                        <span class="text-rose-500 mr-2 flex-shrink-0 mt-0.5">•</span>
                                        <span x-text="point"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 結果なし (初期状態以外で結果がnullの場合) -->
                <div x-show="!isLoading && !result && !error && topic" x-cloak class="text-center py-10 text-gray-500">
                     <p>分析ボタンを押して情報の取得を開始してください。</p>
                </div>
            </div>

            <!-- フッター -->
            <div class="px-6 py-3 border-t border-gray-200 bg-white rounded-b-xl flex justify-end items-center">
                <div class="flex gap-3">
                    <button @click="useAsTopic()"
                            x-show="result && topic !== initialTopic"
                            class="px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors">
                        この論題に変更する
                    </button>
                    <button @click="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors">
                        {{ __('common.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function topicInsight() {
        return {
            isOpen: false,
            targetInputId: '{{ $targetInputId }}',
            topic: '',
            initialTopic: '',
            result: null,
            isLoading: false,
            error: null,
            abortController: null,

            init() {},

            openModal(detail) {
                this.isOpen = true;
                this.error = null;

                if (detail && detail.targetId) {
                    this.targetInputId = detail.targetId;
                }

                // 入力欄から論題を取得
                const input = document.getElementById(this.targetInputId);
                if (input && input.value) {
                    this.topic = input.value;
                    this.initialTopic = input.value;
                    // 論題がある場合は自動で分析開始
                    if (this.topic.trim()) {
                        this.getTopicInfo();
                    }
                } else {
                    this.topic = '';
                    this.initialTopic = '';
                    this.result = null;
                }
            },

            closeModal() {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                    this.isLoading = false;
                }
                this.isOpen = false;
            },

            useAsTopic() {
                const input = document.getElementById(this.targetInputId);
                if (input) {
                    input.value = this.topic;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    this.initialTopic = this.topic; // 更新完了
                }
                this.closeModal();
            },

            async getTopicInfo() {
                if (this.isLoading || !this.topic.trim()) {
                    return;
                }

                if (this.abortController) {
                    this.abortController.abort();
                }

                this.isLoading = true;
                this.error = null;
                this.result = null;
                this.abortController = new AbortController();

                try {
                    const language = document.documentElement.lang === 'en' ? 'english' : 'japanese';

                    const response = await fetch('{{ route("api.ai.topics.insight") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            topic: this.topic,
                            language: language,
                        }),
                        signal: this.abortController.signal,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || '{{ __("topic_catalog.ai.generation_failed") }}');
                    }

                    if (data.success && data.info) {
                        this.result = data.info;
                    }
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    console.error('Topic info error:', error);
                    this.error = error.message || '{{ __("topic_catalog.ai.generation_failed") }}';
                } finally {
                    this.isLoading = false;
                    this.abortController = null;
                }
            }
        }
    }
</script>
