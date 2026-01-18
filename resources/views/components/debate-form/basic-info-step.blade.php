@props([
    'formType' => 'room', // 'room' or 'ai'
    'languageOrder' => [],
    'showRoomName' => true,
    'errors' => null
])

<!-- ステップ1: 基本情報 -->
<div id="step1-content" class="step-content bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
    <h2 class="text-base sm:text-lg font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
        <span class="material-icons-outlined text-indigo-500 mr-2">info</span>
        {{ __('rooms.basic_information') }}
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <!-- 論題 -->
        <div class="md:col-span-2 required-field" x-data="{ topicInput: @js(old('topic', '')) }">
            <label for="topic" class="text-xs sm:text-sm font-medium text-gray-700 mb-1 flex items-center">
                {{ __('rooms.topic') }}
                <span class="text-red-500 ml-1 text-base">*</span>
                <span class="ml-2 text-xs text-gray-500 bg-red-50 px-2 py-0.5 rounded-full">{{ __('common.required') }}</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">subject</span>
                </div>
                <input type="text" id="topic" name="topic" x-model="topicInput" value="{{ old('topic') }}"
                    placeholder="{{ __('rooms.placeholder_topic') }}" required
                    class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md transition-colors duration-200">
                @if($errors)
                    <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                @endif
            </div>
            <div class="mt-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <p class="text-xs text-gray-500 text-left sm:text-right">{{ __('rooms.topic_guideline') }}</p>
                <div class="flex gap-2">
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-topic-catalog', { detail: { targetId: 'topic' } }))"
                            class="inline-flex items-center px-3.5 py-1.5 text-xs sm:text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-full transition-colors border border-indigo-200 shadow-sm group">
                        <span class="material-icons-outlined text-sm mr-1.5 text-indigo-600 group-hover:text-indigo-800 transition-colors">tips_and_updates</span>
                        <span class="sm:hidden">{{ __('topic_catalog.ai.btn_suggestion_short') }}</span>
                        <span class="hidden sm:inline">{{ __('topic_catalog.select_btn') ?? '論題を提案' }}</span>
                    </button>
                    <button type="button"
                            :disabled="!topicInput || !topicInput.trim()"
                            onclick="window.dispatchEvent(new CustomEvent('open-topic-insight', { detail: { targetId: 'topic' } }))"
                            class="inline-flex items-center px-3.5 py-1.5 text-xs sm:text-sm font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-full transition-colors border border-purple-200 shadow-sm group disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="material-icons-outlined text-sm mr-1.5 text-purple-600 group-hover:text-purple-800 transition-colors">analytics</span>
                        <span class="sm:hidden">{{ __('topic_catalog.ai.btn_insight_short') }}</span>
                        <span class="hidden sm:inline">{{ __('topic_catalog.ai.btn_insight') ?? 'AI分析' }}</span>
                    </button>
                </div>
            </div>
        </div>

        @if($showRoomName)
            <!-- ルーム名 -->
            <div class="required-field">
                <label for="name" class="text-xs sm:text-sm font-medium text-gray-700 mb-1 flex items-center">
                    {{ __('rooms.room_name') }}
                    <span class="text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full ml-2">({{ __('common.optional') }})</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">meeting_room</span>
                    </div>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                        placeholder="{{ __('rooms.placeholder_room_name') }}"
                        class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md transition-colors duration-200">
                    @if($errors)
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    @endif
                </div>
            </div>
        @endif

        <!-- 言語設定 -->
        <div class="required-field {{ $showRoomName ? '' : 'md:col-span-1' }}">
            <label for="language" class="text-xs sm:text-sm font-medium text-gray-700 mb-1 flex items-center">
                {{ __('rooms.language') }}
                <span class="text-red-500 ml-1 text-base">*</span>
                <span class="ml-2 text-xs text-gray-500 bg-red-50 px-2 py-0.5 rounded-full">{{ __('common.required') }}</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">language</span>
                </div>
                <select id="language" name="language"
                    class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md transition-colors duration-200">
                    @foreach ($languageOrder as $lang)
                        <option value="{{ $lang }}" {{ old('language') == $lang ? 'selected' : '' }}>
                            {{ __('debates_format.' . $lang) }}
                        </option>
                    @endforeach
                </select>
                @if($errors)
                    <x-input-error :messages="$errors->get('language')" class="mt-2" />
                @endif
            </div>
        </div>

        @if($showRoomName)
            <!-- 備考 -->
            <div class="md:col-span-2">
                <label for="remarks" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
                    {{ __('rooms.remarks') }}
                    <span class="text-gray-500 text-xs bg-gray-100 px-2 py-0.5 rounded-full ml-2">({{ __('common.optional') }})</span>
                </label>
                <div class="relative">
                    <div class="absolute top-2 sm:top-3 left-3 flex items-start pointer-events-none">
                        <span class="material-icons-outlined text-gray-400 text-xs sm:text-sm">description</span>
                    </div>
                    <textarea id="remarks" name="remarks" rows="3"
                        placeholder="{{ __('rooms.placeholder_remarks') }}"
                        class="pl-10 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md transition-colors duration-200">{{ old('remarks') }}</textarea>
                    @if($errors)
                        <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                    @endif
                </div>
            </div>
        @endif
    </div>

<x-debate-form.topic-suggestion-modal />
<x-debate-form.topic-insight-modal />

    <!-- ステップ1のナビゲーション -->
    <div class="flex justify-end pt-4 border-t mt-6">
        <button type="button" id="next-to-step2"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            {{ __('forms.next_step') }}
            <span class="material-icons-outlined ml-1 text-sm">arrow_forward</span>
        </button>
    </div>
</div>
