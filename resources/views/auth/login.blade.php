<x-guest-layout>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">ログイン</h2>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('メールアドレス')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('パスワード')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('ログイン状態を維持する') }}</span>
            </label>
        </div>

        <div class="mt-4">
            <x-primary-button class="w-full justify-center bg-teal-600 hover:bg-teal-500">
                {{ __('ログイン') }}
            </x-primary-button>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('password.request') }}" class="text-teal-600 hover:text-teal-500">
                {{ __('パスワードをお忘れですか?') }}
            </a>
        </div>

        <div class="mt-6 text-center text-gray-500">または</div>

        <div class="mt-4">
            {{-- <a href="{{ route('login.google') }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"> --}}
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Google_%22G%22_Logo.svg/512px-Google_%22G%22_Logo.svg.png" alt="Google logo" class="w-4 h-4 mr-2">
                Googleで続行
            {{-- </a> --}}
        </div>

        <div class="mt-4">
            {{-- <a href="{{ route('login.twitter') }}" class="w-full inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"> --}}
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Twitter-logo.svg/512px-Twitter-logo.svg.png" alt="X logo" class="w-4 h-4 mr-2">
                Xで続行
            {{-- </a> --}}
        </div>
    </form>
</x-guest-layout>
