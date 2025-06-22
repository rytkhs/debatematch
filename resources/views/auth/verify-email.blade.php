<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{-- 登録ありがとうございます！始める前に、メールでお送りしたリンクをクリックしてメールアドレスを確認していただけますか？メールが届かない場合は、再送いたします。 --}}
        {{ __('auth.verify_email_message') }}
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
