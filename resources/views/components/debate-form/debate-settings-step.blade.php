@props([
    'formType' => 'room', // 'room' or 'ai'
    'translatedFormats' => [],
    'submitButtonText' => null,
    'submitButtonIcon' => 'check_circle',
    'errors' => null
])

<!-- ステップ2: ディベート設定 -->
<div id="step2-content" class="step-content bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200 hidden">
    <h2 class="text-base sm:text-lg font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
        <span class="material-icons-outlined text-indigo-500 mr-2">settings</span>
        {{ __('debates_format.debate_settings') }}
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <!-- サイドの選択 -->
        <div class="required-field">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
                {{ __('rooms.your_side') }}
                <span class="text-red-500 ml-1 text-base">*</span>
                <span class="ml-2 text-xs text-gray-500 bg-red-50 px-2 py-0.5 rounded-full">{{ __('common.required') }}</span>
            </label>
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <label class="relative flex bg-green-50 p-3 sm:p-4 rounded-lg border border-green-200 cursor-pointer hover:bg-green-100 transition-all duration-200 hover:shadow-md">
                    <input type="radio" name="side" value="affirmative"
                        class="form-radio absolute opacity-0" {{ old('side') == 'affirmative' ? 'checked' : '' }}>
                    <div class="flex items-center">
                        <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-green-500 flex items-center justify-center mr-2 sm:mr-3">
                            <div class="side-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-green-500 opacity-0 transition-opacity duration-200"></div>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-medium text-green-800">{{ __('rooms.affirmative_side') }}</span>
                            <span class="text-xs text-green-600">{{ __('rooms.agree_with_topic') }}</span>
                        </div>
                    </div>
                </label>

                <label class="relative flex bg-red-50 p-3 sm:p-4 rounded-lg border border-red-200 cursor-pointer hover:bg-red-100 transition-all duration-200 hover:shadow-md">
                    <input type="radio" name="side" value="negative"
                        class="form-radio absolute opacity-0" {{ old('side') == 'negative' ? 'checked' : '' }}>
                    <div class="flex items-center">
                        <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-red-500 flex items-center justify-center mr-2 sm:mr-3">
                            <div class="side-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-red-500 opacity-0 transition-opacity duration-200"></div>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-medium text-red-800">{{ __('rooms.negative_side') }}</span>
                            <span class="text-xs text-red-600">{{ __('rooms.disagree_with_topic') }}</span>
                        </div>
                    </div>
                </label>
            </div>
            @if($errors)
                <x-input-error :messages="$errors->get('side')" class="mt-2" />
            @endif
        </div>

        <!-- 証拠資料の使用有無 -->
        <div class="{{ $formType === 'ai' ? '' : 'required-field' }}">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 {{ $formType === 'ai' ? '' : 'flex items-center' }}">
                {{ __('rooms.evidence_usage') }}
                @if($formType !== 'ai')
                    <span class="text-red-500 ml-1 text-base">*</span>
                    <span class="ml-2 text-xs text-gray-500 bg-red-50 px-2 py-0.5 rounded-full">{{ __('common.required') }}</span>
                @endif
            </label>
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                <label class="relative flex bg-blue-50 p-3 sm:p-4 rounded-lg border border-blue-200 {{ $formType === 'ai' ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:bg-blue-100' }} transition-all duration-200 {{ $formType !== 'ai' ? 'hover:shadow-md' : '' }}">
                    <input type="radio" name="evidence_allowed" value="1"
                        class="form-radio absolute opacity-0" {{ old('evidence_allowed') == '1' ? 'checked' : '' }} {{ $formType === 'ai' ? 'disabled' : '' }}>
                    <div class="flex items-center">
                        <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-blue-500 flex items-center justify-center mr-2 sm:mr-3">
                            <div class="evidence-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-blue-500 opacity-0 transition-opacity duration-200"></div>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-medium text-blue-800">
                                {{ __('rooms.evidence_allowed') }}
                            </span>
                            <span class="text-xs text-blue-600">
                                {{ __('rooms.can_use_evidence') }}
                            </span>
                        </div>
                    </div>
                </label>

                <label class="relative flex bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200 {{ $formType === 'ai' ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:bg-gray-100' }} transition-all duration-200 {{ $formType !== 'ai' ? 'hover:shadow-md' : '' }}">
                    <input type="radio" name="evidence_allowed" value="0"
                        class="form-radio absolute opacity-0" {{ old('evidence_allowed') == '0' || $formType === 'ai' ? 'checked' : '' }} {{ $formType === 'ai' ? 'disabled' : '' }}>
                    <div class="flex items-center">
                        <div class="w-4 h-4 sm:w-5 sm:h-5 rounded-full border-2 border-gray-500 flex items-center justify-center mr-2 sm:mr-3">
                            <div class="evidence-indicator w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-gray-500 {{ $formType === 'ai' ? 'opacity-100' : 'opacity-0' }} transition-opacity duration-200"></div>
                        </div>
                        <div>
                            <span class="block text-xs sm:text-sm font-medium text-gray-800">
                                {{ __('rooms.evidence_not_allowed') }}
                            </span>
                        </div>
                    </div>
                </label>
            </div>
            @if($formType === 'ai')
                <p class="mt-2 text-xs text-gray-500 flex items-center">
                    <span class="material-icons-outlined text-base mr-1">info</span>
                    {{ __('ai_debate.ai_evidence_not_supported') }}
                </p>
            @endif
            @if($errors)
                <x-input-error :messages="$errors->get('evidence_allowed')" class="mt-2" />
            @endif
        </div>

        <!-- フォーマット選択 -->
        <div class="md:col-span-2 required-field">
            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
                {{ __('debates_format.select_format') }}
                <span class="text-red-500 ml-1 text-base">*</span>
                <span class="ml-2 text-xs text-gray-500 bg-red-50 px-2 py-0.5 rounded-full">{{ __('common.required') }}</span>
            </label>

            <!-- フォーマット説明（折りたたみ式） -->
            <div class="mb-4">
                <button type="button" class="flex items-center text-xs text-blue-600 hover:text-blue-800 transition-colors" onclick="toggleFormatHelp()">
                    <span class="material-icons-outlined text-xs mr-1">help_outline</span>
                    <span>{{ __('debates_format.format_selection_help') }}</span>
                    <span class="material-icons-outlined text-xs ml-1 format-help-icon">expand_more</span>
                </button>
                <div id="format-help-content" class="hidden mt-2 p-3 bg-blue-50 border-l-4 border-blue-400 rounded-md">
                    <ul class="list-disc list-inside text-xs text-blue-700 space-y-1">
                        <li><strong>{{ __('debates_format.competition_format') }}：</strong>{{ __('debates_format.competition_format_help') }}</li>
                        <li><strong>{{ __('debates_format.custom_format') }}：</strong>{{ __('debates_format.custom_format_help') }}</li>
                        <li><strong>{{ __('debates.format_name_free') }}：</strong>{{ __('debates_format.free_format_help') }}</li>
                    </ul>
                    <p class="mt-2 text-xs text-blue-600">
                        <a href="{{ route('guide') }}#debate-formats" class="underline hover:text-blue-800 transition-colors">
                            {{ __('forms.view_detailed_guide') }}
                        </a>
                    </p>
                </div>
            </div>

            <!-- フォーマット選択グループ -->
            <div class="space-y-3">
                <!-- 大会フォーマット選択 -->
                <label class="relative flex items-center bg-blue-50 p-3 sm:p-4 rounded-lg border border-blue-200 cursor-pointer hover:bg-blue-100 transition-all duration-200 group hover:shadow-md">
                    <input type="radio" name="format_selection_type" value="standard"
                        class="form-radio text-blue-600 focus:ring-blue-500 h-4 w-4 sm:h-5 sm:w-5"
                        onchange="handleFormatSelectionChange('standard')" {{ old('format_type') != 'free' && old('format_type') != 'custom' && old('format_type') != '' ? 'checked' : '' }}>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="material-icons-outlined text-blue-600 text-xs sm:text-sm mr-2">emoji_events</span>
                                <span class="text-xs sm:text-sm font-medium text-blue-800">{{ __('debates_format.competition_format') }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-blue-700 mt-1">{{ __('debates_format.competition_format_description') }}</p>
                        <div class="mt-2" id="standard-format-select">
                            <select name="format_type" id="format_type"
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md transition-colors duration-200"
                                onchange="updateFormatPreview(this.value);" {{ old('format_type') == 'free' || old('format_type') == 'custom' ? 'disabled' : '' }}>
                                <option value="" {{ old('format_type') == '' ? 'selected' : '' }}>{{ __('debates_format.please_select') }}</option>
                                @foreach ($translatedFormats as $translatedName => $turns)
                                    <option value="{{ array_search($turns, $translatedFormats, true) }}" {{ old('format_type') == array_search($turns, $translatedFormats, true) ? 'selected' : '' }}>{{ $turns['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </label>

                <!-- カスタムフォーマット選択 -->
                <label class="relative flex items-center bg-amber-50 p-3 sm:p-4 rounded-lg border border-amber-200 cursor-pointer hover:bg-amber-100 transition-all duration-200 group hover:shadow-md">
                    <input type="radio" name="format_selection_type" value="custom"
                        class="form-radio text-amber-600 focus:ring-amber-500 h-4 w-4 sm:h-5 sm:w-5"
                        onchange="handleFormatSelectionChange('custom')" {{ old('format_type') == 'custom' ? 'checked' : '' }}>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="material-icons-outlined text-amber-600 text-xs sm:text-sm mr-2">edit</span>
                                <span class="text-xs sm:text-sm font-medium text-amber-800">{{ __('debates_format.custom_format') }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-amber-700 mt-1">{{ __('debates_format.custom_format_description') }}</p>
                    </div>
                </label>

                <!-- フリーフォーマット選択 -->
                <label class="relative flex items-center bg-green-50 p-3 sm:p-4 rounded-lg border border-green-200 cursor-pointer hover:bg-green-100 transition-all duration-200 group hover:shadow-md">
                    <input type="radio" name="format_selection_type" value="free"
                        class="form-radio text-green-600 focus:ring-green-500 h-4 w-4 sm:h-5 sm:w-5"
                        onchange="handleFormatSelectionChange('free')" {{ old('format_type') == 'free' ? 'checked' : '' }}>
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="material-icons-outlined text-green-600 text-xs sm:text-sm mr-2">tune</span>
                                <span class="text-xs sm:text-sm font-medium text-green-800">
                                    {{ __('debates.format_name_free') }}
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-green-700 mt-1">{{ __('debates_format.free_format_short_description') }}</p>
                    </div>
                </label>
            </div>

            @if($errors)
                <x-input-error :messages="$errors->get('format_type')" class="mt-2" />
            @endif

            <!-- フリーフォーマット用の隠しフィールド -->
            <input type="hidden" id="free_format_hidden" name="format_type" value="{{ old('format_type') == 'free' ? 'free' : old('format_type') }}">
        </div>
    </div>

    <!-- ステップ2のナビゲーション -->
    <div class="flex justify-between pt-4 border-t mt-6">
        <button type="button" id="back-to-step1"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
            <span class="material-icons-outlined mr-1 text-sm">arrow_back</span>
            {{ __('forms.previous_step') }}
        </button>
        <button type="submit" id="submit-form"
            class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="material-icons-outlined mr-1 text-sm">{{ $submitButtonIcon }}</span>
            {{ $submitButtonText ?? __('navigation.create_room') }}
        </button>
    </div>
</div>
