<x-app-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-white min-h-screen">
        <x-slot name="header">
            <x-header></x-header>
        </x-slot>

        <div class="max-w-6xl mx-auto py-6 sm:py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="px-3 py-4 sm:px-4 sm:py-5">
                    <div class="flex items-center mb-6 sm:mb-8 border-b pb-3 sm:pb-4">
                        <span class="material-icons-outlined text-indigo-600 text-xl sm:text-2xl mr-2 sm:mr-3">smart_toy</span>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-700">{{ __('messages.start_ai_debate') }}</h1>
                    </div>

                    <form action="{{ route('ai.debate.store') }}" method="POST" class="space-y-6 sm:space-y-8">
                        @csrf

                        <!-- セクション1: 基本情報 -->
                        <div class="bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h2 class="text-base sm:text-lg font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
                                <span class="material-icons-outlined text-indigo-500 mr-2">info</span>{{
                                __('messages.basic_information') }}
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- 論題 -->
                                <div class="md:col-span-2">
                                    <label for="topic"
                                        class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">{{
                                        __('messages.topic') }} <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span
                                                class="material-icons-outlined text-gray-400 text-xs sm:text-sm">subject</span>
                                        </div>
                                        <input type="text" id="topic" name="topic" value="{{ old('topic') }}"
                                            placeholder="{{ __('messages.placeholder_topic') }}" required
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md">
                                        <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">{{ __('messages.topic_guideline') }}</p>
                                </div>

                                <!-- 言語設定 -->
                                <div>
                                    <label for="language"
                                        class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">{{
                                        __('messages.language') }} <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span
                                                class="material-icons-outlined text-gray-400 text-xs sm:text-sm">language</span>
                                        </div>
                                        <select id="language" name="language"
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md">
                                            <option value="english">{{ __('messages.english') }}</option>
                                            <option value="japanese">{{ __('messages.japanese') }}</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('language')" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- セクション2: ディベート設定 -->
                        <div class="bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h2 class="text-base sm:text-lg font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
                                <span class="material-icons-outlined text-indigo-500 mr-2">settings</span>{{
                                __('messages.debate_settings') }}
                            </h2>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- サイドの選択 -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">{{
                                        __('messages.your_side') }} <span class="text-red-500">*</span></label>
                                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                        <label
                                            class="relative flex bg-green-50 p-3 sm:p-4 rounded-lg border border-green-200 cursor-pointer hover:bg-green-100 transition">
                                            <input type="radio" name="side" value="affirmative"
                                                class="form-radio absolute opacity-0">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-green-500 flex items-center justify-center mr-2 sm:mr-3">
                                                    <div
                                                        class="side-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-green-500 opacity-0">
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="block text-xs sm:text-sm font-medium text-green-800">{{
                                                        __('messages.affirmative_side') }}</span>
                                                    <span class="text-xs text-green-600">{{
                                                        __('messages.agree_with_topic') }}</span>
                                                </div>
                                            </div>
                                        </label>

                                        <label
                                            class="relative flex bg-red-50 p-3 sm:p-4 rounded-lg border border-red-200 cursor-pointer hover:bg-red-100 transition">
                                            <input type="radio" name="side" value="negative"
                                                class="form-radio absolute opacity-0">
                                            <div class="flex items-center">
                                                <div
                                                    class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-red-500 flex items-center justify-center mr-2 sm:mr-3">
                                                    <div
                                                        class="side-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-red-500 opacity-0">
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="block text-xs sm:text-sm font-medium text-red-800">{{
                                                        __('messages.negative_side') }}</span>
                                                    <span class="text-xs text-red-600">{{
                                                        __('messages.disagree_with_topic') }}</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <x-input-error :messages="$errors->get('side')" class="mt-2" />
                                </div>

                                <!-- 証拠資料の使用有無 -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
                                        {{ __('messages.evidence_usage') }}
                                    </label>
                                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                                        <label
                                            class="relative flex bg-blue-50 p-3 sm:p-4 rounded-lg border border-blue-200 cursor-not-allowed opacity-60">
                                            <input type="radio" name="evidence_allowed" value="1"
                                                class="form-radio absolute opacity-0" disabled>
                                            <div class="flex items-center">
                                                <div
                                                    class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-blue-500 flex items-center justify-center mr-2 sm:mr-3">
                                                    <div
                                                        class="evidence-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-500 opacity-0">
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="block text-xs sm:text-sm font-medium text-blue-800">
                                                        {{ __('messages.evidence_allowed') }}
                                                    </span>
                                                    <span class="text-xs text-blue-600">
                                                        {{ __('messages.can_use_evidence') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </label>

                                        <label
                                            class="relative flex bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200 cursor-not-allowed opacity-60">
                                            <input type="radio" name="evidence_allowed" value="0"
                                                class="form-radio absolute opacity-0" checked disabled>
                                            <div class="flex items-center">
                                                <div
                                                    class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-gray-500 flex items-center justify-center mr-2 sm:mr-3">
                                                    <div
                                                        class="evidence-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-gray-500 opacity-0">
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="block text-xs sm:text-sm font-medium text-gray-800">
                                                        {{ __('messages.evidence_not_allowed') }}
                                                    </span>
                                                    {{-- <span class="text-xs text-gray-600">
                                                        {{ __('messages.cannot_use_evidence') }}
                                                    </span> --}}
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 flex items-center">
                                        <span class="material-icons-outlined text-base mr-1">info</span>
                                        {{ __('messages.ai_evidence_not_supported') }}
                                    </p>
                                    <x-input-error :messages="$errors->get('evidence_allowed')" class="mt-2" />
                                </div>

                                <!-- フォーマット選択 -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">{{
                                        __('messages.format') }} <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span
                                                class="material-icons-outlined text-gray-400 text-xs sm:text-sm">format_list_numbered</span>
                                        </div>
                                        <select name="format_type" id="format_type"
                                            class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md"
                                            onchange="toggleCustomFormat(this.value === 'custom'); updateFormatPreview(this.value);">
                                            @foreach ($translatedFormats as $translatedName => $turns)
                                            <option value="{{ array_search($turns, $translatedFormats, true) }}">{{ $turns['name'] }}</option>
                                            @endforeach
                                            <option value="custom">{{ __('messages.custom_format') }}</option>
                                        </select>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">{{ __('messages.format_selection_guide') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- フォーマットプレビュー -->
                        <div id="format-preview"
                            class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-200">
                            <button type="button" class="w-full text-left focus:outline-none group transition-all"
                                onclick="toggleFormatPreview()">
                                <h3
                                    class="text-sm sm:text-md font-semibold text-gray-700 flex items-center justify-between">
                                    <span class="flex items-center">
                                        <span class="material-icons-outlined text-indigo-500 mr-2">preview</span>
                                        <span id="format-preview-title">{{ __('messages.format_preview') }}</span>
                                    </span>
                                    <span
                                        class="material-icons-outlined text-gray-400 group-hover:text-indigo-500 transition-colors format-preview-icon">expand_more</span>
                                </h3>
                            </button>

                            <div id="format-preview-content"
                                class="hidden mt-3 sm:mt-4 transition-all duration-300 transform">
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border border-gray-100 rounded-lg">
                                            <tbody id="format-preview-body" class="bg-white divide-y divide-gray-200">
                                                <!-- JavaScriptで動的に生成 -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- カスタムフォーマット設定 -->
                        <div id="custom-format-settings"
                            class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-200 hidden">
                            <h3 class="text-sm sm:text-md font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
                                <span class="material-icons-outlined text-indigo-500 mr-2">edit</span>
                                {{ __('messages.configure_custom_format') }}
                            </h3>
                            <div class="mb-3 sm:mb-4 p-3 sm:p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <span
                                            class="material-icons-outlined text-yellow-600 text-sm sm:text-base">info</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-xs sm:text-sm text-yellow-700">
                                            {{ __('messages.custom_format_guide') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div id="turns-container" class="space-y-3 sm:space-y-4">
                                <!-- ターン設定テンプレート -->
                                <div
                                    class="turn-card border rounded-lg p-3 sm:p-4 bg-white shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-center mb-2 sm:mb-3">
                                        <div class="flex items-center">
                                            <span
                                                class="turn-number w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-medium text-xs sm:text-sm">1</span>
                                            <h4 class="turn-title text-xs sm:text-sm font-medium ml-2 text-gray-700">{{
                                                __('messages.part') }} 1</h4>
                                        </div>
                                        <button type="button"
                                            class="delete-turn text-gray-400 hover:text-red-500 transition-colors">
                                            <span class="material-icons-outlined text-sm sm:text-base">delete</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs text-gray-500">{{ __('messages.side') }}</label>
                                            <select name="turns[0][speaker]"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                                <option value="affirmative">{{ __('messages.affirmative_side') }}
                                                </option>
                                                <option value="negative">{{ __('messages.negative_side') }}</option>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-5">
                                            <label class="block text-xs text-gray-500">{{ __('messages.part_name')
                                                }}</label>
                                            <input type="text" name="turns[0][name]"
                                                placeholder="{{ __('messages.placeholder_part_name') }}"
                                                list="part-suggestions"
                                                class="part-name mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                            <datalist id="part-suggestions">
                                                <option value="{{ __('messages.suggestion_constructive') }}">
                                                <option value="{{ __('messages.suggestion_first_constructive') }}">
                                                <option value="{{ __('messages.suggestion_second_constructive') }}">
                                                <option value="{{ __('messages.suggestion_rebuttal') }}">
                                                <option value="{{ __('messages.suggestion_first_rebuttal') }}">
                                                <option value="{{ __('messages.suggestion_second_rebuttal') }}">
                                                <option value="{{ __('messages.suggestion_questioning') }}">
                                                <option value="{{ __('messages.suggestion_prep_time') }}">
                                            </datalist>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-xs text-gray-500">{{
                                                __('messages.duration_minutes') }}</label>
                                            <input type="number" name="turns[0][duration]" value="5" min="1" max="14"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                                        </div>
                                        <div class="sm:col-span-2 flex flex-col justify-end">
                                            <div class="flex items-center space-x-2">
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="turns[0][is_questions]" value="1"
                                                        class="question-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                                                    <span class="ml-1 text-xs text-gray-500">{{
                                                        __('messages.question_time') }}</span>
                                                </label>
                                                <label class="inline-flex items-center">
                                                    <input type="checkbox" name="turns[0][is_prep_time]" value="1"
                                                        class="prep-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                                                    <span class="ml-1 text-xs text-gray-500">{{ __('messages.prep_time')
                                                        }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 sm:mt-6">
                                <button type="button" id="add-turn"
                                    class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-colors">
                                    <span class="material-icons-outlined text-xs sm:text-sm mr-1">add</span>
                                    {{ __('messages.add_part') }}
                                </button>
                            </div>
                        </div>

                        <!-- 送信ボタンエリア -->
                        <div class="flex justify-between items-center pt-4 sm:pt-6 border-t">
                            <a href="{{ route('welcome') }}"
                                class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 border border-gray-300 rounded-md shadow-sm text-xs sm:text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <span
                                    class="material-icons-outlined text-gray-500 mr-1 text-xs sm:text-sm">arrow_back</span>
                                {{ __('messages.cancel') }}
                            </a>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 sm:px-6 sm:py-3 border border-transparent text-xs sm:text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <span class="material-icons-outlined mr-1 text-sm">play_circle</span>
                                {{ __('messages.start_debate') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // フォーマットデータ
        const formats = @json($translatedFormats);
        // 翻訳済みテキストをJavaScriptに渡す
        const translations = {
            affirmative: "{{ __('messages.affirmative_side') }}",
            negative: "{{ __('messages.negative_side') }}",
            formatInfoMissing: "{{ __('messages.format_info_missing') }}",
            minuteSuffix: "{{ __('messages.minute_suffix') }}",
            part: "{{ __('messages.part') }}",
            side: "{{ __('messages.side') }}",
            partName: "{{ __('messages.part_name') }}",
            durationMinutes: "{{ __('messages.duration_minutes') }}",
            questionTime: "{{ __('messages.question_time') }}",
            prepTime: "{{ __('messages.prep_time') }}",
            placeholderPartName: "{{ __('messages.placeholder_part_name') }}",
            prepTimeSuggestion: "{{ __('messages.suggestion_prep_time') }}",
            questionTimeSuggestion: "{{ __('messages.suggestion_questioning') }}",
            suggestionConstructive: "{{ __('messages.suggestion_constructive') }}",
            suggestionFirstConstructive: "{{ __('messages.suggestion_first_constructive') }}",
            suggestionSecondConstructive: "{{ __('messages.suggestion_second_constructive') }}",
            suggestionRebuttal: "{{ __('messages.suggestion_rebuttal') }}",
            suggestionFirstRebuttal: "{{ __('messages.suggestion_first_rebuttal') }}",
            suggestionSecondRebuttal: "{{ __('messages.suggestion_second_rebuttal') }}",
            suggestionPrepTime: "{{ __('messages.suggestion_prep_time') }}",
        };

        // サイド選択のラジオボタン動作
        document.addEventListener('DOMContentLoaded', function() {
            const sideRadios = document.querySelectorAll('input[name="side"]');
            // const evidenceRadios = document.querySelectorAll('input[name="evidence_allowed"]');

            sideRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.side-indicator').forEach(indicator => {
                        indicator.style.opacity = '0';
                    });
                    this.closest('label').querySelector('.side-indicator').style.opacity = '1';
                });
            });

            // 証拠資料ラジオボタンのイベントハンドラ
            // evidenceRadios.forEach(radio => {
            //     radio.addEventListener('change', function() {
            //         document.querySelectorAll('.evidence-indicator').forEach(indicator => {
            //             indicator.style.opacity = '0';
            //         });
            //         this.closest('label').querySelector('.evidence-indicator').style.opacity = '1';
            //     });
            // });

            // 初期状態で選択されているラジオボタンのインジケーターを表示
            const checkedSideRadio = document.querySelector('input[name="side"]:checked');
            if (checkedSideRadio) {
                checkedSideRadio.closest('label').querySelector('.side-indicator').style.opacity = '1';
            }

            // 初期状態で選択されている証拠資料ラジオボタンのインジケーターを表示
            const checkedEvidenceRadio = document.querySelector('input[name="evidence_allowed"]:checked');
            if (checkedEvidenceRadio) {
                checkedEvidenceRadio.closest('label').querySelector('.evidence-indicator').style.opacity = '1';
            }
        });

        // カスタムフォーマット表示切替
        function toggleCustomFormat(show) {
            const customSettings = document.getElementById('custom-format-settings');
            customSettings.classList.toggle('hidden', !show);
            const formatPreview = document.getElementById('format-preview');
            formatPreview.classList.toggle('hidden', show);
        }

        // フォーマットプレビュー更新関数
        function updateFormatPreview(formatKey) {
            if (formatKey === 'custom') return;

            const previewBody = document.getElementById('format-preview-body');
            previewBody.innerHTML = '';
            const previewTitle = document.getElementById('format-preview-title');

            if (!formats[formatKey] || !formats[formatKey].turns) {
                previewTitle.textContent = translations.formatInfoMissing;
                previewBody.innerHTML =
                    `<tr><td colspan="4" class="px-3 py-2 text-sm text-gray-500">${translations.formatInfoMissing}</td></tr>`;
                return;
            }

            previewTitle.textContent = formats[formatKey].name;

            Object.entries(formats[formatKey].turns).forEach(([index, turn]) => {
                const row = document.createElement('tr');
                const displayIndex = index;
                let speakerText = '';
                let bgClass = '';
                let textClass = '';
                let badgeClass = '';

                if (turn.speaker === 'affirmative') {
                    speakerText = translations.affirmative;
                    bgClass = 'bg-green-50';
                    textClass = 'text-green-800';
                    badgeClass = 'bg-green-100';
                } else if (turn.speaker === 'negative') {
                    speakerText = translations.negative;
                    bgClass = 'bg-red-50';
                    textClass = 'text-red-800';
                    badgeClass = 'bg-red-100';
                } else {
                    speakerText = turn.speaker;
                    bgClass = 'bg-gray-50';
                    textClass = 'text-gray-800';
                    badgeClass = 'bg-gray-100';
                }

                let typeIcon = '';

                if (turn.is_prep_time) {
                    typeIcon =
                        '<span class="material-icons-outlined text-xs mr-1 text-gray-500">timer</span>';
                } else if (turn.is_questions) {
                    typeIcon =
                        '<span class="material-icons-outlined text-xs mr-1 text-gray-500">help</span>';
                }

                row.className = bgClass;
                row.innerHTML = `<td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700">${displayIndex}</td>
                <td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm">
                    <span class="px-2 py-0.5 inline-flex items-center rounded-full ${badgeClass} ${textClass} text-xs font-medium">
                        ${speakerText}
                    </span>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700 flex items-center">
                    ${typeIcon}${turn.name}
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-xs sm:text-sm text-gray-700">
                    ${turn.duration / 60}${translations.minuteSuffix}
                </td>`;
                previewBody.appendChild(row);
            });
        }

        // フォーマットプレビューの開閉
        function toggleFormatPreview() {
            const content = document.getElementById('format-preview-content');
            const icon = document.querySelector('.format-preview-icon');

            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                setTimeout(() => {
                    content.classList.add('opacity-100');
                }, 10);
                icon.textContent = 'expand_less';
            } else {
                content.classList.remove('opacity-100');
                content.classList.add('opacity-0');
                setTimeout(() => {
                    content.classList.add('hidden');
                    icon.textContent = 'expand_more';
                }, 200);
            }
        }

        // ページ読み込み時に実行
        document.addEventListener('DOMContentLoaded', function() {
            // サイド選択の初期状態設定
            const checkedRadio = document.querySelector('input[name="side"]:checked');
            if (checkedRadio) {
                checkedRadio.closest('label').querySelector('.side-indicator').style.opacity = '1';
            }

            const formatSelect = document.getElementById('format_type');
            toggleCustomFormat(formatSelect.value === 'custom');
            updateFormatPreview(formatSelect.value);

            formatSelect.addEventListener('change', function() {
                toggleCustomFormat(this.value === 'custom');
                updateFormatPreview(this.value);
            });

            // ターン追加ボタン
            const addTurnButton = document.getElementById('add-turn');
            const turnsContainer = document.getElementById('turns-container');
            let turnCount = turnsContainer.children.length > 0 ? turnsContainer.children.length : 1;

            // ターン追加処理
            addTurnButton.addEventListener('click', function() {
                const newTurn = document.createElement('div');
                newTurn.className =
                    'turn-card border rounded-lg p-3 sm:p-4 bg-white shadow-sm hover:shadow-md transition-shadow';
                newTurn.innerHTML = `<div class="flex justify-between items-center mb-2 sm:mb-3">
                    <div class="flex items-center">
                        <span class="turn-number w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-medium text-xs sm:text-sm">${turnCount + 1}</span>
                        <h4 class="turn-title text-xs sm:text-sm font-medium ml-2 text-gray-700">${translations.part} ${turnCount + 1}</h4>
                    </div>
                    <button type="button" class="delete-turn text-gray-400 hover:text-red-500 transition-colors">
                        <span class="material-icons-outlined text-sm sm:text-base">delete</span>
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                    <div class="sm:col-span-3">
                        <label class="block text-xs text-gray-500">${translations.side}</label>
                        <select name="turns[${turnCount}][speaker]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                            <option value="affirmative">${translations.affirmative}</option>
                            <option value="negative">${translations.negative}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-5">
                        <label class="block text-xs text-gray-500">${translations.partName}</label>
                        <input type="text" name="turns[${turnCount}][name]" placeholder="${translations.placeholderPartName}" list="part-suggestions" class="part-name mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs text-gray-500">${translations.durationMinutes}</label>
                        <input type="number" name="turns[${turnCount}][duration]" value="3" min="1" max="14" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm">
                    </div>
                    <div class="sm:col-span-2 flex flex-col justify-end">
                        <div class="flex items-center space-x-2">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="turns[${turnCount}][is_questions]" value="1"
                                    class="question-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                                <span class="ml-1 text-xs text-gray-500">${translations.questionTime}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="turns[${turnCount}][is_prep_time]" value="1"
                                    class="prep-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4">
                                <span class="ml-1 text-xs text-gray-500">${translations.prepTime}</span>
                            </label>
                        </div>
                    </div>
                </div>`;
                turnsContainer.appendChild(newTurn);
                turnCount++;

                attachDeleteListeners();
                attachInputListenersToElement(newTurn);
            });

            // 削除ボタンのイベントリスナー設定
            function attachDeleteListeners() {
                document.querySelectorAll('.delete-turn').forEach(button => {
                    button.replaceWith(button.cloneNode(true));
                });
                document.querySelectorAll('.delete-turn').forEach(button => {
                    button.addEventListener('click', handleDeleteTurn);
                });
            }

            function handleDeleteTurn() {
                if (turnsContainer.children.length > 1) {
                    this.closest('.turn-card').remove();
                    updateTurnNumbersAndNames();
                } else {
                    const turnCard = this.closest('.turn-card');
                    turnCard.classList.add('border-red-500', 'animate-pulse');
                    setTimeout(() => {
                        turnCard.classList.remove('border-red-500', 'animate-pulse');
                    }, 1000);
                }
            }

            // ターン番号と Name 属性の更新
            function updateTurnNumbersAndNames() {
                const turns = turnsContainer.querySelectorAll('.turn-card');
                turns.forEach((turn, index) => {
                    const displayTurnNumber = index + 1;
                    const numberDisplay = turn.querySelector('.turn-number');
                    if(numberDisplay) numberDisplay.textContent = `${displayTurnNumber}`;
                    const titleDisplay = turn.querySelector('.turn-title');
                    if(titleDisplay) titleDisplay.textContent = `${translations.part} ${displayTurnNumber}`;

                    turn.querySelectorAll('input, select').forEach(input => {
                        const name = input.getAttribute('name');
                        if (name) {
                            const newName = name.replace(/turns\[\d+\]/, `turns[${index}]`);
                            input.setAttribute('name', newName);
                        }
                    });
                });
                turnCount = turns.length;
            }

            // 特定の要素内の入力にリスナーをアタッチ
            function attachInputListenersToElement(element) {
                element.querySelectorAll('.part-name').forEach(input => {
                    input.removeEventListener('input', handlePartNameInput);
                    input.addEventListener('input', handlePartNameInput);
                });
                element.querySelectorAll('.prep-time-checkbox, .question-time-checkbox').forEach(checkbox => {
                    checkbox.removeEventListener('change', handleCheckboxChange);
                    checkbox.addEventListener('change', handleCheckboxChange);
                });
            }

            // Part Name Input Handler
            function handlePartNameInput() {
                const turnCard = this.closest('.turn-card');
                if (!turnCard) return;
                const partNameInput = this;
                const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
                const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

                if (!prepTimeCheckbox || !questionTimeCheckbox) return;

                if (partNameInput.value.trim() === translations.prepTimeSuggestion) {
                    prepTimeCheckbox.checked = true;
                    questionTimeCheckbox.checked = false;
                } else if (partNameInput.value.trim() === translations.questionTimeSuggestion) {
                    questionTimeCheckbox.checked = true;
                    prepTimeCheckbox.checked = false;
                } else {
                }
            }

            // Checkbox Change Handler
            function handleCheckboxChange() {
                const turnCard = this.closest('.turn-card');
                if (!turnCard) return;
                const partNameInput = turnCard.querySelector('.part-name');
                const prepTimeCheckbox = turnCard.querySelector('.prep-time-checkbox');
                const questionTimeCheckbox = turnCard.querySelector('.question-time-checkbox');

                if (!partNameInput || !prepTimeCheckbox || !questionTimeCheckbox) return;

                const isPrepTime = this === prepTimeCheckbox;
                const isQuestionTime = this === questionTimeCheckbox;

                if (isPrepTime && this.checked) {
                    partNameInput.value = translations.prepTimeSuggestion;
                    questionTimeCheckbox.checked = false;
                } else if (isQuestionTime && this.checked) {
                    partNameInput.value = translations.questionTimeSuggestion;
                    prepTimeCheckbox.checked = false;
                } else if (
                    (isPrepTime && !this.checked && partNameInput.value === translations.prepTimeSuggestion) ||
                    (isQuestionTime && !this.checked && partNameInput.value === translations.questionTimeSuggestion)
                ) {
                    partNameInput.value = '';
                }
            }

            // 初期化時に既存の要素にリスナーをアタッチ
            attachDeleteListeners();
            turnsContainer.querySelectorAll('.turn-card').forEach(card => {
                attachInputListenersToElement(card);
            });
        });
    </script>
</x-app-layout>
