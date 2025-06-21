<x-app-layout>
    <!-- ヘッダー -->
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <!-- メインコンテンツ -->
    <div class="bg-gradient-to-br from-slate-50 to-white">
        <!-- ヒーローセクション -->
        <div class="relative overflow-hidden">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
                <div class="text-center">
                    <h1 class="text-4xl md:text-6xl font-light text-gray-900 mb-6 tracking-tight">
                        {{ __('messages.start_online_debate') }}
                    </h1>
                    <p class="text-lg md:text-xl text-gray-600 mb-12 max-w-3xl mx-auto leading-relaxed">
                        {{ __('messages.welcome_description') }}
                    </p>

                    <!-- メインアクション -->
                    <div class="flex flex-col sm:flex-row justify-center gap-4 mb-16">
                        <a href="{{ route('rooms.create') }}"
                           class="group inline-flex items-center justify-center px-8 py-4 text-base font-medium rounded-xl bg-gray-900 text-white hover:bg-gray-800 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('messages.create_room') }}
                        </a>
                        <a href="{{ route('rooms.index') }}"
                           class="inline-flex items-center justify-center px-8 py-4 text-base font-medium rounded-xl bg-white text-gray-900 border border-gray-200 hover:border-gray-300 hover:shadow-md transition-all duration-200">
                            <i class="fa-solid fa-door-open mr-2"></i>
                            {{ __('messages.search_room') }}
                        </a>
                    </div>

                    <!-- AIディベートボタン -->
                    <div class="flex justify-center">
                        <a href="{{ route('ai.debate.create') }}"
                           class="group relative inline-flex items-center justify-center px-8 py-4 text-base font-medium rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                            <span class="material-icons-outlined mr-2 group-hover:scale-110 transition-transform">smart_toy</span>
                            {{ __('messages.ai_debate_button') }}
                            <span class="absolute -top-2 -right-2 bg-amber-400 text-gray-900 text-xs font-bold px-2 py-1 rounded-full">β</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 特徴セクション -->
        <div class="py-20 bg-gray-50">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-light text-gray-900 mb-4">
                        {{ __('messages.features_title') }}
                    </h2>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- 特徴カード1 -->
                    <div class="group bg-white rounded-2xl p-8 shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100">
                        <div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center mb-6 group-hover:bg-blue-100 transition-colors">
                            <i class="fa-solid fa-comments text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-gray-900">{{ __('messages.realtime_chat') }}</h3>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('messages.realtime_chat_description') }}
                        </p>
                    </div>

                    <!-- 特徴カード2 -->
                    <div class="group bg-white rounded-2xl p-8 shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100">
                        <div class="w-16 h-16 rounded-2xl bg-green-50 flex items-center justify-center mb-6 group-hover:bg-green-100 transition-colors">
                            <i class="fa-solid fa-clock text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-gray-900">{{ __('messages.time_management') }}</h3>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('messages.time_management_description') }}
                        </p>
                    </div>

                    <!-- 特徴カード3 -->
                    <div class="group bg-white rounded-2xl p-8 shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100">
                        <div class="w-16 h-16 rounded-2xl bg-purple-50 flex items-center justify-center mb-6 group-hover:bg-purple-100 transition-colors">
                            <i class="fa-solid fa-brain text-purple-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-gray-900">{{ __('messages.ai_feedback') }}</h3>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('messages.ai_feedback_description') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 使い方セクション -->
        <div class="py-20 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-light text-gray-900 mb-4">
                        {{ __('messages.how_to_use') }}
                    </h2>
                </div>

                <div class="grid md:grid-cols-3 gap-12">
                    <!-- ステップ1 -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-900 text-white text-xl font-bold mb-6">
                            1
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-gray-900">{{ __('messages.step1_title') }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ __('messages.step1_description') }}</p>
                    </div>

                    <!-- ステップ2 -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-900 text-white text-xl font-bold mb-6">
                            2
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-gray-900">{{ __('messages.step2_title') }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ __('messages.step2_description') }}</p>
                    </div>

                    <!-- ステップ3 -->
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-900 text-white text-xl font-bold mb-6">
                            3
                        </div>
                        <h3 class="text-xl font-semibold mb-4 text-gray-900">{{ __('messages.step3_title') }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ __('messages.step3_description') }}</p>
                    </div>
                </div>

                <!-- ガイドページへのリンク -->
                <div class="text-center mt-16">
                    <a href="{{ route('guide') }}"
                       class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-900 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium">
                        <i class="fa-solid fa-book-open mr-2"></i>
                        {{ __('messages.detailed_guide') }}
                    </a>
                </div>
            </div>
        </div>

        <!-- FAQセクション -->
        <div class="py-20 bg-gray-50">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-light text-gray-900 mb-4">
                        {{ __('messages.faq_title') }}
                    </h2>
                </div>

                <div class="space-y-4">
                    <!-- FAQ項目1：初心者向け -->
                    <div x-data="{ open: false }" class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100">
                        <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-5 text-lg font-medium text-left text-gray-900 hover:bg-gray-50 focus:outline-none transition-colors">
                            <span>{{ __('messages.faq1_question') }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition.duration.300ms class="px-6 py-5 text-gray-600 bg-gray-50 border-t border-gray-100">
                            {!! __('messages.faq1_answer', ['url' => route('guide')]) !!}
                        </div>
                    </div>

                    <!-- FAQ項目2：料金 -->
                    <div x-data="{ open: false }" class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100">
                        <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-5 text-lg font-medium text-left text-gray-900 hover:bg-gray-50 focus:outline-none transition-colors">
                            <span>{{ __('messages.faq2_question') }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition.duration.300ms class="px-6 py-5 text-gray-600 bg-gray-50 border-t border-gray-100">
                            {{ __('messages.faq2_answer') }}
                        </div>
                    </div>

                    <!-- FAQ項目3：必要なもの -->
                    <div x-data="{ open: false }" class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100">
                        <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-5 text-lg font-medium text-left text-gray-900 hover:bg-gray-50 focus:outline-none transition-colors">
                            <span>{{ __('messages.faq3_question') }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition.duration.300ms class="px-6 py-5 text-gray-600 bg-gray-50 border-t border-gray-100">
                            {{ __('messages.faq3_answer') }}
                        </div>
                    </div>

                    <!-- FAQ項目4：スマホ利用 -->
                    <div x-data="{ open: false }" class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100">
                        <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-5 text-lg font-medium text-left text-gray-900 hover:bg-gray-50 focus:outline-none transition-colors">
                            <span>{{ __('messages.faq4_question') }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition.duration.300ms class="px-6 py-5 text-gray-600 bg-gray-50 border-t border-gray-100">
                            {{ __('messages.faq4_answer') }}
                        </div>
                    </div>

                    <!-- FAQ項目5：AIフィードバック -->
                    <div x-data="{ open: false }" class="bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100">
                        <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-5 text-lg font-medium text-left text-gray-900 hover:bg-gray-50 focus:outline-none transition-colors">
                            <span>{{ __('messages.faq5_question') }}</span>
                            <svg class="w-5 h-5 text-gray-500 transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition.duration.300ms class="px-6 py-5 text-gray-600 bg-gray-50 border-t border-gray-100">
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
    @vite(['resources/js/pages/welcome.js'])
</x-app-layout>
