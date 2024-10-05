<x-guest-layout>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">パスワードの確認</h2>
    <p class="text-sm text-gray-600 mb-4 text-center">
        アプリケーションの安全な領域です。続行する前にパスワードを確認してください。
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('パスワード')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-primary-button class="w-full justify-center bg-teal-600 hover:bg-teal-500">
                {{ __('確認') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
