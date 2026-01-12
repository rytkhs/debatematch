@php
// 利用可能なロケールと現在のロケールを取得
$availableLocales = config('app.available_locales', ['en' => 'English', 'ja' => '日本語']); // 設定ファイルから取得、なければデフォルト
$currentLocale = App::getLocale();
$currentLocaleName = $availableLocales[$currentLocale] ?? $currentLocale; // 現在の言語名を取得、なければコード表示
@endphp

<nav x-data="{ open: false }" class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- ロゴ -->
            <div class="flex items-center">
                <a href="{{ route('welcome') }}" class="flex items-center">
                    <svg class="h-8 w-8 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4.5V6.5M12 17.5V19.5M4.5 12H6.5M17.5 12H19.5M16.5 16.5L15 15M16.5 7.5L15 9M7.5 16.5L9 15M7.5 7.5L9 9M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-primary">DebateMatch</span>
                </a>
            </div>

            <!-- ナビゲーションリンク & 言語スイッチャー（デスクトップ） -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <!-- メインナビリンク -->
                <div class="flex space-x-4">
                    <x-nav-link :href="route('rooms.create')" :active="request()->routeIs('rooms.create')">
                        <i class="fa-solid fa-plus mr-1"></i>{{ __('navigation.create_room') }}
                    </x-nav-link>
                    <x-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms.index')">
                        <i class="fa-solid fa-door-open mr-1"></i>{{ __('navigation.search_room') }}
                    </x-nav-link>
                    <x-nav-link :href="route('guide')" :active="request()->routeIs('guide')">
                        <i class="fa-solid fa-book mr-1"></i>{{ __('navigation.how_to_use') }}
                    </x-nav-link>
                </div>

                <!-- 言語スイッチャードロップダウン（デスクトップ） -->
                @if (count($availableLocales) > 1)
                <div class="ml-4 relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <i class="fa-solid fa-globe mr-1"></i>
                                <span>{{ $currentLocaleName }}</span>
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @foreach ($availableLocales as $localeCode => $localeName)
                                @if ($localeCode !== $currentLocale) {{-- 現在の言語以外をリスト表示 --}}
                                    <x-dropdown-link href="{{ url('/language/' . $localeCode) }}">
                                        {{ $localeName }}
                                    </x-dropdown-link>
                                @endif
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                </div>
                @endif

                <!-- ログイン/ユーザーメニュー（デスクトップ） -->
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center text-sm font-medium text-gray-600 hover:text-primary hover:border-gray-300 focus:outline-none focus:text-primary transition duration-150 ease-in-out">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF&length=1" class="h-8 w-8 rounded-full mr-2" alt="{{ Auth::user()->name }}">
                                <div class="flex flex-col items-start">
                                    <span>{{ Auth::user()->name }}</span>
                                    @if(Auth::user()->isGuest())
                                        <span class="text-xs text-orange-600 font-medium">{{ __('common.guest_user') }}</span>
                                    @endif
                                </div>
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            {{-- プロフィール等へのリンク --}}
                            <x-dropdown-link :href="route('profile.edit')" class="flex items-center">
                                <i class="fa-solid fa-user mr-2"></i>{{ __('navigation.my_profile') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('records.index')" class="flex items-center">
                                <i class="fa-solid fa-history mr-2"></i>{{ __('navigation.debate_history') }}
                            </x-dropdown-link>

                            <div class="border-t border-gray-200 my-1"></div>
                            {{-- ログアウトフォーム --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center text-red-600 hover:text-red-800">
                                    <i class="fa-solid fa-sign-out-alt mr-2"></i>{{ __('common.logout') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                    @else
                    {{-- ログイン・登録ボタン --}}
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-primary border border-primary rounded-md hover:bg-primary-light transition duration-150 ease-in-out">{{ __('common.login') }}</a>
                        <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark transition duration-150 ease-in-out">{{ __('common.register') }}</a>
                    </div>
                    @endauth
                </div>
            </div>

            <!-- ハンバーガーメニューボタン -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-primary hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-primary transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- モバイルメニュー -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <!-- メインナビリンク（モバイル） -->
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('rooms.create')" :active="request()->routeIs('rooms.create')" class="flex items-center">
                <i class="fa-solid fa-plus mr-2"></i>{{ __('navigation.create_room') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('rooms.index')" :active="request()->routeIs('rooms.index')" class="flex items-center">
                <i class="fa-solid fa-door-open mr-2"></i>{{ __('navigation.search_room') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('guide')" :active="request()->routeIs('guide')" class="flex items-center">
                <i class="fa-solid fa-book mr-2"></i>{{ __('navigation.how_to_use') }}
            </x-responsive-nav-link>
        </div>

        <!-- 言語スイッチャー（モバイル） -->
        @if (count($availableLocales) > 1)
        <div class="border-t border-gray-200 pt-2 pb-1">
             {{-- 現在の言語を表示（非アクティブな項目として） --}}
            <div class="block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-400 text-start text-base font-medium text-indigo-700 bg-indigo-50 flex items-center">
                 <i class="fa-solid fa-globe mr-2"></i>{{ $currentLocaleName }} (Current)
            </div>
             {{-- 他の言語へのリンクをリスト表示 --}}
            @foreach ($availableLocales as $localeCode => $localeName)
                @if ($localeCode !== $currentLocale)
                    <x-responsive-nav-link href="{{ url('/language/' . $localeCode) }}" class="flex items-center">
                         <span class="ml-7">{{ $localeName }}</span> {{-- インデント調整用 --}}
                    </x-responsive-nav-link>
                @endif
            @endforeach
        </div>
        @endif

        <!-- ログインユーザーメニュー（モバイル） -->
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            {{-- ユーザー情報 --}}
            <div class="flex items-center px-4">
                <div class="flex-shrink-0">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF&length=1" class="h-10 w-10 rounded-full" alt="{{ Auth::user()->name }}">
                </div>
                <div class="ml-3">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    @if(Auth::user()->isGuest())
                        <div class="text-sm text-orange-600 font-medium">{{ __('common.guest_user') }}</div>
                    @endif
                </div>
            </div>
            {{-- ユーザーメニューリンク --}}
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="flex items-center">
                    <i class="fa-solid fa-user mr-2"></i>{{ __('navigation.my_profile') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('records.index')" class="flex items-center">
                    <i class="fa-solid fa-history mr-2"></i>{{ __('navigation.debate_history') }}
                </x-responsive-nav-link>

                {{-- ログアウトフォーム --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="flex items-center text-red-600">
                        <i class="fa-solid fa-sign-out-alt mr-2"></i>{{ __('common.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @else
        <!-- 未ログイン時メニュー（モバイル） -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex flex-col space-y-2 px-4 py-2">
                <a href="{{ route('login') }}" class="w-full px-4 py-2 text-center text-sm font-medium text-primary border border-primary rounded-md hover:bg-primary-light transition duration-150 ease-in-out">{{ __('common.login') }}</a>
                <a href="{{ route('register') }}" class="w-full px-4 py-2 text-center text-sm font-medium text-white bg-primary rounded-md hover:bg-primary-dark transition duration-150 ease-in-out">{{ __('common.register') }}</a>
            </div>
        </div>
        @endauth
    </div>
</nav>
