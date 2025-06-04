<x-app-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-white min-h-screen">
        <x-slot name="header">
            <x-header></x-header>
        </x-slot>

        <div class="max-w-6xl mx-auto py-6 sm:py-8 px-4 sm:px-6 lg:px-8">
            <!-- ステップインジケーター -->
            <x-debate-form.step-indicator
                :steps="[__('messages.basic_information'), __('messages.debate_settings')]" />

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="px-3 py-4 sm:px-4 sm:py-5">
                    <div class="flex items-center mb-6 sm:mb-8 border-b pb-3 sm:pb-4">
                        <span class="material-icons-outlined text-indigo-600 text-xl sm:text-2xl mr-2 sm:mr-3">add_circle</span>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-700">{{ __('messages.create_new_room') }}</h1>
                        <!-- ガイドへのリンク -->
                        <a href="{{ route('guide') }}" class="ml-auto inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 transition-colors">
                            <span class="material-icons-outlined text-sm mr-1">help_outline</span>
                            {{ __('messages.view_guide') }}
                        </a>
                    </div>

                    <form action="{{ route('rooms.store') }}" method="POST" class="space-y-6 sm:space-y-8" id="room-create-form">
                        @csrf

                        <!-- ステップ1: 基本情報 -->
                        <x-debate-form.basic-info-step
                            formType="room"
                            :languageOrder="$languageOrder"
                            :showRoomName="true"
                            :errors="$errors" />

                        <!-- ステップ2: ディベート設定 -->
                        <x-debate-form.debate-settings-step
                            formType="room"
                            :translatedFormats="$translatedFormats"
                            submitButtonText="{{ __('messages.create_room') }}"
                            submitButtonIcon="check_circle"
                            :errors="$errors" />

                        <!-- フォーマットプレビュー -->
                        <x-debate-form.format-preview />

                        <!-- カスタムフォーマット設定 -->
                        <x-debate-form.custom-format-settings
                            formType="room"
                            :errors="$errors" />

                        <!-- フリーフォーマット設定 -->
                        <x-debate-form.free-format-settings
                            :errors="$errors" />

                        <!-- キャンセルボタン -->
                        <div class="flex justify-center pt-4 border-t">
                            <a href="{{ route('welcome') }}"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                <span class="material-icons-outlined text-gray-500 mr-1 text-sm">close</span>
                                {{ __('messages.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScriptの読み込み -->
    @vite('resources/js/debate-form.js')
    <script>
        // 設定とデータ
        const debateFormConfig = {
            formType: 'room',
            formSelector: '#room-create-form',
            formats: @json($translatedFormats),
            requiredFields: [
                { name: 'topic', message: "{{ __('messages.topic_required') }}" },
                { name: 'name', message: "{{ __('messages.room_name_required') }}" },
                { name: 'language', message: "{{ __('messages.language_required') }}" }
            ],
            translations: {
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
                topicRequired: "{{ __('messages.topic_required') }}",
                languageRequired: "{{ __('messages.language_required') }}",
                roomNameRequired: "{{ __('messages.room_name_required') }}",
                fieldRequired: "{{ __('messages.field_required') }}"
            }
        };

        // ディベートフォームマネージャーの初期化
        document.addEventListener('DOMContentLoaded', function() {
            const debateForm = new DebateFormManager(debateFormConfig);
            debateForm.init();

            // グローバル参照の設定（後方互換性のため）
            window.stepManager = debateForm.stepManager;
            window.formatManager = debateForm.formatManager;
        });
    </script>
</x-app-layout>
