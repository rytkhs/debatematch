<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-gray-100 font-open-sans text-[#333333]">
    <header class="w-full flex justify-between p-4 bg-white shadow-md">
        <h1 class="text-2xl font-bold text-[#333333]">DebateMatch</h1>
        <div class="flex space-x-4">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                        <div>ユーザー名</div>

                        <div class="ms-1">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">
                        プロフィール
                    </x-dropdown-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                            this.closest('form').submit();">
                            ログアウト
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </header>
    <main class="flex flex-col items-center w-full flex-1 p-4">
        <form action="{{ route('rooms.store') }}" method="POST" class="bg-white p-6 rounded shadow-md w-full max-w-[600px] space-y-4">
            @csrf
            <div>
                <x-input-label for="name" :value="__('ルーム名')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')"  placeholder="例: ディベート甲子園練習ルーム" autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="mt-4">
                <x-input-label for="topic" :value="__('ディベートテーマ')" />
                <x-text-input id="topic" class="block mt-1 w-full" type="text" name="topic" :value="old('topic')" placeholder="例: 日本は内閣による衆議院の解散権を制限すべきである。是か非か" />
                <x-input-error :messages="$errors->get('topic')" class="mt-2" />
            </div>
            <div class="block mt-4">
                <x-input-label :value="__('ルームの公開設定')" />
                <div class="flex items-center space-x-4 mt-2">
                    <label class="flex items-center">
                        <input type="radio" name="privacy" value="public" {{ old('privacy') === 'public' ? 'checked' : '' }} class="mr-2">
                        公開
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="privacy" value="private" {{ old('privacy') === 'private' ? 'checked' : '' }} class="mr-2">
                        非公開
                    </label>
                </div>
            </div>
            <x-primary-button class="mt-4">
                {{ __('作成') }}
            </x-primary-button>
        </form>
    </main>
</body>
</html>
