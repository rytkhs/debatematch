<x-app-layout>
    <!-- ヘッダー -->
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

        <!-- メインコンテンツ -->
        <div class="bg-white">
            <!-- ヒーローセクション -->
            <div class="overflow-hidden bg-primary-light/40">

                <!-- メイン -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-28">
                    <div class="text-center">
                        <div>
                            <h2 class="text-2xl md:text-4xl font-bold text-gray-900 mb-4 md:mb-6">
                                {{ __('messages.start_online_debate') }}
                            </h2>
                            <p class="text-base md:text-lg text-gray-600 mb-12 md:mb-20 max-w-2xl mx-auto">
                                {{ __('messages.welcome_description') }}
                            </p>
                            <!-- メインアクション -->
                            <div class="flex flex-col md:flex-row justify-center gap-3 md:gap-4 mb-12 md:mb-16">
                                <a href="{{ route('rooms.create') }}" class="hero-button bg-primary text-white hover:bg-primary-dark text-sm md:text-base">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ __('messages.create_room') }}
                                </a>
                                <a href="{{route('rooms.index')}}" class="hero-button bg-white text-primary border-2 border-primary hover:bg-primary hover:text-white text-sm md:text-base">
                                    <i class="fa-solid fa-door-open mr-2"></i>
                                    {{ __('messages.search_room') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 波形の装飾 -->
                <div class="w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" fill="#ffffff">
                        <path d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z"></path>
                    </svg>
                </div>
            </div>

            <!-- 特徴セクション -->
            <div class="py-12 md:py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-10 md:mb-16">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                            {{ __('messages.features_title') }}
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6 md:gap-8">
                        <!-- 特徴カード1 -->
                        <div class="feature-card shadow-md p-5">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-primary-light flex items-center justify-center mb-4 md:mb-5">
                                <i class="fa-solid fa-comments text-primary text-lg md:text-xl"></i>
                            </div>
                            <h3 class="text-lg md:text-xl font-semibold mb-2 md:mb-3 text-gray-900">{{ __('messages.realtime_chat') }}</h3>
                            <p class="text-sm md:text-base text-gray-600">
                                {{ __('messages.realtime_chat_description') }}
                            </p>
                        </div>

                        <!-- 特徴カード2 -->
                        <div class="feature-card shadow-md p-5">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-primary-light flex items-center justify-center mb-4 md:mb-5">
                                <i class="fa-solid fa-clock text-primary text-lg md:text-xl"></i>
                            </div>
                            <h3 class="text-lg md:text-xl font-semibold mb-2 md:mb-3 text-gray-900">{{ __('messages.time_management') }}</h3>
                            <p class="text-sm md:text-base text-gray-600">
                                {{ __('messages.time_management_description') }}
                            </p>
                        </div>

                        <!-- 特徴カード3 -->
                        <div class="feature-card shadow-md p-5">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-primary-light flex items-center justify-center mb-4 md:mb-5">
                                <i class="fa-solid fa-brain text-primary text-lg md:text-xl"></i>
                            </div>
                            <h3 class="text-lg md:text-xl font-semibold mb-2 md:mb-3 text-gray-900">{{ __('messages.ai_feedback') }}</h3>
                            <p class="text-sm md:text-base text-gray-600">
                                {{ __('messages.ai_feedback_description') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 使い方セクション -->
            <div class="py-12 md:py-16 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-10 md:mb-16">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                            {{ __('messages.how_to_use') }}
                        </h2>
                    </div>

                    <div class="relative">
                        <!-- 接続線 -->
                        <div class="hidden md:block absolute top-24 left-0 right-0 h-1 bg-primary"></div>

                        <div class="grid md:grid-cols-3 gap-6 md:gap-8">
                            <!-- ステップ1 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-10 h-10 md:w-12 md:h-12 rounded-full bg-primary text-white flex items-center justify-center mb-4 md:mb-5 text-lg md:text-xl font-bold">1</div>
                                <div class="text-center">
                                    <h3 class="text-lg md:text-xl font-semibold mb-2 md:mb-3">{{ __('messages.step1_title') }}</h3>
                                    <p class="text-sm md:text-base text-gray-600">{{ __('messages.step1_description') }}</p>
                                </div>
                            </div>

                            <!-- ステップ2 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-10 h-10 md:w-12 md:h-12 rounded-full bg-primary text-white flex items-center justify-center mb-4 md:mb-5 text-lg md:text-xl font-bold">2</div>
                                <div class="text-center">
                                    <h3 class="text-lg md:text-xl font-semibold mb-2 md:mb-3">{{ __('messages.step2_title') }}</h3>
                                    <p class="text-sm md:text-base text-gray-600">{{ __('messages.step2_description') }}</p>
                                </div>
                            </div>

                            <!-- ステップ3 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-10 h-10 md:w-12 md:h-12 rounded-full bg-primary text-white flex items-center justify-center mb-4 md:mb-5 text-lg md:text-xl font-bold">3</div>
                                <div class="text-center">
                                    <h3 class="text-lg md:text-xl font-semibold mb-2 md:mb-3">{{ __('messages.step3_title') }}</h3>
                                    <p class="text-sm md:text-base text-gray-600">{{ __('messages.step3_description') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQセクション -->
            <div class="py-12 md:py-16 bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-10 md:mb-16">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900">
                            {{ __('messages.faq_title') }}
                        </h2>
                    </div>

                    <div class="space-y-4 md:space-y-6">
                        <!-- FAQ項目1：初心者向け -->
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>{{ __('messages.faq1_question') }}</span>
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition.duration.300ms class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-gray-50">
                                {!! __('messages.faq1_answer', ['url' => route('guide')]) !!}
                            </div>
                        </div>

                        <!-- FAQ項目2：料金 -->
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>{{ __('messages.faq2_question') }}</span>
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition.duration.300ms class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-gray-50">
                                {{ __('messages.faq2_answer') }}
                            </div>
                        </div>

                        <!-- FAQ項目3：必要なもの -->
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>{{ __('messages.faq3_question') }}</span>
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition.duration.300ms class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-gray-50">
                                {{ __('messages.faq3_answer') }}
                            </div>
                        </div>

                        <!-- FAQ項目4：スマホ利用 -->
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>{{ __('messages.faq4_question') }}</span>
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition.duration.300ms class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-gray-50">
                                {{ __('messages.faq4_answer') }}
                            </div>
                        </div>

                        <!-- FAQ項目5：AIフィードバック -->
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>{{ __('messages.faq5_question') }}</span>
                                <svg class="w-4 h-4 md:w-5 md:h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition.duration.300ms class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-gray-50">
                                {{ __('messages.faq5_answer') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- フッター -->
        <x-slot name="footer">
            <x-footer></x-footer>
        </x-slot>
</x-app-layout>
