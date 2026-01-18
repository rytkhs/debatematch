<x-app-layout>
    <div class="bg-gradient-to-b from-indigo-50 to-white min-h-screen">
        <x-slot name="header">
            <x-header></x-header>
        </x-slot>

        <div class="max-w-6xl mx-auto py-6 sm:py-8 px-4 sm:px-6 lg:px-8">
            <!-- ステップインジケーター -->
            <x-debate-form.step-indicator
                :steps="[__('rooms.basic_information'), __('debates_format.debate_settings')]" />

            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="px-3 py-4 sm:px-4 sm:py-5">
                    <div class="flex items-center mb-6 sm:mb-8 border-b pb-3 sm:pb-4">
                        <span class="material-icons-outlined text-indigo-600 text-xl sm:text-2xl mr-2 sm:mr-3">add_circle</span>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-700">{{ __('rooms.create_new_room') }}</h1>
                        <!-- ガイドへのリンク -->
                        <a href="{{ route('guide') }}" class="ml-auto inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 transition-colors">
                            <span class="material-icons-outlined text-sm mr-1">help_outline</span>
                            {{ __('common.view_guide') }}
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
                            submitButtonText="{{ __('navigation.create_room') }}"
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
                                {{ __('common.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 設定データをJavaScriptページエントリーポイントに渡す
        window.roomCreateConfig = {
            formats: @json($translatedFormats),
            requiredFields: [
                { name: 'topic', message: "{{ __('forms.topic_required') }}" },
                { name: 'language', message: "{{ __('forms.language_required') }}" }
            ],
            translations: {
                affirmative: "{{ __('rooms.affirmative_side') }}",
                negative: "{{ __('rooms.negative_side') }}",
                formatInfoMissing: "{{ __('debates_format.format_info_missing') }}",
                minuteSuffix: "{{ __('debates_format.minute_suffix') }}",
                part: "{{ __('debates_format.part') }}",
                side: "{{ __('rooms.side') }}",
                partName: "{{ __('debates_format.part_name') }}",
                durationMinutes: "{{ __('debates_format.duration_minutes') }}",
                questionTime: "{{ __('debates_format.question_time') }}",
                prepTime: "{{ __('debates_format.prep_time') }}",
                placeholderPartName: "{{ __('debates_format.placeholder_part_name') }}",
                prepTimeSuggestion: "{{ __('debates_format.suggestion_prep_time') }}",
                questionTimeSuggestion: "{{ __('debates_format.suggestion_questioning') }}",
                suggestionConstructive: "{{ __('debates_format.suggestion_constructive') }}",
                suggestionFirstConstructive: "{{ __('debates_format.suggestion_first_constructive') }}",
                suggestionSecondConstructive: "{{ __('debates_format.suggestion_second_constructive') }}",
                suggestionRebuttal: "{{ __('debates_format.suggestion_rebuttal') }}",
                suggestionFirstRebuttal: "{{ __('debates_format.suggestion_first_rebuttal') }}",
                suggestionSecondRebuttal: "{{ __('debates_format.suggestion_second_rebuttal') }}",
                suggestionPrepTime: "{{ __('debates_format.suggestion_prep_time') }}",
                topicRequired: "{{ __('forms.topic_required') }}",
                languageRequired: "{{ __('forms.language_required') }}",
                roomNameRequired: "{{ __('forms.room_name_required') }}",
                fieldRequired: "{{ __('common.field_required') }}"
            }
        };
    </script>
    @vite(['resources/js/pages/room-create.js'])
</x-app-layout>
