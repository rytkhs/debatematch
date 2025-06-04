@props([
    'formType' => 'room', // 'room' or 'ai'
    'maxDuration' => null,
    'errors' => null
])

@php
    $maxDuration = $maxDuration ?? ($formType === 'ai' ? 14 : 60);
@endphp

<!-- カスタムフォーマット設定 -->
<div id="custom-format-settings" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-200 hidden">
    <h3 class="text-sm sm:text-md font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
        <span class="material-icons-outlined text-indigo-500 mr-2">edit</span>
        {{ __('messages.configure_custom_format') }}
    </h3>

    <!-- 改善されたカスタムフォーマット説明 -->
    <div class="mb-4 p-4 bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-400 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <span class="material-icons-outlined text-yellow-600 text-sm">lightbulb</span>
            </div>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-yellow-800 mb-2">{{ __('messages.custom_format_guide_title') }}</h4>
                <p class="text-xs text-yellow-700 mb-2">{{ __('messages.custom_format_guide') }}</p>
                <ul class="list-disc list-inside text-xs text-yellow-700 space-y-1">
                    <li>{{ __('messages.custom_format_tip1') }}</li>
                    <li>{{ __('messages.custom_format_tip2') }}</li>
                    <li>{{ __('messages.custom_format_tip3') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="turns-container" class="space-y-3 sm:space-y-4">
        <!-- ターン設定テンプレート -->
        <div class="turn-card border rounded-lg p-3 sm:p-4 bg-white shadow-sm hover:shadow-md transition-shadow">
            <div class="flex justify-between items-center mb-2 sm:mb-3">
                <div class="flex items-center">
                    <span class="turn-number w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-medium text-xs sm:text-sm">1</span>
                    <h4 class="turn-title text-xs sm:text-sm font-medium ml-2 text-gray-700">{{ __('messages.part') }} 1</h4>
                </div>
                <button type="button" class="delete-turn text-gray-400 hover:text-red-500 transition-colors">
                    <span class="material-icons-outlined text-sm sm:text-base">delete</span>
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4">
                <div class="sm:col-span-3">
                    <label class="block text-xs text-gray-500">{{ __('messages.side') }}</label>
                    <select name="turns[0][speaker]"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                        <option value="affirmative" {{ old('turns.0.speaker') == 'affirmative' ? 'selected' : '' }}>{{ __('messages.affirmative_side') }}</option>
                        <option value="negative" {{ old('turns.0.speaker') == 'negative' ? 'selected' : '' }}>{{ __('messages.negative_side') }}</option>
                    </select>
                </div>
                <div class="sm:col-span-5">
                    <label class="block text-xs text-gray-500">{{ __('messages.part_name') }}</label>
                    <input type="text" name="turns[0][name]" value="{{ old('turns.0.name') }}"
                        placeholder="{{ __('messages.placeholder_part_name') }}"
                        list="part-suggestions"
                        class="part-name mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
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
                    <label class="block text-xs text-gray-500">{{ __('messages.duration_minutes') }}</label>
                    <input type="number" name="turns[0][duration]" value="{{ old('turns.0.duration', 5) }}" min="1" max="{{ $maxDuration }}"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs sm:text-sm transition-colors duration-200">
                </div>
                <div class="sm:col-span-2 flex flex-col justify-end">
                    <div class="flex items-center space-x-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="turns[0][is_questions]" value="1"
                                class="question-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4" {{ old('turns.0.is_questions') ? 'checked' : '' }}>
                            <span class="ml-1 text-xs text-gray-500">{{ __('messages.question_time') }}</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="turns[0][is_prep_time]" value="1"
                                class="prep-time-checkbox rounded text-indigo-600 focus:ring-indigo-500 h-3 w-3 sm:h-4 sm:w-4" {{ old('turns.0.is_prep_time') ? 'checked' : '' }}>
                            <span class="ml-1 text-xs text-gray-500">{{ __('messages.prep_time') }}</span>
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
