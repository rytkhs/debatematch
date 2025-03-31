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
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-32">
                    <div class="text-center">
                        <div>
                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                                オンラインで
                                <span class="text-primary">ディベート</span>
                                を始めよう
                            </h2>
                            <p class="text-lg text-gray-600 mb-20 max-w-2xl mx-auto">
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
                                    <i class="fa-solid fa-door-open mr-2"></i>
                                    ルームを探す
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
            <div class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold text-gray-900">
                            DebateMatchの特徴
                        </h2>
                    </div>

                    <div class="grid md:grid-cols-3 gap-8">
                        <!-- 特徴カード1 -->
                        <div class="feature-card shadow-md">
                            <div class="w-12 h-12 rounded-full bg-primary-light flex items-center justify-center mb-5">
                                <i class="fa-solid fa-comments text-primary text-xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold mb-3 text-gray-900">リアルタイムチャット</h3>
                            <p class="text-gray-600">
                                場所や時間に縛られることなく、いつでもどこでもディベーターとマッチング。すぐにディベートを始めることができます。
                            </p>
                        </div>

                        <!-- 特徴カード2 -->
                        <div class="feature-card shadow-md">
                            <div class="w-12 h-12 rounded-full bg-primary-light flex items-center justify-center mb-5">
                                <i class="fa-solid fa-clock text-primary text-xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold mb-3 text-gray-900">タイムマネジメント</h3>
                            <p class="text-gray-600">
                                自動化されたタイマーと進行管理により、効率的な討論の場を提供します。
                            </p>
                        </div>

                        <!-- 特徴カード3 -->
                        <div class="feature-card shadow-md">
                            <div class="w-12 h-12 rounded-full bg-primary-light flex items-center justify-center mb-5">
                                <i class="fa-solid fa-brain text-primary text-xl"></i>
                            </div>
                            <h3 class="text-xl font-semibold mb-3 text-gray-900">AIフィードバック</h3>
                            <p class="text-gray-600">
                                ディベート終了後、AIジャッジが議論を分析。勝敗判定と詳細な講評を提供します。具体的な改善点もフィードバックします。
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 使い方セクション -->
            <div class="py-16 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold text-gray-900">
                            使い方
                        </h2>
                    </div>

                    <div class="relative">
                        <!-- 接続線 -->
                        <div class="hidden md:block absolute top-24 left-0 right-0 h-1 bg-primary"></div>

                        <div class="grid md:grid-cols-3 gap-8">
                            <!-- ステップ1 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center mb-5 text-xl font-bold">1</div>
                                <div class="text-center">
                                    <h3 class="text-xl font-semibold mb-3">ルームを選択 / 作成</h3>
                                    <p class="text-gray-600">ルームを探して参加するか、新しいルームを作成します</p>
                                </div>
                            </div>

                            <!-- ステップ2 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center mb-5 text-xl font-bold">2</div>
                                <div class="text-center">
                                    <h3 class="text-xl font-semibold mb-3">ディベート開始</h3>
                                    <p class="text-gray-600">ディベーターが揃ったら、システムの進行に従いディベートを行います</p>
                                </div>
                            </div>

                            <!-- ステップ3 -->
                            <div class="relative flex flex-col items-center">
                                <div class="z-10 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center mb-5 text-xl font-bold">3</div>
                                <div class="text-center">
                                    <h3 class="text-xl font-semibold mb-3">AI講評</h3>
                                    <p class="text-gray-600">AIジャッジからディベートの講評とフィードバックを受け取ります</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQセクション -->
            <div class="py-16 bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-16">
                        <h2 class="text-3xl font-bold text-gray-900">
                            よくある<span class="text-primary">質問</span>
                        </h2>
                    </div>

                    <div class="space-y-6">
                        <!-- FAQ項目 -->
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>ディベート経験がなくても参加できますか？</span>
                                <svg class="w-5 h-5 text-primary" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="px-6 py-4 text-gray-600 bg-gray-50">
                                はい、初心者の方でも安心して参加いただけます。チュートリアルやAIのサポートで、基本的なルールから丁寧に学べます。また、初心者向けのルームもご用意しています。
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>1回のディベートにどのくらいの時間がかかりますか？</span>
                                <svg class="w-5 h-5 text-primary" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="px-6 py-4 text-gray-600 bg-gray-50">
                                基本的な1セッションは30分程度です。準備時間、ディベート時間、振り返りの時間が含まれます。ルーム作成時に時間設定をカスタマイズすることも可能です。
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>どんなテーマでディベートができますか？</span>
                                <svg class="w-5 h-5 text-primary" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="px-6 py-4 text-gray-600 bg-gray-50">
                                社会問題、テクノロジー、教育など、様々なジャンルのテーマをご用意しています。また、カスタムテーマを作成することも可能です。定期的に新しいテーマも追加されます。
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                            <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                                <span>AIフィードバックはどのように機能しますか？</span>
                                <svg class="w-5 h-5 text-primary" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition class="px-6 py-4 text-gray-600 bg-gray-50">
                                ディベート中の発言を分析し、論理性、説得力、反論の適切さなどを評価します。改善点を具体的にアドバイスするほか、ディベート全体の勝敗判定も行います。フィードバック内容は保存でき、自分の成長を確認できます。
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
