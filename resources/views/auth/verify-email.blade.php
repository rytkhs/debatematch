<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-2">
            {{ __('auth.otp_verification_title') }}
        </h2>
        <p class="text-sm text-gray-600">
            {{ __('auth.otp_verification_instruction') }}
        </p>
    </div>

    <!-- Email address display -->
    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
        <div class="flex items-center">
            <svg class="w-4 h-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
            </svg>
            <p class="text-sm text-blue-800">
                {{ __('auth.otp_sent_to', ['email' => $maskedEmail]) }}
            </p>
        </div>
    </div>

    <!-- 認証メール遅延の注意メッセージ -->
    <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-md">
        <div class="flex items-center">
            <svg class="w-4 h-4 text-gray-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-sm text-gray-700">
                {{ __('auth.verification_email_delay_notice') }}
            </p>
        </div>
    </div>

    <!-- Success message -->
    @if (session('status') == 'otp-sent')
        <div class="mb-4 font-medium text-sm text-green-600 p-3 bg-green-50 border border-green-200 rounded-md">
            {{ __('auth.verification_link_sent') }}
        </div>
    @endif

    <!-- Error messages -->
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
            <div class="flex">
                <svg class="w-4 h-4 text-red-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    @foreach ($errors->all() as $error)
                        <p class="text-sm text-red-800">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- OTP Verification Form -->
    <form method="POST" action="{{ route('verification.verify') }}" class="mb-6">
        @csrf

        <div class="mb-4">
            <x-input-label for="otp" :value="__('auth.otp_code_label')" />
            <x-text-input
                id="otp"
                class="block mt-1 w-full text-center text-2xl font-mono tracking-widest @error('otp') border-red-500 @enderror"
                type="text"
                name="otp"
                :value="old('otp')"
                required
                autofocus
                autocomplete="off"
                maxlength="6"
                pattern="[0-9]{6}"
                placeholder="000000"
                inputmode="numeric"
            />
            <p class="mt-1 text-xs text-gray-500">
                {{ __('auth.otp_enter_code') }}
            </p>
        </div>

        <div class="flex items-center justify-center">
            <x-primary-button class="w-full justify-center">
                {{ __('auth.otp_verify_button') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Resend and Logout buttons -->
    <div class="flex items-center justify-between">
        <form id="resend-form" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-600 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-400 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('auth.otp_resend_button') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('common.logout') }}
            </button>
        </form>
    </div>

    @push('scripts')
        @vite('resources/js/pages/otp-verify.js')
    @endpush
</x-guest-layout>
