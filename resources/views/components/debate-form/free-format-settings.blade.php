@props(['errors' => null])

<!-- フリーフォーマット設定 -->
<div id="free-format-settings" class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-200 hidden">
    <h3 class="text-sm sm:text-md font-semibold text-gray-700 mb-3 sm:mb-4 flex items-center">
        <span class="material-icons-outlined text-indigo-500 mr-2">tune</span>
        {{ __('messages.free_format_settings') }}
    </h3>

    <!-- フリーフォーマット説明 -->
    <div class="mb-4 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 border-l-4 border-purple-400 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <span class="material-icons-outlined text-purple-600 text-sm">auto_awesome</span>
            </div>
            <div class="ml-3">
                <h4 class="text-sm font-medium text-purple-800 mb-2">{{ __('messages.free_format_benefits') }}</h4>
                <p class="text-xs text-purple-700">{{ __('messages.free_format_description') }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <!-- 1ターンの時間 -->
        <div>
            <label for="turn_duration" class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
                {{ __('messages.turn_duration') }}
                <span class="text-red-500 ml-1">*</span>
            </label>
            <div class="relative">
                <input type="number" name="turn_duration" id="turn_duration"
                    value="{{ old('turn_duration', 3) }}" min="1" max="10"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md transition-colors duration-200 pr-12">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 text-xs sm:text-sm">{{ __('messages.minute_unit') }}</span>
                </div>
            </div>
            @if($errors)
                <x-input-error :messages="$errors->get('turn_duration')" class="mt-2" />
            @endif
        </div>

        <!-- 最大ターン数 -->
        <div>
            <label for="max_turns" class="block text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
                {{ __('messages.max_turns') }}
                <span class="text-red-500 ml-1">*</span>
            </label>
            <div class="relative">
                <input type="number" name="max_turns" id="max_turns"
                    value="{{ old('max_turns', 20) }}" min="2" max="100" step="2"
                    class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full text-xs sm:text-sm border-gray-300 rounded-md transition-colors duration-200 pr-16">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 text-xs sm:text-sm">{{ __('messages.turn_unit') }}</span>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">{{ __('messages.even_numbers_only') }}</p>
            @if($errors)
                <x-input-error :messages="$errors->get('max_turns')" class="mt-2" />
            @endif
        </div>
    </div>
</div>
