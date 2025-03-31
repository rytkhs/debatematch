<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>
    <div class="bg-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- ヘッダーセクション -->
        <div class="text-center mb-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">DebateMatchの使い方</h1>
        <p class="text-lg text-gray-600 max-w-3xl mx-auto">
        オンラインでディベートを楽しみ、論理的思考力と表現力を高めましょう。このガイドではDebateMatchの基本的な使い方を説明します。
        </p>
        </div>

        <!-- 目次 -->
        <div class="bg-gray-50 p-6 rounded-lg shadow-sm mb-12">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">目次</h2>
        <ul class="space-y-2">
        <li>
        <a href="#about" class="text-primary hover:text-primary-dark transition-colors">
        DebateMatchとは
        </a>
        </li>
        <li>
        <a href="#getting-started" class="text-primary hover:text-primary-dark transition-colors">
        はじめ方
        </a>
        </li>
        <li>
        <a href="#creating-joining-rooms" class="text-primary hover:text-primary-dark transition-colors">
        ルームの作成と参加
        </a>
        </li>
        <li>
        <a href="#debate-flow" class="text-primary hover:text-primary-dark transition-colors">
        ディベートの流れ
        </a>
        </li>
        <li>
        <a href="#evaluation" class="text-primary hover:text-primary-dark transition-colors">
        AI評価とフィードバック
        </a>
        </li>
        <li>
        <a href="#faq" class="text-primary hover:text-primary-dark transition-colors">
        よくある質問
        </a>
        </li>
        </ul>
        </div>

        <!-- DebateMatchとは -->
        <section id="about" class="mb-16">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">DebateMatchとは</h2>
          <div class="prose max-w-none">
            <p class="mb-4">DebateMatchは、オンラインでディベートを行うためのプラットフォームです。ユーザー同士が1対1で肯定側・否定側に分かれて議論を行い、論理的思考力や表現力を向上させることができます。</p>

            <div class="bg-primary-light p-4 rounded-lg mb-6">
              <h3 class="font-semibold text-lg mb-2">DebateMatchの特徴</h3>
              <ul class="list-disc list-inside space-y-2">
                <li>いつでもどこでもオンラインでディベートが可能</li>
                <li>1対1の対戦形式で集中した議論ができる</li>
                <li>時間管理機能により効率的なディベートが実現</li>
                <li>AIによる公平な評価とフィードバック</li>
                <li>ディベート履歴の保存と振り返り</li>
              </ul>
            </div>

            <p>初心者から経験者まで、様々なレベルのユーザーが利用でき、ディベートスキルを段階的に高めていくことができます。</p>
          </div>
        </section>

        <!-- はじめ方 -->
        <section id="getting-started" class="mb-16">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">はじめ方</h2>
          <div class="prose max-w-none">
            <ol class="list-decimal list-inside space-y-6">
              <li class="mb-4">
                <strong>アカウント登録</strong>
                <p class="mt-2">DebateMatchを利用するには、まずアカウント登録が必要です。トップページの「新規登録」ボタンから登録画面に進み、必要情報を入力してアカウントを作成してください。</p>
              </li>

              <li class="mb-4">
                <strong>ログイン</strong>
                <p class="mt-2">登録したメールアドレスとパスワードでログインします。ログイン後、ダッシュボードにアクセスできます。</p>
              </li>

              <li class="mb-4">
                <strong>プロフィール設定</strong>
                <p class="mt-2">右上のユーザーメニューから「プロフィール」を選択し、プロフィール情報を設定しましょう。ディベート時に相手に表示される名前などを編集できます。</p>
              </li>
            </ol>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 my-6">
              <p class="font-medium">注意事項</p>
              <p class="text-sm">DebateMatchでは、相互尊重を基本としています。相手を尊重し、建設的な議論を心がけましょう。</p>
            </div>
          </div>
        </section>

        <!-- ルームの作成と参加 -->
        <section id="creating-joining-rooms" class="mb-16">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">ルームの作成と参加</h2>
          <div class="prose max-w-none">
            <h3 class="text-xl font-semibold mb-4">ルームの作成</h3>
            <ol class="list-decimal list-inside space-y-3 mb-6">
              <li>ダッシュボードまたはヘッダーメニューの「ルームを作成」をクリック</li>
              <li>ルーム名、議題、ディベートフォーマットを入力・選択</li>
              <li>「作成」ボタンをクリックしてルームを作成</li>
              <li>作成されたルームは「募集中」状態になり、他のユーザーが参加するのを待ちます</li>
            </ol>

            <div class="bg-primary-light p-4 rounded-lg mb-6">
              <h4 class="font-semibold mb-2">ルーム作成時のポイント</h4>
              <ul class="list-disc list-inside space-y-2">
                <li>議題は明確かつ議論しやすいテーマを選びましょう</li>
                <li>フォーマットは目的や経験に合わせて選択してください</li>
                <li>ルーム名は内容がわかりやすいものにすると参加者が集まりやすくなります</li>
              </ul>
            </div>

            <h3 class="text-xl font-semibold mb-4">ルームへの参加</h3>
            <ol class="list-decimal list-inside space-y-3 mb-6">
              <li>「ルームを探す」から参加可能なルーム一覧を表示</li>
              <li>興味のあるルームを選択して詳細を確認</li>
              <li>「肯定側で参加」または「否定側で参加」ボタンをクリック</li>
              <li>両方の立場に参加者が揃うと、ルーム作成者がディベートを開始できるようになります</li>
            </ol>

            <div class="flex justify-center">
              <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 w-full max-w-2xl">
                <h4 class="font-semibold text-center mb-4">ルームステータスについて</h4>
                <table class="min-w-full border-collapse">
                  <thead>
                    <tr class="bg-gray-100">
                      <th class="border px-4 py-2 text-left">ステータス</th>
                      <th class="border px-4 py-2 text-left">説明</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="border px-4 py-2"><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">募集中</span></td>
                      <td class="border px-4 py-2">参加者を募集している状態</td>
                    </tr>
                    <tr>
                      <td class="border px-4 py-2"><span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">準備完了</span></td>
                      <td class="border px-4 py-2">参加者が揃い、ディベート開始が可能</td>
                    </tr>
                    <tr>
                      <td class="border px-4 py-2"><span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">ディベート中</span></td>
                      <td class="border px-4 py-2">現在ディベートが進行中</td>
                    </tr>
                    <tr>
                      <td class="border px-4 py-2"><span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">終了</span></td>
                      <td class="border px-4 py-2">ディベートが終了した状態</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- ディベートの流れ -->
        <section id="debate-flow" class="mb-16">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">ディベートの流れ</h2>
          <div class="prose max-w-none">
            <p class="mb-4">DebateMatchでは、一般的なディベート大会のルールに準拠した進行を採用しています。各ターンには制限時間があり、時間が終了すると自動的に次のターンに進みます。</p>

            <h3 class="text-xl font-semibold mb-4">基本的な進行</h3>
            <div class="bg-gray-50 p-6 rounded-lg mb-8">
              <ol class="list-decimal list-inside space-y-4">
                <li>
                  <strong class="text-green-700">肯定側立論</strong>: 肯定側が議題に対する主張と根拠を提示
                </li>
                <li>
                  <strong class="text-red-700">否定側立論</strong>: 否定側が議題に対する反論と根拠を提示
                </li>
                <li>
                  <strong class="text-green-700">肯定側質疑</strong>: 肯定側が否定側に質問し、否定側が回答
                </li>
                <li>
                  <strong class="text-red-700">否定側質疑</strong>: 否定側が肯定側に質問し、肯定側が回答
                </li>
                <li>
                  <strong class="text-green-700">肯定側第一反駁</strong>: 肯定側が否定側の主張に反論
                </li>
                <li>
                  <strong class="text-red-700">否定側第一反駁</strong>: 否定側が肯定側の主張に反論
                </li>
                <li>
                  <strong class="text-green-700">肯定側第二反駁</strong>: 肯定側が否定側の反論に再反論
                </li>
                <li>
                  <strong class="text-red-700">否定側第二反駁</strong>: 否定側が肯定側の反論に再反論
                </li>
                <li>
                  <strong class="text-green-700">肯定側最終弁論</strong>: 肯定側が議論をまとめ、最終的な主張を行う
                </li>
                <li>
                  <strong class="text-red-700">否定側最終弁論</strong>: 否定側が議論をまとめ、最終的な主張を行う
                </li>
              </ol>
            </div>

            <h3 class="text-xl font-semibold mb-4">ディベート画面の見方</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div class="bg-primary-light p-4 rounded-lg">
                <h4 class="font-semibold mb-2">左側パネル</h4>
                <ul class="list-disc list-inside space-y-2">
                  <li>参加者情報 (肯定側・否定側)</li>
                  <li>現在のターン表示</li>
                  <li>残り時間カウントダウン</li>
                  <li>進行状況タイムライン</li>
                </ul>
              </div>

              <div class="bg-primary-light p-4 rounded-lg">
                <h4 class="font-semibold mb-2">中央メインエリア</h4>
                <ul class="list-disc list-inside space-y-2">
                  <li>メッセージ履歴</li>
                  <li>メッセージ入力欄</li>
                  <li>ターン別メッセージフィルター</li>
                </ul>
              </div>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 my-6">
              <p class="font-medium">発言のタイミング</p>
              <p class="text-sm">各ターンでは、そのターンに割り当てられた側のみが発言できます。ただし、質疑ターンでは質問と回答のやりとりが可能です。自分の発言ターンになると、メッセージ入力欄が有効になります。</p>
            </div>

            <h3 class="text-xl font-semibold mb-4">時間管理</h3>
            <p>各ターンには制限時間が設けられており、画面上部にカウントダウンタイマーが表示されます。時間が終了すると自動的に次のターンに進みます。効果的な時間配分を心がけましょう。</p>
          </div>
        </section>

        <!-- AI評価とフィードバック -->
        <section id="evaluation" class="mb-16">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">AI評価とフィードバック</h2>
          <div class="prose max-w-none">
            <p class="mb-4">ディベート終了後、AIがディベート内容を分析し、評価とフィードバックを提供します。これにより客観的な視点からディベートのスキルを改善することができます。</p>

            <h3 class="text-xl font-semibold mb-4">AI評価の項目</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold mb-2 text-primary">勝敗判定</h4>
                <p class="text-sm">議論の論理性、証拠の質、反論の適切さなどを総合的に評価し、勝敗を判定します。</p>
              </div>

              <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold mb-2 text-primary">分析レポート</h4>
                <p class="text-sm">議論全体の流れ、主要な論点、効果的だった主張などを分析します。</p>
              </div>

              <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold mb-2 text-primary">肯定側へのフィードバック</h4>
                <p class="text-sm">肯定側の主張の強み・弱み、改善点などを指摘します。</p>
              </div>

              <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold mb-2 text-primary">否定側へのフィードバック</h4>
                <p class="text-sm">否定側の主張の強み・弱み、改善点などを指摘します。</p>
              </div>
            </div>

            <h3 class="text-xl font-semibold mb-4">フィードバックの活用法</h3>
            <ul class="list-disc list-inside space-y-3">
              <li>AIからのフィードバックを参考に、自分の議論スタイルの強みと弱みを把握する</li>
              <li>指摘された改善点を次回のディベートで意識して実践する</li>
              <li>過去のディベート結果を振り返り、成長の過程を確認する</li>
              <li>特定の議論パターンや反論テクニックを学び、レパートリーを増やす</li>
            </ul>

            <div class="bg-primary-light p-4 rounded-lg my-6">
              <p class="font-medium">アドバイス</p>
              <p class="text-sm">AIのフィードバックは学習の指針として活用しましょう。勝敗よりも、議論のプロセスと自身の成長に焦点を当てることが重要です。</p>
            </div>
          </div>
        </section>

        <!-- よくある質問 -->
        <section id="faq" class="mb-16">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b pb-2">よくある質問</h2>
          <div class="space-y-6">
            <div class="bg-gray-50 p-4 rounded-lg">
              <h3 class="font-semibold text-lg mb-2">Q: ディベート中に接続が切れた場合はどうなりますか？</h3>
              <p>A: 一時的な接続の問題であれば再接続が可能です。長時間の切断が続くと、システムが検知してディベートが強制終了される場合があります。安定したネットワーク環境でのご利用をお勧めします。</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h3 class="font-semibold text-lg mb-2">Q: ディベートの議題はどのように選べばよいですか？</h3>
              <p>A: 賛否が分かれる社会的なテーマ、倫理的な問題、または専門分野の論点などが適しています。初心者の場合は、比較的シンプルで情報が豊富なテーマから始めることをおすすめします。</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h3 class="font-semibold text-lg mb-2">Q: ディベート経験がなくても参加できますか？</h3>
              <p>A: はい、初心者も歓迎します。ガイドに従って基本的なルールを理解すれば、誰でも参加できます。実践を通じて徐々にスキルを向上させることができます。</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h3 class="font-semibold text-lg mb-2">Q: ディベート中に相手が失礼なメッセージを送ってきた場合はどうすればよいですか？</h3>
              <p>A: DebateMatchでは相互尊重を重視しています。不適切な行為があった場合は、ディベート終了後に報告機能を使用してください。モデレーターが対応します。</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
              <h3 class="font-semibold text-lg mb-2">Q: 過去のディベート履歴はどこで確認できますか？</h3>
              <p>A: プロフィールページから「ディベート履歴」を選択すると、過去に参加したディベートの一覧が表示されます。各ディベートをクリックすると、詳細な記録やAI評価結果を確認できます。</p>
            </div>
          </div>
        </section>

        <!-- サポート情報 -->
        <section class="mb-8">
          <div class="bg-gray-50 p-6 rounded-lg">
            <h2 class="text-lg font-semibold mb-3">さらなるサポートが必要ですか？</h2>
            <p class="mb-4">ご質問やフィードバックがございましたら、お気軽にお問い合わせください。</p>
            <a href="mailto:info@debatematch.com" class="text-primary hover:text-primary-dark transition-colors flex items-center">
              <span class="material-icons mr-2 text-sm">email</span> info@debatematch.com
            </a>
          </div>
        </section>

        </div>
    </div>
    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
