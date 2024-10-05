<x-guest-layout>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">メールアドレスの確認</h2>
    <p class="text-sm text-gray-600 mb-4 text-center">
        登録ありがとうございます！開始する前に、送信したメールのリンクをクリックしてメールアドレスを確認してください。メールが届かない場合は再送信いたします。
    </p>

    @if (session('status') == 'verification-link-sent')
    <div class="mb-4 font-medium text-sm text-green-600">
        {{ __('登録時に入力したメールアドレスに新しい確認リンクが送信されました。') }}
    </div>
    @endif

    <div class="mt-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="w-full justify-center">
                    {{ __('確認メールを再送信') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit"
                class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('ログアウト') }}
            </button>
        </form>
    </div>
</x-guest-layout>
