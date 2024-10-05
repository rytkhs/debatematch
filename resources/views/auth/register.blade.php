<x-guest-layout>
    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4 text-center">アカウントの作成</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('ユーザーネーム')" />
            <x-text-input id="name" class="block mt-1 w-full dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200"
                type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2 dark:text-red-400" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('メールアドレス')" />
            <x-text-input id="email" class="block mt-1 w-full dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200"
                type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 dark:text-red-400" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('パスワード')" />

            <x-text-input id="password"
                class="block mt-1 w-full dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200" type="password"
                name="password" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2 dark:text-red-400" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('パスワードの確認')" />

            <x-text-input id="password_confirmation"
                class="block mt-1 w-full dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200" type="password"
                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 dark:text-red-400" />
        </div>

        <div class="mt-4">
            <x-primary-button class="w-full justify-center">
                {{ __('登録') }}
            </x-primary-button>
        </div>

        <div class="mt-6 text-center dark:text-gray-200">
            すでにアカウントをお持ちですか？ <a href="{{ route('login') }}"
                class="text-teal-600 hover:text-teal-500 dark:text-teal-400 dark:hover:text-teal-300">{{ __('ログイン')
                }}</a>
        </div>
        @if(config('services.google.client_id') || config('services.twitter.client_id'))

        <div class="mt-6 text-center text-gray-500 dark:text-gray-400">または</div>

        <div class="mt-4 flex items-center justify-center">
            @if(config('services.google.client_id'))
            <a href="{{ route('login.google') }}"
                class="w-full inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Google_%22G%22_Logo.svg/512px-Google_%22G%22_Logo.svg.png"
                    alt="Google logo" class="w-4 h-4 mr-2">
                Googleで続行
            </a>
            @endif
        </div>

        <div class="mt-4 flex items-center justify-center">
            @if(config('services.twitter.client_id'))
            <a href="{{ route('login.twitter') }}"
                class="w-full inline-flex items-center justify-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Twitter-logo.svg/512px-Twitter-logo.svg.png"
                    alt="X logo" class="w-4 h-4 mr-2">
                Xで続行
            </a>
            @endif
        </div>
        @endif
    </form>
</x-guest-layout>
