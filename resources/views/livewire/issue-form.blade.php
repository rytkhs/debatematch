<div class="max-w-4xl mx-auto">
    @if ($submitted)
        <!-- 送信完了メッセージ -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-green-100 rounded-full">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-green-800 mb-2">
                {{ __('Thank you for your feedback!') }}
            </h3>
            <p class="text-green-700 mb-4">
                {{ __('We have received your issue report and will review it as soon as possible.') }}
            </p>
            <p class="text-sm text-green-600 mb-4">
                {{ __('Reference ID') }}: #{{ $contactId }}
            </p>
            <button
                wire:click="resetForm"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150"
            >
                {{ __('Submit Another Issue') }}
            </button>
        </div>
    @else
        <!-- 問題報告・機能リクエストフォーム -->
        <form wire:submit.prevent="submit" class="space-y-6">
            @if (session()->has('error'))
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 問題種別 -->
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Issue Type') }} <span class="text-red-500">*</span>
                </label>
                <select
                    wire:model.live="type"
                    id="type"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('type') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                >
                    <option value="">{{ __('Please select') }}</option>
                    @foreach ($this->issueTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- 件名 -->
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Subject') }} <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    wire:model.live="subject"
                    id="subject"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('subject') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    placeholder="{{ __('Brief description of the issue') }}"
                >
                @error('subject')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- メッセージ -->
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                    {{ __('Message') }} <span class="text-red-500">*</span>
                </label>
                <textarea
                    wire:model.live="message"
                    id="message"
                    rows="6"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('message') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                    placeholder="{{ __('Please provide detailed information about the issue...') }}"
                ></textarea>
                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('Minimum 10 characters, maximum 5000 characters') }}
                </p>
            </div>

            <!-- 送信ボタン -->
            <div class="flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                >
                    <span wire:loading.delay.remove>{{ __('Submit Issue') }}</span>
                    <span wire:loading.delay.long class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('Sending...') }}
                    </span>
                </button>
            </div>
        </form>
    @endif
</div>
