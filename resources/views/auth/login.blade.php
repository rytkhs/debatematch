<x-guest-layout>
    <h2 class="text-2xl font-semibold text-gray-700 mb-4 text-center">{{ __('messages.login') }}</h2>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('messages.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('messages.password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-gray-500 hover:text-gray-700 text-sm flex justify-end">{{ __('messages.forgot_password') }}</a>
            @endif
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('messages.remember_me') }}</span>
            </label>
        </div>

        <div class="mt-4">
            <button class="bg-primary text-white py-2 px-4 rounded-md w-full hover:bg-primary-dark focus:outline-none focus:shadow-outline-gray active:bg-gray-800 mb-4">
                {{ __('messages.login') }}
            </button>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('register') }}"
            class="text-gray-600 hover:text-gray-500">
            {{ __('messages.register') }}
        </a>
        </div>
        @if(config('services.google.client_id') || config('services.twitter.client_id'))

        <div class="relative flex py-3 items-center">
            <div class="flex-grow border-t border-gray-300"></div>
            <span class="mx-4 text-gray-500">{{ __('messages.or') }}</span>
            <div class="flex-grow border-t border-gray-300"></div>
        </div>
        <div class="mt-2">
            @if(config('services.google.client_id'))
            <button class="flex items-center justify-center w-full py-2 border border-gray-300 rounded-md mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-apple mr-2" viewBox="0 0 16 16">
                   <path d="M11.182.116a.5.5 0 0 1 .467.562l-1.138 9.077c-.645.79-1.615 1.065-2.431.816-.827-.23-1.518-.643-1.93-1.18-1.181-1.432-2.288-3.534-2.288-5.898 0-2.369 1.044-4.267 2.163-5.093.673-.451 1.115-.914 1.229-1.18a.5.5 0 0 1 .678-.012zm.437.728a.5.5 0 0 1 .827.173c.086.115.118.226.118.358 0 .289-.071.466-.152.654-1.053 1.288-2.497 3.707-2.898 5.542-.445 2.054-.078 3.51 1.536 4.616.672.488 1.124.923 1.577 1.359.453.435.954.972 1.58.811.361-.093.594-.272.785-.519.359-.445.752-1.018.974-1.648.181-.572.209-1.204.209-1.856 0-.868-.157-1.384-.501-1.682z"/>
               </svg>
               {{ __('messages.google_login') }}
           </button>
            @endif
        </div>

        <div class="mt-4">
            @if(config('services.twitter.client_id'))

            <button class="flex items-center justify-center w-full py-2 border border-gray-300 rounded-md mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-google mr-2" viewBox="0 0 16 16">
                    <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.033c0 .748-.171 1.452-.482 2.081a7.05 7.05 0 0 1-3.576 2.154 6.5 6.5 0 0 1-.157-.024c0-.017.014-.03.014-.043a6.78 6.78 0 0 1 .178-.225 6.64 6.64 0 0 1-.256-.148c-.026-.023-.063-.049-.115-.072a6.79 6.79 0 0 1-.038-.013l.003-.002c0-.004 0-.007-.002-.008a6.76 6.76 0 0 1-.22-.084 5.94 5.94 0 0 1-.183-.066c-.033-.02-.084-.047-.14-.078a5.94 5.94 0 0 1-.197-.095c-.016-.016-.04-.035-.07-.051a5.94 5.94 0 0 1-.051-.04c-.007-.005-.017-.012-.028-.018a5.94 5.94 0 0 1-.198-.065c-.045-.034-.078-.068-.13-.108a5.94 5.94 0 0 1-.202-.122a5.94 5.94 0 0 1-.236-.179c-.065-.09-.163-.23-.25-.358a5.94 5.94 0 0 1-.293-.398c-.098-.149-.197-.32-.267-.5a5.94 5.94 0 0 1-.279-.624c-.024-.064-.049-.146-.064-.238a5.94 5.94 0 0 1-.022-.253c-.005-.067-.002-.147.01-.223a5.94 5.94 0 0 1 .108-.583c.037-.145.09-.276.145-.385.11-.225.207-.46.262-.65a5.94 5.94 0 0 1 .053-.111c.004-.008.005-.017.004-.026l-.002-.005a.04.04 0 0 0-.002-.003.079.079 0 0 0-.003-.004l-.003-.001c-.004 0-.005-.001-.007-.001a.009.009 0 0 0-.001 0zM5.282 14.085a8.56 8.56 0 0 1-3.041-.848 8.41 8.41 0 0 1-2.373-2.797 8.43 8.43 0 0 1-.085-2.572c0-1.862.716-3.448 2.004-4.386 1.306-1.011 3.017-1.477 4.392-1.27a3.9 3.9 0 0 0-.006.003l.001.001c.018.004.052.015.101.025.07.014.17.04.255.065a.439.439 0 0 0 .032.023c.008.004.017.007.024.01a13.43 13.43 0 0 1-.057.017a8.42 8.42 0 0 1-.123.021l-.014.004c-.007 0-.012-.001-.017-.001a.036.036 0 0 0-.004 0zm0-1.269c-.393.183-.816.283-1.281.269-1.336-.037-2.456-.836-3.095-2.093-.639-1.257-.703-2.917-.188-4.422.515-1.511 1.467-2.507 2.812-3.035 1.347-.527 2.937-.375 3.888.467.127.116.25.278.36.438.031.048.047.107.068.185.027.1.072.275.126.474l.034.119a.032.032 0 0 1 .006.008z"/>
                </svg>
                {{ __('messages.twitter_login') }}
            </button>
            @endif
        </div>
        @endif
    </form>

</x-guest-layout>
