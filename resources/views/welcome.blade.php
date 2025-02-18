<x-app-layout>
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
        <!-- ヘッダー -->
        <x-slot name="header">
            <x-header></x-header>
        </x-slot>

        <!-- メインコンテンツ -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- ヒーローセクション -->
            <div class="py-16 md:py-24 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                    オンラインで
                    <span class="text-primary">ディベート</span>
                    を始めよう
                </h2>
                <p class="text-lg text-gray-600 mb-12 max-w-2xl mx-auto">
                    DebateMatchは、誰でも簡単に参加できるオンラインディベートプラットフォームです。
                    意見を交わし、新しい視点を見つけましょう。
                </p>

                <!-- メインアクション -->
                <div class="flex flex-col md:flex-row justify-center gap-4 mb-16">
                    <a href="{{ route('rooms.create') }}" class="hero-button bg-primary text-white hover:bg-primary-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        ルームを作成
                    </a>
                    <a href="{{route('rooms.index')}}" class="hero-button bg-white text-primary border-2 border-primary hover:bg-primary hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        ルームを探す
                    </a>
                </div>

                <!-- 特徴セクション -->
                <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto mb-24">
                    <div class="feature-card">
                        <div class="feature-icon bg-primary-light text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">リアルタイム対戦</h3>
                        <p class="text-gray-600">
                            オンラインで即座に対戦相手とマッチング。
                            時間や場所を問わずディベートを楽しめます。
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon bg-primary-light text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">タイムマネジメント</h3>
                        <p class="text-gray-600">
                            発言時間の管理や進行状況の可視化で、
                            効率的なディベートを実現します。
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon bg-primary-light text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">AIフィードバック</h3>
                        <p class="text-gray-600">
                            AIによる論理性の分析と改善提案で、
                            ディベートスキルの向上をサポート。
                        </p>
                    </div>
                </div>

                <!-- 使い方セクション -->
                <div class="mb-24">
                    <h2 class="text-3xl font-bold mb-12">使い方</h2>
                    <div class="grid md:grid-cols-4 gap-8 max-w-5xl mx-auto">
                        <div class="how-to-card">
                            <div class="how-to-number">1</div>
                            <h3 class="text-lg font-semibold mb-2">ルームを選択</h3>
                            <p class="text-gray-600">
                                既存のルームに参加するか、新しいルームを作成します。
                            </p>
                        </div>
                        <div class="how-to-card">
                            <div class="how-to-number">2</div>
                            <h3 class="text-lg font-semibold mb-2">立場を決定</h3>
                            <p class="text-gray-600">
                                テーマに対して肯定側か否定側を選択します。
                            </p>
                        </div>
                        <div class="how-to-card">
                            <div class="how-to-number">3</div>
                            <h3 class="text-lg font-semibold mb-2">ディベート開始</h3>
                            <p class="text-gray-600">
                                対戦相手が揃ったら、ディベートを開始します。
                            </p>
                        </div>
                        <div class="how-to-card">
                            <div class="how-to-number">4</div>
                            <h3 class="text-lg font-semibold mb-2">振り返り</h3>
                            <p class="text-gray-600">
                                AIフィードバックで自身の主張を振り返ります。
                            </p>
                        </div>
                    </div>
                </div>

                <!-- よくある質問セクション -->
                <div class="mb-24">
                    <h2 class="text-3xl font-bold mb-12">よくある質問</h2>
                    <div class="max-w-3xl mx-auto">
                        <div class="faq-item">
                            <h3 class="text-xl font-semibold mb-2">ディベート経験がなくても参加できますか？</h3>
                            <p class="text-gray-600 mb-6">
                                はい、初心者の方でも安心して参加いただけます。チュートリアルやAIのサポートで、基本的なルールから丁寧に学べます。
                            </p>
                        </div>
                        <div class="faq-item">
                            <h3 class="text-xl font-semibold mb-2">1回のディベートにどのくらいの時間がかかりますか？</h3>
                            <p class="text-gray-600 mb-6">
                                基本的な1セッションは30分程度です。準備時間、ディベート時間、振り返りの時間が含まれます。
                            </p>
                        </div>
                        <div class="faq-item">
                            <h3 class="text-xl font-semibold mb-2">どんなテーマでディベートができますか？</h3>
                            <p class="text-gray-600 mb-6">
                                社会問題、テクノロジー、教育など、様々なジャンルのテーマをご用意しています。また、カスタムテーマを作成することも可能です。
                            </p>
                        </div>
                        <div class="faq-item">
                            <h3 class="text-xl font-semibold mb-2">AIフィードバックはどのように機能しますか？</h3>
                            <p class="text-gray-600">
                                ディベート中の発言を分析し、論理性、説得力、反論の適切さなどを評価。改善点を具体的にアドバイスします。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- フッター -->
        <x-slot name="footer">
            <x-footer></x-footer>
        </x-slot>
    </div>
</x-app-layout>
