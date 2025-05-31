<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    {{-- ページヘッダー --}}
    <div class="bg-white pt-12 sm:pt-16 pb-8 sm:pb-12">
        <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
            {{-- ページタイトル --}}
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-primary mb-3 sm:mb-4">{{
                __('messages.guide_title') }}</h1>
            {{-- ページ概要説明 --}}
            <p class="text-base sm:text-lg text-gray-600 max-w-3xl mx-auto">
                {{ __('messages.guide_description') }}
            </p>
        </div>
    </div>

    {{-- 主な機能セクション --}}
    <section class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 text-center mb-8 sm:mb-12">{{
                __('messages.main_features') }}</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
                {{-- 機能カード: ルーム管理 --}}
                <div class="feature-card bg-white p-4 sm:p-6 rounded-lg shadow-sm text-center">
                    <div
                        class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-primary-light flex items-center justify-center mb-3 sm:mb-5 mx-auto">
                        <span class="material-icons text-primary text-2xl sm:text-3xl">meeting_room</span>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3 text-gray-900">{{
                        __('messages.room_management') }}</h3>
                    <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.room_management_description') }}</p>
                </div>
                {{-- 機能カード: リアルタイムチャット --}}
                <div class="feature-card bg-white p-4 sm:p-6 rounded-lg shadow-sm text-center">
                    <div
                        class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-primary-light flex items-center justify-center mb-3 sm:mb-5 mx-auto">
                        <span class="material-icons text-primary text-2xl sm:text-3xl">chat</span>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3 text-gray-900">{{
                        __('messages.realtime_chat') }}</h3>
                    <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.realtime_chat_feature_description') }}
                    </p>
                </div>
                {{-- 機能カード: 自動進行 & タイマー --}}
                <div class="feature-card bg-white p-4 sm:p-6 rounded-lg shadow-sm text-center">
                    <div
                        class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-primary-light flex items-center justify-center mb-3 sm:mb-5 mx-auto">
                        <span class="material-icons text-primary text-2xl sm:text-3xl">timer</span>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3 text-gray-900">{{
                        __('messages.auto_progress_timer') }}</h3>
                    <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.auto_progress_timer_description') }}</p>
                </div>
                {{-- 機能カード: AIによる講評 --}}
                <div class="feature-card bg-white p-4 sm:p-6 rounded-lg shadow-sm text-center">
                    <div
                        class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-primary-light flex items-center justify-center mb-3 sm:mb-5 mx-auto">
                        <span class="material-icons text-primary text-2xl sm:text-3xl">psychology</span>
                    </div>
                    <h3 class="text-lg sm:text-xl font-semibold mb-2 sm:mb-3 text-gray-900">{{
                        __('messages.ai_critique') }}</h3>
                    <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.ai_critique_description') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ディベートの流れセクション --}}
    <section class="py-12 sm:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 text-center mb-8 sm:mb-12">{{
                __('messages.debate_flow') }}</h2>
            <div class="space-y-8 sm:space-y-12">
                {{-- ステップ 1: 準備 --}}
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold text-primary mb-4 sm:mb-6 flex items-center">
                        <span class="material-icons mr-2">how_to_reg</span> {{ __('messages.step1_preparation') }}
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4 sm:gap-8">
                        {{-- 準備ステップ1: ユーザー登録・ログイン --}}
                        <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{ __('messages.prep_step1_title')
                                }}</h4>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-4">{!! __('messages.prep_step1_desc1',
                                ['register_url' => route('register'), 'login_url' => route('login')]) !!}</p>
                            <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.prep_step1_desc2') }}</p>
                        </div>
                        {{-- 準備ステップ2: ルームを探す or 作成する --}}
                        <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{ __('messages.prep_step2_title')
                                }}</h4>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2">
                                {!! __('messages.prep_step2_desc1', ['index_url' => route('rooms.index'), 'create_url'
                                => route('rooms.create')]) !!}
                            </p>
                            <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.prep_step2_desc2') }}</p>
                        </div>
                    </div>
                </div>

                {{-- ステップ 2: マッチング --}}
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold text-primary mb-4 sm:mb-6 flex items-center">
                        <span class="material-icons mr-2">groups</span> {{ __('messages.step2_matching') }}
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4 sm:gap-8">
                        {{-- マッチングステップ1: ルームに参加する --}}
                        <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{
                                __('messages.match_step1_title') }}</h4>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-4">{{ __('messages.match_step1_desc1')
                                }}</p>
                            <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.match_step1_desc2') }}</p>
                        </div>
                        {{-- マッチングステップ2: 待機と開始 --}}
                        <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{
                                __('messages.match_step2_title') }}</h4>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2">{{ __('messages.match_step2_desc1') }}</p>
                            <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.match_step2_desc2') }}</p> {{--
                            "ディベート開始" ボタンのテキストは language ファイルにある start_debate を使う想定 --}}
                        </div>
                    </div>
                </div>

                {{-- ステップ 3: ディベート --}}
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold text-primary mb-4 sm:mb-6 flex items-center">
                        <span class="material-icons mr-2">gavel</span> {{ __('messages.step3_debate') }}
                    </h3>
                    {{-- ディベート画面の説明 --}}
                    <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200 mb-4 sm:mb-8">
                        <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{ __('messages.debate_step1_title')
                            }}</h4>
                        <div class="space-y-2 sm:space-y-4 text-xs sm:text-sm text-gray-600">
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_timeline')
                                    }}:</strong> {{ __('messages.debate_timeline_desc') }}</p>
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_chat_area')
                                    }}:</strong> {{ __('messages.debate_chat_area_desc') }}</p>
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_message_input')
                                    }}:</strong> {{ __('messages.debate_message_input_desc') }}</p>
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_participant_list')
                                    }}:</strong> {{ __('messages.debate_participant_list_desc') }}</p>
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_timer') }}:</strong>
                                {{ __('messages.debate_timer_desc') }}</p>
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_prep_time')
                                    }}:</strong> {{ __('messages.debate_prep_time_desc') }}</p>
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_qa_time') }}:</strong>
                                {{ __('messages.debate_qa_time_desc') }}</p>
                            <p><strong class="font-semibold text-gray-800">{{ __('messages.debate_leave_interrupt')
                                    }}:</strong> {{ __('messages.debate_leave_interrupt_desc') }}</p>
                        </div>
                    </div>
                </div>

                {{-- ステップ 4: 講評と履歴 --}}
                <div>
                    <h3 class="text-lg sm:text-xl font-semibold text-primary mb-4 sm:mb-6 flex items-center">
                        <span class="material-icons mr-2">analytics</span> {{ __('messages.step4_critique_history') }}
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4 sm:gap-8">
                        {{-- 講評ステップ1: ディベート終了とAI講評 --}}
                        <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{
                                __('messages.critique_step1_title') }}</h4>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-4">{{
                                __('messages.critique_step1_desc1') }}</p>
                            <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.critique_step1_desc2') }}</p>
                        </div>
                        {{-- 講評ステップ2: 結果の確認 --}}
                        <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200">
                            <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{
                                __('messages.critique_step2_title') }}</h4>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2">{{ __('messages.critique_step2_desc1') }}
                            </p>
                            <ul class="list-disc list-inside text-xs sm:text-sm text-gray-600 space-y-1">
                                <li>{{ __('messages.critique_result_win_loss') }}</li>
                                <li>{{ __('messages.critique_result_point_analysis') }}</li>
                                <li>{{ __('messages.critique_result_reason') }}</li>
                                <li>{{ __('messages.critique_result_feedback') }}</li>
                            </ul>
                            <p class="text-xs sm:text-sm text-gray-600 mt-2">{{ __('messages.critique_step2_desc2') }}
                            </p>
                        </div>
                        {{-- 講評ステップ3: ディベート履歴の確認 --}}
                        <div class="guide-step bg-gray-50 p-4 sm:p-6 rounded-lg border border-gray-200 md:col-span-2">
                            <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{
                                __('messages.critique_step3_title') }}</h4>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2 sm:mb-4">{!!
                                __('messages.critique_step3_desc1', ['url' => route('records.index')]) !!}</p>
                            <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.critique_step3_desc2') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ディベートフォーマットセクション --}}
    <section class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 text-center mb-8 sm:mb-12">{{
                __('messages.debate_formats') }}</h2>
            <div class="bg-white p-4 sm:p-6 rounded-lg shadow-sm border border-gray-200">
                <p class="text-xs sm:text-sm text-gray-600 mb-3 sm:mb-4">{{ __('messages.debate_formats_description') }}
                </p>
                <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{ __('messages.available_formats') }}</h4>
                {{-- config/debate.php からフォーマット名を取得してリスト表示 --}}
                <ul class="list-disc list-inside text-xs sm:text-sm text-gray-600 space-y-1 mb-3 sm:mb-4">
                    @foreach(config('debate.formats') as $name => $format)
                    <li>{{ __('debates.'.$name) }}</li>
                    @endforeach
                </ul>
                <h4 class="text-base sm:text-lg font-medium mb-2 sm:mb-3">{{ __('messages.custom_format') }}</h4>
                <p class="text-xs sm:text-sm text-gray-600">{{ __('messages.custom_format_description') }}</p>
            </div>
        </div>
    </section>

    {{-- よくある質問 (FAQ) セクション --}}
    <section class="py-12 sm:py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 text-center mb-8 sm:mb-12">{{
                __('messages.faq_title') }}</h2>
            <div class="space-y-4 sm:space-y-6">
                {{-- FAQ項目1 --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                        class="flex justify-between items-center w-full px-4 sm:px-6 py-3 sm:py-4 text-base sm:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                        <span>{{ __('messages.faq_guide1_q') }}</span>
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary transition-transform duration-200"
                        :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms
                        class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600 bg-gray-50">
                        {{ __('messages.faq_guide1_a') }}
                    </div>
                </div>
                {{-- FAQ項目2 --}}
                {{-- <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                        class="flex justify-between items-center w-full px-4 sm:px-6 py-3 sm:py-4 text-base sm:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                        <span>{{ __('messages.faq_guide2_q') }}</span>
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary transition-transform duration-200"
                        :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms
                        class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600 bg-gray-50">
                        {{ __('messages.faq_guide2_a') }}
                    </div>
                </div> --}}
                {{-- FAQ項目3 --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                        class="flex justify-between items-center w-full px-4 sm:px-6 py-3 sm:py-4 text-base sm:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                        <span>{{ __('messages.faq_guide3_q') }}</span>
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary transition-transform duration-200"
                        :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms
                        class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600 bg-gray-50">
                        {{ __('messages.faq_guide3_a') }}
                    </div>
                </div>
                {{-- FAQ項目4 --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                        class="flex justify-between items-center w-full px-4 sm:px-6 py-3 sm:py-4 text-base sm:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                        <span>{{ __('messages.faq_guide4_q') }}</span>
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary transition-transform duration-200"
                            :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms
                        class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600 bg-gray-50">
                        {{ __('messages.faq_guide4_a') }}
                    </div>
                </div>
                {{-- FAQ項目5 --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                        class="flex justify-between items-center w-full px-4 sm:px-6 py-3 sm:py-4 text-base sm:text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                        <span>{{ __('messages.faq_guide5_q') }}</span>
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-primary transition-transform duration-200"
                            :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms
                        class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm text-gray-600 bg-gray-50">
                        {{ __('messages.faq_guide5_a') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 困ったときはセクション --}}
    <section class="py-16 bg-gray-50 text-center">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('messages.when_in_trouble') }}</h2>
            <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                {{ __('messages.trouble_description') }}
            </p>
            {{-- サポートリンク --}}
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('terms') }}"
                    class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-gray-50 shadow-sm">
                    <span class="material-icons-outlined mr-2">description</span> {{ __('messages.terms_of_service') }}
                </a>
                <a href="{{ route('privacy') }}"
                    class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-gray-50 shadow-sm">
                    <span class="material-icons-outlined mr-2">shield</span> {{ __('messages.privacy_policy') }}
                </a>
                <a href="{{ route('contact.index') }}"
                    class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary-dark shadow-sm">
                    <span class="material-icons-outlined mr-2">email</span> {{ __('messages.contact_us') }}
                </a>
            </div>
        </div>
    </section>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
