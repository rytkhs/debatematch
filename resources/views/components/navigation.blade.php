<nav x-data="{ open: false }" class="bg-transparent border-b border-gray-100">
    <div class="mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex justify-between items-center">
            <!-- ロゴ -->
            <h1 class="text-2xl font-medium text-primary">
                <a href="{{ route('welcome') }}">
                    DebateMatch
                </a>
            </h1>

            <!-- ナビゲーションリンク -->
            <div class="hidden sm:flex space-x-6">
                <x-nav-link :href="route('dashboard')"
                :active="request()->routeIs('dashboard')">使い方</x-nav-link>
                @auth
                <a href="{{ route('welcome') }}" class="flex items-center text-gray-600 hover:text-primary transition-colors duration-200 ease-in-out">
                    <i class="fa-solid fa-house w-4 h-3 mr-1 mb-1"></i>
                    <span class="hidden sm:inline">ホーム</span>
                </a>
                <x-dropdown>
                    <x-slot name="trigger">
                        <button class="nav-link flex items-center">
                            {{ Auth::user()->name }}
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('プロフィール') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('ログアウト') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                <x-nav-link :href="route('login')">ログイン</x-nav-link>
                <x-nav-link :href="route('register')">新規登録</x-nav-link>
                @endauth
            </div>

            <!-- Hamburger -->
            <button @click="open = ! open" class="sm:hidden text-gray-600 hover:text-primary transition-colors duration-200 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div x-cloak
         x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform -translate-y-2"
         class="sm:hidden absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
         @click.outside="open = false">
        <div class="py-1">
            <x-responsive-nav-link :href="route('dashboard')">
                使い方
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('プロフィール') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('ログアウト') }}
                    </x-responsive-nav-link>
                </form>
            @else
                <x-responsive-nav-link :href="route('login')">
                    ログイン
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('register')">
                    新規登録
                </x-responsive-nav-link>
            @endauth
        </div>
    </div>
</nav>
