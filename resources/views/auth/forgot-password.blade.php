<x-guest-layout>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">パスワードをお忘れですか？</h2>
    <p class="text-sm text-gray-600 mb-4 text-center">
        パスワードをお忘れですか？問題ありません。メールアドレスをお知らせいただければ、新しいパスワードを選択できるパスワードリセットリンクをメールでお送りします。
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('メールアドレス')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-primary-button class="w-full justify-center bg-teal-600 hover:bg-teal-500">
                {{ __('パスワードリセットリンクをメールで送信') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
