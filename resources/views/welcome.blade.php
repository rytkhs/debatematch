<x-app-layout>
    <!-- ヘッダー -->
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

        <!-- メインコンテンツ -->
        <div class="bg-white">
            <!-- ヒーローセクション -->
            <div class="overflow-hidden bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
            <!-- パーティクル背景 -->
                <div id="particles-container" class="absolute inset-0 overflow-hidden">
                    <canvas id="particles-canvas" class="absolute inset-0"></canvas>
                </div>
                <!-- メイン -->
                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-28">
                    <div class="text-center">
                        <div>
                            <h2 class="text-2xl md:text-4xl font-bold text-gray-900 mb-4 md:mb-6 animate-fade-in">
                                {{ __('welcome.start_online_debate') }}
                            </h2>
                            <p class="text-base md:text-lg text-gray-600 mb-12 md:mb-20 max-w-2xl mx-auto opacity-90">
                                {{ __('welcome.welcome_description') }}
                            </p>
                            <!-- メインアクション -->
                            <div class="flex flex-col md:flex-row justify-center gap-3 md:gap-4 mb-12 md:mb-16">
                                <a href="{{ route('rooms.create') }}" class="inline-flex items-center justify-center px-8 py-3 bg-primary text-white font-semibold rounded-full hover:bg-primary-dark transition-all transform hover:scale-105 shadow-lg text-sm md:text-base">
                                    <span class="material-icons mr-2">add_circle</span>
                                    {{ __('navigation.create_room') }}
                                </a>
                                <a href="{{route('rooms.index')}}" class="inline-flex items-center justify-center px-8 py-3 bg-white text-primary border-2 border-primary font-semibold rounded-full hover:bg-primary hover:text-white transition-all transform hover:scale-105 shadow-lg text-sm md:text-base">
                                    <span class="material-icons mr-2">meeting_room</span>
                                    {{ __('navigation.search_room') }}
                                </a>
                            </div>

                            <!-- AIとディベートボタン -->
                            <div class="flex justify-center mb-8">
                                <a href="{{ route('ai.debate.create') }}" class="inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-teal-400 to-blue-500 text-white font-semibold rounded-full hover:from-teal-500 hover:to-blue-600 transition-all transform hover:scale-105 shadow-lg text-sm md:text-base relative group">
                                    <span class="material-icons mr-2">smart_toy</span>
                                    {{ __('ai_debate.ai_debate_button') }}
                                    <span class="absolute -top-2 -right-2 bg-yellow-400 text-xs font-bold px-2 py-0.5 rounded-full opacity-95">β</span>
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
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                            <span class="text-gradient bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                {{ __('welcome.features_title') }}
                            </span>
                        </h2>
                        <p class="text-gray-600">{{ __('guide.features_subtitle') }}</p>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6 md:gap-8">
                        <!-- 特徴カード1 -->
                        <div class="relative group">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                            <div class="relative bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 p-6">
                                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-4">
                                    <span class="material-icons text-blue-600 text-2xl">chat</span>
                                </div>
                                <h3 class="text-lg md:text-xl font-semibold mb-3 text-gray-900">{{ __('welcome.realtime_chat') }}</h3>
                                <p class="text-sm md:text-base text-gray-600">
                                    {{ __('welcome.realtime_chat_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- 特徴カード2 -->
                        <div class="relative group">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                            <div class="relative bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 p-6">
                                <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-4">
                                    <span class="material-icons text-orange-600 text-2xl">timer</span>
                                </div>
                                <h3 class="text-lg md:text-xl font-semibold mb-3 text-gray-900">{{ __('welcome.time_management') }}</h3>
                                <p class="text-sm md:text-base text-gray-600">
                                    {{ __('welcome.time_management_description') }}
                                </p>
                            </div>
                        </div>

                        <!-- 特徴カード3 -->
                        <div class="relative group">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-pink-600 to-red-600 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                            <div class="relative bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 p-6">
                                <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-4">
                                    <span class="material-icons text-purple-600 text-2xl">psychology</span>
                                </div>
                                <h3 class="text-lg md:text-xl font-semibold mb-3 text-gray-900">{{ __('welcome.ai_feedback') }}</h3>
                                <p class="text-sm md:text-base text-gray-600">
                                    {{ __('welcome.ai_feedback_description') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 使い方セクション -->
            <div class="py-12 md:py-16 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-10 md:mb-16">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                            {{ __('navigation.how_to_use') }}
                        </h2>
                        <p class="text-gray-600">{{ __('guide.quick_start_steps') }}</p>
                    </div>

                    <div class="relative">
                        <!-- 接続線 -->
                        <div class="hidden md:block absolute top-24 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-full"></div>

                        <div class="grid md:grid-cols-3 gap-6 md:gap-8">
                            <!-- ステップ1 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 text-white flex items-center justify-center mb-5 text-xl font-bold shadow-lg">1</div>
                                <div class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                                    <h3 class="text-lg md:text-xl font-semibold mb-3 text-gray-900">{{ __('welcome.step1_title') }}</h3>
                                    <p class="text-sm md:text-base text-gray-600">{{ __('welcome.step1_description') }}</p>
                                </div>
                            </div>

                            <!-- ステップ2 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-12 h-12 rounded-full bg-gradient-to-r from-purple-500 to-purple-600 text-white flex items-center justify-center mb-5 text-xl font-bold shadow-lg">2</div>
                                <div class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                                    <h3 class="text-lg md:text-xl font-semibold mb-3 text-gray-900">{{ __('welcome.step2_title') }}</h3>
                                    <p class="text-sm md:text-base text-gray-600">{{ __('welcome.step2_description') }}</p>
                                </div>
                            </div>

                            <!-- ステップ3 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-12 h-12 rounded-full bg-gradient-to-r from-pink-500 to-pink-600 text-white flex items-center justify-center mb-5 text-xl font-bold shadow-lg">3</div>
                                <div class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                                    <h3 class="text-lg md:text-xl font-semibold mb-3 text-gray-900">{{ __('welcome.step3_title') }}</h3>
                                    <p class="text-sm md:text-base text-gray-600">{{ __('welcome.step3_description') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ガイドページへのリンク -->
                    <div class="text-center mt-10 md:mt-12">
                        <a href="{{ route('guide') }}" class="inline-flex items-center justify-center px-8 py-3 bg-white text-blue-600 border-2 border-blue-600 font-semibold rounded-full hover:bg-blue-600 hover:text-white transition-all transform hover:scale-105 shadow-lg">
                            <span class="material-icons mr-2">auto_stories</span>
                            {{ __('welcome.detailed_guide') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- FAQセクション -->
            <div class="py-12 md:py-16 bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-10 md:mb-16">
                        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                            {{ __('welcome.faq_title') }}
                        </h2>
                        <p class="text-gray-600">{{ __('guide.faq_answer_text') }}</p>
                    </div>

                    <div class="space-y-4 md:space-y-6">
                        <!-- FAQ項目1：初心者向け -->
                        <div x-data="{ open: false }" class="bg-gray-50 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 hover:bg-gray-100 focus:outline-none transition-colors">
                                <div class="flex items-center">
                                    <span class="text-blue-500 font-semibold mr-3">Q1.</span>
                                    <span>{{ __('welcome.faq1_question') }}</span>
                                </div>
                                <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-white border-t border-gray-200">
                                {!! __('welcome.faq1_answer', ['url' => route('guide')]) !!}
                            </div>
                        </div>

                        <!-- FAQ項目2：料金 -->
                        <div x-data="{ open: false }" class="bg-gray-50 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 hover:bg-gray-100 focus:outline-none transition-colors">
                                <div class="flex items-center">
                                    <span class="text-blue-500 font-semibold mr-3">Q2.</span>
                                    <span>{{ __('welcome.faq2_question') }}</span>
                                </div>
                                <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-white border-t border-gray-200">
                                {{ __('welcome.faq2_answer') }}
                            </div>
                        </div>

                        <!-- FAQ項目3：必要なもの -->
                        <div x-data="{ open: false }" class="bg-gray-50 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 hover:bg-gray-100 focus:outline-none transition-colors">
                                <div class="flex items-center">
                                    <span class="text-blue-500 font-semibold mr-3">Q3.</span>
                                    <span>{{ __('welcome.faq3_question') }}</span>
                                </div>
                                <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-white border-t border-gray-200">
                                {{ __('welcome.faq3_answer') }}
                            </div>
                        </div>

                        <!-- FAQ項目4：スマホ利用 -->
                        <div x-data="{ open: false }" class="bg-gray-50 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 hover:bg-gray-100 focus:outline-none transition-colors">
                                <div class="flex items-center">
                                    <span class="text-blue-500 font-semibold mr-3">Q4.</span>
                                    <span>{{ __('welcome.faq4_question') }}</span>
                                </div>
                                <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-white border-t border-gray-200">
                                {{ __('welcome.faq4_answer') }}
                            </div>
                        </div>

                        <!-- FAQ項目5：AIフィードバック -->
                        <div x-data="{ open: false }" class="bg-gray-50 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-4 md:px-6 py-3 md:py-4 text-base md:text-lg font-medium text-left text-gray-900 hover:bg-gray-100 focus:outline-none transition-colors">
                                <div class="flex items-center">
                                    <span class="text-blue-500 font-semibold mr-3">Q5.</span>
                                    <span>{{ __('welcome.faq5_question') }}</span>
                                </div>
                                <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0" class="px-4 md:px-6 py-3 md:py-4 text-sm md:text-base text-gray-600 bg-white border-t border-gray-200">
                                {{ __('welcome.faq5_answer') }}
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

    <style>
        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .animate-fade-in {
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-button {
            @apply inline-flex items-center justify-center px-8 py-3 font-semibold rounded-full transition-all transform hover:scale-105 shadow-lg;
        }

        .feature-card {
            @apply bg-white rounded-xl transition-all duration-300 transform hover:-translate-y-1;
        }
    </style>

    @vite(['resources/js/pages/welcome.js'])
</x-app-layout>
