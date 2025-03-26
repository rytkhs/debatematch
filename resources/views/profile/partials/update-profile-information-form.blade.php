<section>
    <header class="mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
            プロフィール情報
        </h2>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            アカウントのプロフィール情報とメールアドレスを更新します。
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-8 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" class="text-sm font-medium" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full rounded-md shadow-sm" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" class="text-sm font-medium" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full rounded-md shadow-sm" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-md">
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        メールアドレスが未認証です。
                        <button form="send-verification" class="underline text-sm text-primary hover:text-primary-dark dark:text-primary-light rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary dark:focus:ring-offset-gray-800">
                            ここをクリックして認証メールを再送信してください。
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-success dark:text-success-light">
                            新しい認証リンクがメールアドレスに送信されました。
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button class="px-5 py-2.5">保存</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-success dark:text-success-light bg-success-light/50 dark:bg-success-light/10 py-1 px-3 rounded-full"
                >保存されました。</p>
            @endif
        </div>
    </form>
</section>
