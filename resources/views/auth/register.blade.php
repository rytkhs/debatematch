<x-guest-layout>
    <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">{{ __('auth.create_account') }}</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('common.name')" />
            <x-text-input id="name" class="block mt-1 w-full"
                type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('common.email')" />
            <x-text-input id="email" class="block mt-1 w-full"
                type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('common.password')" />

            <x-text-input id="password"
                class="block mt-1 w-full" type="password"
                name="password" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('common.confirm_password')" />

            <x-text-input id="password_confirmation"
                class="block mt-1 w-full" type="password"
                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-8">
            <button class="bg-primary text-white py-2 px-4 rounded-md w-full hover:bg-primary-dark focus:outline-none focus:shadow-outline-gray active:bg-gray-800 mb-4">
                {{ __('common.create') }}
            </button>
        </div>

        <div class="mt-4 text-center">
            {{ __('common.already_have_account') }}
            <a href="{{ route('login') }}"
                class="text-gray-500 hover:text-gray-400">{{ __('common.login') }}
            </a>
        </div>

        <div class="relative flex py-3 items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="mx-4 text-gray-500">{{ __('common.or') }}</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>

        <div class="mt-2 flex items-center justify-center">
            @if(config('services.google.client_id'))
            <a href="{{ route('auth.google') }}" class="flex items-center justify-center w-full py-2 border border-gray-300 rounded-md mb-2 hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                    <path d="M1 1h22v22H1z" fill="none" />
                </svg>
                {{ __('common.google_login') }}
            </a>
            @endif
        </div>
    </form>
</x-guest-layout>
