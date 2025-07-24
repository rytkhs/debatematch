<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{-- 登録ありがとうございます！始める前に、メールでお送りしたリンクをクリックしてメールアドレスを確認していただけますか？メールが届かない場合は、再送いたします。 --}}
        {{ __('auth.verify_email_message') }}
    </div>

    <!-- 認証メール遅延の注意メッセージ -->
    <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
        <div class="flex items-center">
            <svg class="w-4 h-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-sm text-blue-800">
                {{ __('auth.verification_email_delay_notice') }}
            </p>
        </div>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{-- 新しい確認リンクが、登録時に提供されたメールアドレスに送信されました。 --}}
            {{ __('auth.verification_link_sent') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{-- 確認メールを再送 --}}
                    {{ __('auth.resend_verification_email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                {{ __('common.logout') }}
            </button>
        </form>
    </div>
</x-guest-layout>
