<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    {{-- ページヘッダー --}}
    <div class="bg-white pt-16 pb-12">
        <div class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
            {{-- ページタイトル --}}
            <h1 class="text-3xl md:text-4xl font-bold text-primary my-4">DebateMatch 使い方ガイド</h1>
            {{-- ページ概要説明 --}}
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                DebateMatchへようこそ！このガイドでは、サービスの基本的な使い方から応用的な機能までをわかりやすく解説します。
            </p>
        </div>
    </div>

    {{-- 主な機能セクション --}}
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">主な機能</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                {{-- 機能カード: ルーム管理 --}}
                <div class="feature-card bg-white p-6 rounded-lg shadow-sm text-center">
                    <div class="w-16 h-16 rounded-full bg-primary-light flex items-center justify-center mb-5 mx-auto">
                        <span class="material-icons text-primary text-3xl">meeting_room</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-900">ルーム管理</h3>
                    <p class="text-gray-600 text-sm">ディベートルームを自由に作成・検索し、他のユーザーとマッチングできます。</p>
                </div>
                {{-- 機能カード: リアルタイムチャット --}}
                <div class="feature-card bg-white p-6 rounded-lg shadow-sm text-center">
                    <div class="w-16 h-16 rounded-full bg-primary-light flex items-center justify-center mb-5 mx-auto">
                        <span class="material-icons text-primary text-3xl">chat</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-900">リアルタイムチャット</h3>
                    <p class="text-gray-600 text-sm">テキストベースのチャットで、スムーズなディベート進行を実現します。</p>
                </div>
                {{-- 機能カード: 自動進行 & タイマー --}}
                <div class="feature-card bg-white p-6 rounded-lg shadow-sm text-center">
                     <div class="w-16 h-16 rounded-full bg-primary-light flex items-center justify-center mb-5 mx-auto">
                        <span class="material-icons text-primary text-3xl">timer</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-900">自動進行 & タイマー</h3>
                    <p class="text-gray-600 text-sm">設定されたフォーマットに基づき、タイマーと進行が自動で管理されます。</p>
                </div>
                {{-- 機能カード: AIによる講評 --}}
                 <div class="feature-card bg-white p-6 rounded-lg shadow-sm text-center">
                    <div class="w-16 h-16 rounded-full bg-primary-light flex items-center justify-center mb-5 mx-auto">
                        <span class="material-icons text-primary text-3xl">psychology</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-900">AIによる講評</h3>
                    <p class="text-gray-600 text-sm">ディベート終了後、AIが議論を分析し、勝敗判定と詳細なフィードバックを提供します。</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ディベートの流れセクション --}}
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">ディベートの流れ</h2>
            <div class="space-y-12">
                {{-- ステップ 1: 準備 --}}
                <div>
                    <h3 class="text-xl font-semibold text-primary mb-6 flex items-center">
                        <span class="material-icons mr-2">how_to_reg</span> ステップ1：準備
                    </h3>
                    <div class="grid md:grid-cols-2 gap-8">
                        {{-- 準備ステップ1: ユーザー登録・ログイン --}}
                        <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <h4 class="text-lg font-medium mb-3">1. ユーザー登録・ログイン</h4>
                            <p class="text-gray-600 text-sm mb-4">初めての方は<a href="{{ route('register') }}" class="text-primary hover:underline">新規登録</a>からアカウントを作成してください。登録済みの方は<a href="{{ route('login') }}" class="text-primary hover:underline">ログイン</a>してください。</p>
                            <p class="text-gray-600 text-sm">メール認証が必要な場合があります。</p>
                        </div>
                        {{-- 準備ステップ2: ルームを探す or 作成する --}}
                        <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <h4 class="text-lg font-medium mb-3">2. ルームを探す or 作成する</h4>
                            <p class="text-gray-600 text-sm mb-2">
                                <a href="{{ route('rooms.index') }}" class="text-primary hover:underline">ルームを探す</a>ページで参加したいルームを見つけるか、
                                <a href="{{ route('rooms.create') }}" class="text-primary hover:underline">ルーム作成</a>ページで新しいルームを作成します。
                            </p>
                            <p class="text-gray-600 text-sm">ルーム作成時には、論題、ルーム名、備考、使用言語、ディベートフォーマットを選択します。カスタムフォーマットも設定可能です。</p>
                        </div>
                    </div>
                </div>

                {{-- ステップ 2: マッチング --}}
                 <div>
                    <h3 class="text-xl font-semibold text-primary mb-6 flex items-center">
                        <span class="material-icons mr-2">groups</span> ステップ2：マッチング
                    </h3>
                    <div class="grid md:grid-cols-2 gap-8">
                        {{-- マッチングステップ1: ルームに参加する --}}
                        <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200">
                             <h4 class="text-lg font-medium mb-3">3. ルームに参加する</h4>
                            <p class="text-gray-600 text-sm mb-4">参加したいルームを見つけたら、ルーム詳細ページで「肯定側」または「否定側」のどちらで参加するかを選択し、参加ボタンを押します。</p>
                            <p class="text-gray-600 text-sm">既に他のユーザーが参加しているサイドには参加できません。</p>
                        </div>
                        {{-- マッチングステップ2: 待機と開始 --}}
                        <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <h4 class="text-lg font-medium mb-3">4. 待機と開始</h4>
                             <p class="text-gray-600 text-sm mb-2">ルームに参加すると待機画面に移ります。肯定側・否定側の両方のプレイヤーが揃うと、ルーム作成者（ホスト）がディベートを開始できます。</p>
                            <p class="text-gray-600 text-sm">準備ができたら、ホストは「ディベート開始」ボタンを押してください。</p>
                         </div>
                    </div>
                </div>

                {{-- ステップ 3: ディベート --}}
                <div>
                     <h3 class="text-xl font-semibold text-primary mb-6 flex items-center">
                        <span class="material-icons mr-2">gavel</span> ステップ3：ディベート
                    </h3>
                     {{-- ディベート画面の説明 --}}
                    <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200 mb-8">
                        <h4 class="text-lg font-medium mb-3">5. ディベート画面の見方と操作</h4>
                        <div class="space-y-4 text-gray-600 text-sm">
                            <p><strong class="font-semibold text-gray-800">タイムライン:</strong> 現在のパート、残り時間、全体の進行状況が表示されます。</p>
                            <p><strong class="font-semibold text-gray-800">チャットエリア:</strong> ディベートの発言がリアルタイムで表示されます。</p>
                            <p><strong class="font-semibold text-gray-800">メッセージ入力欄:</strong> 自分のパートの時に発言を入力し、送信します。</p>
                            <p><strong class="font-semibold text-gray-800">参加者リスト:</strong> 肯定側・否定側の参加者が表示されます。</p>
                            <p><strong class="font-semibold text-gray-800">タイマー:</strong> 各パートの制限時間がカウントダウンされます。時間切れになると自動的に次のパートへ移行します。</p>
                            <p><strong class="font-semibold text-gray-800">準備時間:</strong> フォーマットによっては準備時間が設けられています。この時間は相手の発言はありません。</p>
                            <p><strong class="font-semibold text-gray-800">質疑応答:</strong> フォーマットによっては質疑応答の時間が設けられています。質問側と応答側に分かれます。</p>
                             <p><strong class="font-semibold text-gray-800">退出/中断:</strong> ディベート中の退出は原則できません。相手の接続が切れた場合など、システムが異常を検知した場合はディベートが中断されることがあります。</p>
                        </div>
                    </div>
                </div>

                {{-- ステップ 4: 講評と履歴 --}}
                <div>
                    <h3 class="text-xl font-semibold text-primary mb-6 flex items-center">
                        <span class="material-icons mr-2">analytics</span> ステップ4：講評と履歴
                    </h3>
                    <div class="grid md:grid-cols-2 gap-8">
                        {{-- 講評テップ1: ディベート終了とAI講評 --}}
                        <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <h4 class="text-lg font-medium mb-3">6. ディベート終了とAI講評</h4>
                             <p class="text-gray-600 text-sm mb-4">最後のパートが終了すると、ディベートは自動的に完了します。その後、AIによる評価がバックグラウンドで開始されます。</p>
                             <p class="text-gray-600 text-sm">評価には数十秒〜数分程度かかる場合があります。評価が完了すると、結果ページへ自動的にリダイレクトされるか、通知が表示されます。</p>
                         </div>
                         {{-- 講評ステップ2: 結果の確認 --}}
                         <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200">
                            <h4 class="text-lg font-medium mb-3">7. 結果の確認</h4>
                            <p class="text-gray-600 text-sm mb-2">結果ページでは、AIによる以下の講評を確認できます。</p>
                            <ul class="list-disc list-inside text-gray-600 text-sm space-y-1">
                                <li>勝敗判定 (肯定側/否定側)</li>
                                <li>論点の分析</li>
                                <li>判定理由</li>
                                <li>各サイドへのフィードバック</li>
                            </ul>
                            <p class="text-gray-600 text-sm mt-2">タブを切り替えることで、ディベート中のチャットログも確認できます。</p>
                        </div>
                         {{-- 講評ステップ3: ディベート履歴の確認 --}}
                         <div class="guide-step bg-gray-50 p-6 rounded-lg border border-gray-200 md:col-span-2">
                             <h4 class="text-lg font-medium mb-3">8. ディベート履歴の確認</h4>
                             <p class="text-gray-600 text-sm mb-4">ナビゲーションメニューの<a href="{{ route('records.index') }}" class="text-primary hover:underline">ディベート履歴</a>から、過去に参加したディベートの結果と内容を確認できます。</p>
                             <p class="text-gray-600 text-sm">フィルターやソート機能を使って、特定のディベートを探すことも可能です。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ディベートフォーマットセクション --}}
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">ディベートフォーマット</h2>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <p class="text-gray-600 mb-4">DebateMatchでは、主要なディベート大会の公式ルールに基づいたフォーマットを複数用意しています。ルーム作成時に、希望のフォーマットを選択してください。</p>
                <h4 class="text-lg font-medium mb-3">現在利用可能なフォーマット</h4>
                {{-- config/debate.php からフォーマット名を取得してリスト表示 --}}
                <ul class="list-disc list-inside text-gray-600 space-y-1 mb-4">
                    @foreach(config('debate.formats') as $name => $format)
                        <li>{{ $name }}</li>
                    @endforeach
                </ul>
                <h4 class="text-lg font-medium mb-3">カスタムフォーマット</h4>
                <p class="text-gray-600">上記の既存フォーマット以外に、自分でパート構成（話者、名称、時間など）を自由に設定できる「カスタム」フォーマットも利用可能です。ルーム作成時に「カスタム」を選択し、詳細を設定してください。</p>
            </div>
        </div>
    </section>

    {{-- よくある質問 (FAQ) セクション --}}
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">よくある質問</h2>
            <div class="space-y-6">
                {{-- FAQ項目1 --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                        <span>ディベート経験がなくても参加できますか？</span>
                        <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms class="px-6 py-4 text-gray-600 bg-gray-50">
                        はい、大歓迎です！ DebateMatchは、ディベート経験がない方でも気軽に参加し、楽しみながら学べるように設計されています。AIによるフィードバック機能も、スキルアップの助けになります。まずは、簡単なルームから参加してみることをお勧めします
                    </div>
                </div>
                {{-- FAQ項目2 --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                   <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                         <span>1回のディベートにどのくらいの時間がかかりますか？</span>
                         <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms class="px-6 py-4 text-gray-600 bg-gray-50">
                        選択するフォーマットによって異なります。例えば、「ディベート甲子園(高校の部)」形式では約1時間程度です。カスタムフォーマットでは自由に時間を設定できます。各パートの時間はディベート画面のタイムラインで確認できます。
                    </div>
                </div>
                {{-- FAQ項目3 --}}
                <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                         <span>どんな論題でディベートできますか？</span>
                         <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                           <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms class="px-6 py-4 text-gray-600 bg-gray-50">
                        ルーム作成者が自由に論題を設定できます。社会問題、科学技術、倫理、政策など、様々なテーマでのディベートが可能です。
                    </div>
                </div>
                {{-- FAQ項目4 --}}
                 <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                    <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                         <span>AIフィードバックはどのように機能しますか？</span>
                        <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                             <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="open" x-transition.duration.300ms class="px-6 py-4 text-gray-600 bg-gray-50">
                         ディベート中の全発言をテキストデータとしてAIに渡し、評価基準に基づいて分析を行います。分析結果から、最終的な勝敗判定、判定理由、各サイドへの具体的な改善点を含むフィードバックを生成します。
                    </div>
                </div>
                 {{-- FAQ項目5 --}}
                 <div x-data="{ open: false }" class="border border-gray-200 rounded-lg overflow-hidden">
                     <button @click="open = !open" class="flex justify-between items-center w-full px-6 py-4 text-lg font-medium text-left text-gray-900 bg-white hover:bg-gray-50 focus:outline-none">
                        <span>途中で接続が切れてしまった場合はどうなりますか？</span>
                        <svg class="w-5 h-5 text-primary transition-transform duration-200" :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                     </button>
                     <div x-show="open" x-transition.duration.300ms class="px-6 py-4 text-gray-600 bg-gray-50">
                        ネットワーク接続が不安定になると、一時的に相手との接続が切断された旨の通知が表示されることがあります。一定時間内に再接続できない場合、ディベートは強制的に中断・終了となる場合があります。安定したネットワーク環境でのご利用を推奨します。
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 困ったときはセクション --}}
    <section class="py-16 bg-gray-50 text-center">
         <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">困ったときは</h2>
            <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                不明な点や問題が発生した場合は、以下のリンクをご確認いただくか、お問い合わせください。
            </p>
             {{-- サポートリンク --}}
             <div class="flex flex-col sm:flex-row justify-center gap-4">
                 <a href="{{ route('terms') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-gray-50 shadow-sm">
                    <span class="material-icons-outlined mr-2">description</span> 利用規約
                </a>
                 <a href="{{ route('privacy') }}" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-white hover:bg-gray-50 shadow-sm">
                    <span class="material-icons-outlined mr-2">shield</span> プライバシーポリシー
                </a>
                <a href="https://docs.google.com/forms/d/e/1FAIpQLSeojaEKvwrH1Ewi49qqN8S6i3HqF9yoeMSCvKpGk58soFLuqA/viewform?usp=dialog" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary-dark shadow-sm">
                    <span class="material-icons-outlined mr-2">email</span> お問い合わせ
                </a>
            </div>
        </div>
    </section>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
