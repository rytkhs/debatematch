<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    {{-- ヒーローセクション --}}
    <div class="relative bg-gradient-to-br from-blue-600 to-purple-700 text-white overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="relative container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
            <div class="text-center">
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-6 animate-fade-in">
                    {{ __('guide.guide') }}
                </h1>
                <p class="text-lg sm:text-xl max-w-3xl mx-auto mb-8 opacity-90">
                    {{ __('guide.page_description') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('rooms.create') }}"
                       class="inline-flex items-center justify-center px-8 py-3 bg-white text-blue-600 font-semibold rounded-full hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                        <span class="material-icons mr-2">add_circle</span>
                        {{ __('rooms.create_room') }}
                    </a>
                    <a href="{{ route('ai.debate.create') }}"
                       class="inline-flex items-center justify-center px-8 py-3 bg-purple-500 text-white font-semibold rounded-full hover:bg-purple-600 transition-all transform hover:scale-105 shadow-lg">
                        <span class="material-icons mr-2">smart_toy</span>
                        {{ __('ai_debate.start_ai_debate') }}
                    </a>
                </div>
            </div>
        </div>
        {{-- 波形の装飾 --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0V120Z" fill="white"/>
            </svg>
        </div>
    </div>

    {{-- クイックスタートセクション --}}
    <section class="py-12 sm:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                    <span class="text-gradient bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        {{ __('guide.quick_start') }}
                    </span>
                </h2>
                <p class="text-gray-600">{{ __('guide.how_to_use') }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6 sm:gap-8">
                {{-- ステップ1 --}}
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                    <div class="relative bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h3 class="text-lg font-semibold">{{ __('guide.step_1') }}</h3>
                        </div>
                        <p class="text-gray-600 text-sm">{{ __('guide.step_1_desc') }}</p>
                    </div>
                </div>

                {{-- ステップ2 --}}
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                    <div class="relative bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-purple-600 font-bold">2</span>
                            </div>
                            <h3 class="text-lg font-semibold">{{ __('guide.step_2') }}</h3>
                        </div>
                        <p class="text-gray-600 text-sm">{{ __('guide.step_2_desc') }}</p>
                    </div>
                </div>

                {{-- ステップ3 --}}
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-pink-600 to-red-600 rounded-lg blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
                    <div class="relative bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-pink-600 font-bold">3</span>
                            </div>
                            <h3 class="text-lg font-semibold">{{ __('guide.step_3') }}</h3>
                        </div>
                        <p class="text-gray-600 text-sm">{{ __('guide.step_3_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 主な機能セクション（カード型デザイン） --}}
    <section class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ __('guide.key_features') }}</h2>
                <p class="text-gray-600">{{ __('welcome.features_title') }}</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- 機能カード --}}
                @php
                $features = [
                    [
                        'icon' => 'meeting_room',
                        'color' => 'blue',
                        'title' => __('guide.real_time_debate'),
                        'description' => __('guide.real_time_debate_desc')
                    ],
                    [
                        'icon' => 'chat',
                        'color' => 'green',
                        'title' => __('welcome.realtime_chat'),
                        'description' => __('welcome.realtime_chat_description')
                    ],
                    [
                        'icon' => 'timer',
                        'color' => 'orange',
                        'title' => __('welcome.time_management'),
                        'description' => __('welcome.time_management_description')
                    ],
                    [
                        'icon' => 'psychology',
                        'color' => 'purple',
                        'title' => __('guide.ai_evaluation'),
                        'description' => __('guide.ai_evaluation_desc')
                    ]
                ];
                @endphp

                @foreach($features as $feature)
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="p-6">
                        <div class="w-14 h-14 bg-{{ $feature['color'] }}-100 rounded-xl flex items-center justify-center mb-4">
                            <span class="material-icons text-{{ $feature['color'] }}-600 text-2xl">{{ $feature['icon'] }}</span>
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-gray-900">{{ $feature['title'] }}</h3>
                        <p class="text-sm text-gray-600">{{ $feature['description'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- AI対戦機能（特別セクション） --}}
    <section class="py-16 sm:py-20 bg-gradient-to-br from-blue-50 to-purple-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="md:flex">
                    <div class="md:w-1/2 p-8 sm:p-12">
                        <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-4">
                            <span class="material-icons text-sm mr-1">new_releases</span>
                            NEW FEATURE
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ __('guide.ai_debate_practice') }}</h2>
                        <p class="text-gray-600 mb-6">
                            {{ __('guide.ai_debate_practice_desc') }}
                        </p>

                        <div class="space-y-4 mb-8">
                            @php
                            $aiFeatures = [
                                __('guide.ai_features_24h'),
                                __('guide.ai_features_format'),
                                __('guide.ai_features_logical'),
                                __('guide.ai_features_evaluation'),
                                __('guide.ai_features_multilingual')
                            ];
                            @endphp

                            @foreach($aiFeatures as $aiFeature)
                            <div class="flex items-center">
                                <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                    <span class="material-icons text-green-600 text-sm">check</span>
                                </div>
                                <span class="text-gray-700">{{ $aiFeature }}</span>
                            </div>
                            @endforeach
                        </div>

                        <a href="{{ route('ai.debate.create') }}"
                           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                            <span class="material-icons mr-2">smart_toy</span>
                            {{ __('guide.ai_debate_start_btn') }}
                        </a>
                    </div>

                    <div class="md:w-1/2 bg-gradient-to-br from-blue-500 to-purple-600 p-8 sm:p-12 flex items-center justify-center">
                        <div class="text-white text-center">
                            <span class="material-icons text-8xl mb-4 opacity-20">smart_toy</span>
                            <p class="text-xl font-semibold opacity-90">{{ __('guide.ai_practice_partner') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ディベートの流れ（タイムライン形式） --}}
    <section class="py-12 sm:py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ __('guide.debate_flow') }}</h2>
                <p class="text-gray-600">{{ __('guide.debate_flow_subtitle') }}</p>
            </div>

            <div class="relative">
                {{-- タイムライン --}}
                <div class="absolute left-8 md:left-1/2 transform md:-translate-x-1/2 top-0 bottom-0 w-0.5 bg-gray-300"></div>

                {{-- ステップ --}}
                @php
                $steps = [
                    [
                        'icon' => 'how_to_reg',
                        'title' => __('guide.step1_preparation'),
                        'items' => [
                            [
                                'title' => __('guide.prep_step1_title'),
                                'desc' => __('guide.prep_step1_desc')
                            ],
                            [
                                'title' => __('guide.prep_step2_title'),
                                'desc' => __('guide.room_selection_desc')
                            ]
                        ]
                    ],
                    [
                        'icon' => 'groups',
                        'title' => __('guide.step2_matching'),
                        'items' => [
                            [
                                'title' => __('guide.match_step1_title'),
                                'desc' => __('guide.match_step1_desc1')
                            ],
                            [
                                'title' => __('guide.match_step2_title'),
                                'desc' => __('guide.match_step2_desc1')
                            ]
                        ]
                    ],
                    [
                        'icon' => 'gavel',
                        'title' => __('guide.step3_debate'),
                        'items' => [
                            [
                                'title' => __('guide.debate_timeline_steps'),
                                'desc' => __('guide.debate_timeline_description')
                            ],
                            [
                                'title' => __('guide.qa_steps'),
                                'desc' => __('guide.qa_desc')
                            ]
                        ]
                    ],
                    [
                        'icon' => 'analytics',
                        'title' => __('guide.step4_critique_history'),
                        'items' => [
                            [
                                'title' => __('guide.critique_step1_title'),
                                'desc' => __('guide.critique_step1_desc1')
                            ],
                            [
                                'title' => __('guide.critique_step2_title'),
                                'desc' => __('guide.critique_step2_desc')
                            ]
                        ]
                    ]
                ];
                @endphp

                @foreach($steps as $index => $step)
                <div class="relative flex items-center mb-12 {{ $index % 2 == 0 ? 'md:flex-row' : 'md:flex-row-reverse' }}">
                    {{-- アイコン --}}
                    <div class="absolute left-8 md:left-1/2 transform md:-translate-x-1/2 w-16 h-16 bg-white border-4 border-blue-500 rounded-full flex items-center justify-center z-10">
                        <span class="material-icons text-blue-500 text-2xl">{{ $step['icon'] }}</span>
                    </div>

                    {{-- コンテンツ --}}
                    <div class="ml-28 md:ml-0 md:w-1/2 {{ $index % 2 == 0 ? 'md:pr-12 md:text-right' : 'md:pl-12' }}">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">{{ $step['title'] }}</h3>
                        <div class="space-y-3">
                            @foreach($step['items'] as $item)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-800 mb-1">{{ $item['title'] }}</h4>
                                <p class="text-sm text-gray-600">{{ $item['desc'] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ディベートフォーマット（リスト表示） --}}
    <section class="py-12 sm:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ __('guide.debate_formats') }}</h2>
                <p class="text-gray-600">{{ __('guide.debate_formats_description') }}</p>
            </div>

            <!-- 利用可能なフォーマット一覧 -->
            <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                <h3 class="text-xl font-semibold text-gray-900 mb-6 flex items-center">
                    <span class="material-icons text-blue-600 mr-2">list</span>
                    {{ __('guide.available_formats') }}
                </h3>

                <div class="space-y-4">
                    @foreach(config('debate.formats') as $name => $format)
                        @if($name !== 'format_name_free')
                        <div class="flex items-start p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2 mr-4"></div>
                            <div class="flex-grow">
                                <h4 class="font-medium text-md text-gray-900">{{ __('debates.'.$name) }}</h4>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- 特別なフォーマット -->
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                {{-- カスタムフォーマット --}}
                <div class="bg-gradient-to-br from-orange-50 to-yellow-50 rounded-lg shadow-md p-6 border border-orange-200">
                    <div class="flex items-center mb-3">
                        <span class="material-icons text-orange-500 mr-2">tune</span>
                        <h3 class="font-semibold text-lg">{{ __('debates_format.custom_format') }}</h3>
                    </div>
                    <p class="text-sm text-gray-700 mb-3">{{ __('debates_format.custom_format_description') }}</p>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li class="flex items-start">
                            <span class="text-orange-500 mr-1">•</span>
                            <span>{{ __('guide.custom_format_setting1') }}</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-orange-500 mr-1">•</span>
                            <span>{{ __('guide.custom_format_setting2') }}</span>
                        </li>
                    </ul>
                </div>

                {{-- フリーフォーマット --}}
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg shadow-md p-6 border border-purple-200">
                    <div class="flex items-center mb-3">
                        <span class="material-icons text-purple-500 mr-2">all_inclusive</span>
                        <h3 class="font-semibold text-lg">{{ __('debates.format_name_free') }}フォーマット</h3>
                    </div>
                    <p class="text-sm text-gray-700 mb-3">{{ __('debates_format.free_format_argument_style') }}</p>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li class="flex items-start">
                            <span class="text-purple-500 mr-1">•</span>
                            <span>{{ __('debates_format.free_format_flexible_time') }}</span>
                        </li>
                        <li class="flex items-start">
                            <span class="text-purple-500 mr-1">•</span>
                            <span>{{ __('guide.beginner_ideal_text') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- フォーマット追加リクエストに関する案内 -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <span class="material-icons text-blue-600 mr-3">add_circle_outline</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg text-blue-900 mb-2">{{ __('guide.format_addition_title') }}</h3>
                        <p class="text-sm text-blue-800 mb-3">
                            {{ __('guide.format_addition_desc') }}
                        </p>
                        <ul class="text-sm text-blue-700 space-y-1 mb-4">
                            @foreach(__('guide.format_request_types') as $type)
                            <li class="flex items-start">
                                <span class="text-blue-500 mr-2">•</span>
                                <span>{{ $type }}</span>
                            </li>
                            @endforeach
                        </ul>
                        <p class="text-sm text-blue-800">
                            <a href="{{ route('contact.index') }}" class="inline-flex items-center font-medium text-blue-600 hover:text-blue-800 transition-colors">
                                <span class="material-icons text-sm mr-1">contact_support</span>
                                {{ __('guide.contact_form_link') }}
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- フリーフォーマット詳細セクション --}}
    <section class="py-12 sm:py-14 bg-gradient-to-r from-purple-50 to-pink-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-3">
                        <span class="material-icons text-purple-600 text-3xl">all_inclusive</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-3">
                        {{ __('debates.format_name_free') }}{{ __('debates_format.free_format_title') }}
                    </h2>
                    <p class="text-gray-600 max-w-2xl mx-auto text-sm">
                        {{ __('debates_format.free_format_description') }}
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    {{-- 早期終了機能 --}}
                    <div class="bg-blue-50 rounded-xl p-5">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="material-icons text-blue-600 text-lg">timer_off</span>
                            </div>
                            <h3 class="font-semibold">{{ __('debates_format.early_termination_feature') }}</h3>
                        </div>
                        <p class="text-sm text-gray-700 mb-3">{{ __('debates_format.early_termination_feature_desc') }}</p>
                        <ul class="space-y-1.5">
                            <li class="flex items-start">
                                <span class="material-icons text-blue-500 text-sm mr-2 mt-0.5">check_circle</span>
                                <span class="text-sm text-gray-700">{{ __('debates_format.early_termination_feature_list1') }}</span>
                            </li>
                            <li class="flex items-start">
                                <span class="material-icons text-blue-500 text-sm mr-2 mt-0.5">check_circle</span>
                                <span class="text-sm text-gray-700">{{ __('debates_format.early_termination_feature_list2') }}</span>
                            </li>
                        </ul>
                    </div>

                    {{-- 柔軟な設定 --}}
                    <div class="bg-green-50 rounded-xl p-5">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <span class="material-icons text-green-600 text-lg">tune</span>
                            </div>
                            <h3 class="font-semibold">{{ __('debates_format.flexible_settings') }}</h3>
                        </div>
                        <p class="text-sm text-gray-700 mb-3">{{ __('debates_format.flexible_settings_desc') }}</p>
                        <ul class="space-y-1.5">
                            <li class="flex items-start">
                                <span class="material-icons text-green-500 text-sm mr-2 mt-0.5">check_circle</span>
                                <span class="text-sm text-gray-700">{{ __('debates_format.flexible_settings_list1') }}</span>
                            </li>
                            <li class="flex items-start">
                                <span class="material-icons text-green-500 text-sm mr-2 mt-0.5">check_circle</span>
                                <span class="text-sm text-gray-700">{{ __('debates_format.flexible_settings_list2') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- おすすめの使用場面 --}}
                <div class="bg-gray-50 rounded-xl p-5">
                    <h4 class="text-center font-semibold mb-5">{{ __('debates_format.recommended_situations') }}</h4>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-md">
                                <span class="material-icons text-gray-600 text-xl">school</span>
                            </div>
                            <p class="text-sm font-medium text-gray-700">{{ __('debates_format.beginner_practice') }}</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-md">
                                <span class="material-icons text-gray-600 text-xl">chat</span>
                            </div>
                            <p class="text-sm font-medium text-gray-700">{{ __('debates_format.casual_discussion') }}</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-2 shadow-md">
                                <span class="material-icons text-gray-600 text-xl">speed</span>
                            </div>
                            <p class="text-sm font-medium text-gray-700">{{ __('debates_format.short_time_exchange') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ（アコーディオン改善） --}}
    <section class="py-12 sm:py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ __('welcome.faq_title') }}</h2>
                <p class="text-gray-600">{{ __('guide.faq_answer_text') }}</p>
            </div>

            <div class="space-y-4">
                @php
                $faqs = [
                    [
                        'q' => __('guide.faq_guide1_q'),
                        'a' => __('guide.faq_guide1_a')
                    ],
                    [
                        'q' => __('guide.faq_guide3_q'),
                        'a' => __('guide.faq_guide3_a')
                    ],
                    [
                        'q' => __('guide.faq_guide4_q'),
                        'a' => __('guide.faq_guide4_a')
                    ],
                    [
                        'q' => __('guide.faq_guide5_q'),
                        'a' => __('guide.faq_guide5_a')
                    ],
                    [
                        'q' => __('guide.faq_guide6_q'),
                        'a' => __('guide.faq_guide6_a')
                    ],
                    [
                        'q' => __('guide.ai_debate_faq_q'),
                        'a' => __('guide.ai_debate_faq_a')
                    ],
                    [
                        'q' => __('guide.debate_duration_faq_q'),
                        'a' => __('guide.debate_duration_faq_a')
                    ]
                ];
                @endphp

                @foreach($faqs as $index => $faq)
                <div x-data="{ open: false }" class="bg-gray-50 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                        class="flex justify-between items-center w-full px-6 py-4 text-left hover:bg-gray-100 transition-colors">
                        <div class="flex items-center">
                            <span class="text-blue-500 font-semibold mr-3">Q{{ $index + 1 }}.</span>
                            <span class="font-medium text-gray-900">{{ $faq['q'] }}</span>
                        </div>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                            :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        class="px-6 py-4 text-gray-600 bg-white border-t border-gray-200">
                        {{ $faq['a'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- お問い合わせセクション --}}
    <section class="py-14 sm:py-18 bg-gradient-to-r from-blue-600 to-purple-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold mb-6">{{ __('guide.contact_section_title') }}</h2>
            <p class="text-xl mb-10 opacity-90 max-w-2xl mx-auto">
                {{ __('guide.contact_section_desc') }}
            </p>

            <div class="flex justify-center">
                <a href="{{ route('contact.index') }}"
                    class="inline-flex items-center justify-center px-8 py-3 bg-white text-blue-600 font-semibold rounded-full hover:bg-gray-100 transition-all transform hover:scale-105 shadow-lg">
                    <span class="material-icons-outlined mr-2">email</span>
                    {{ __('navigation.contact_us') }}
                </a>
            </div>
        </div>
    </section>

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
    </style>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
