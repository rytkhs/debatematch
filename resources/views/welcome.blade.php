<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DebateMatch</title>
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap">
</head>

<body class="min-h-screen bg-white dark:bg-gray-900 flex flex-col">
    <header class="flex justify-between items-center p-4 bg-white dark:bg-gray-900">
        <div class="flex items-center">
            <h1 class="text-2xl font-montserrat text-[#333333] dark:text-white mr-4">DebateMatch</h1>
            <a href="/service" class="text-sm text-[#333333] dark:text-gray-300">サービス紹介</a>
        </div>
        @if (Route::has('login'))
        @auth
        <a href="{{ url('/dashboard') }}"
            class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white">
            ダッシュボード
        </a>
        @else
        <a href="{{ route('register') }}"
            class="px-4 py-2 border border-[#333333] dark:border-gray-500 text-[#333333] dark:text-gray-300 hover:bg-[#F5F5F5] dark:hover:bg-gray-700 text-base transition-colors duration-300">
            ログイン/新規登録
        </a>
        @endauth
        @endif
    </header>

    <main class="flex-grow">
        <section class="text-center py-16 px-4">
            <h2 class="text-4xl md:text-[36px] font-montserrat text-[#212121] dark:text-white mb-4">ディベート仲間とつながろう</h2>
            <p class="text-lg md:text-[18px] text-[#757575] dark:text-gray-400 font-open-sans mb-8">
                DebateMatchは、AIや他のユーザーとリアルタイムでディベートを楽しめるオンラインプラットフォームです。</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 max-w-2xl mx-auto">
                <a href="/random-match"
                    class="w-full h-[70px] bg-[#2ECC71] hover:bg-[#27AE60] text-white font-montserrat text-[20px] rounded-[10px] transition-colors duration-300 flex items-center justify-center shadow-md dark:shadow-gray-800">
                    <i class="fas fa-random mr-2"></i> ランダムマッチング
                </a>
                <a href="/ai-practice"
                    class="w-full h-[70px] bg-[#3498DB] hover:bg-[#2980B9] text-white font-montserrat text-[20px] rounded-[10px] transition-colors duration-300 flex items-center justify-center shadow-md dark:shadow-gray-800">
                    <i class="fas fa-robot mr-2"></i> AIと練習
                </a>
                            <a href="/create-room"
                class="w-full h-[70px] bg-[#E74C3C] hover:bg-[#C0392B] text-white font-montserrat text-[20px] rounded-[10px] transition-colors duration-300 flex items-center justify-center shadow-md">
                <i class="fas fa-plus-circle mr-2"></i> ルームを作成
            </a>
            <a href="/find-room"
                class="w-full h-[70px] bg-[#9B59B6] hover:bg-[#8E44AD] text-white font-montserrat text-[20px] rounded-[10px] transition-colors duration-300 flex items-center justify-center shadow-md">
                <i class="fas fa-search mr-2"></i> ルームを探す</a>
            </div>
        </section>

        <section class="py-16 px-4">
            <h2 class="text-3xl font-montserrat text-[#212121] dark:text-white text-center mb-8">サービス紹介</h2>
            <div class="flex flex-wrap justify-center max-w-4xl mx-auto">
                <div class="w-full sm:w-1/2 md:w-1/3 p-4 text-center">
                    <i class="fas fa-users text-[48px] text-[#424242] dark:text-gray-300 mb-4"></i>
                    <p class="text-[16px] text-[#424242] dark:text-gray-300 mb-2">多様なユーザーとマッチング</p>
                    <a href="/service-details" class="text-[14px] text-[#00796B] dark:text-teal-400">詳細はこちら</a>
                </div>
                <div class="w-full sm:w-1/2 md:w-1/3 p-4 text-center">
                    <i class="fas fa-robot text-[48px] text-[#424242] dark:text-gray-300 mb-4"></i>
                    <p class="text-[16px] text-[#424242] dark:text-gray-300 mb-2">AIと練習セッション</p>
                    <a href="/service-details" class="text-[14px] text-[#00796B] dark:text-teal-400">詳細はこちら</a>
                </div>
            </div>
        </section>

        <section class="py-16 px-4 bg-gray-100 dark:bg-gray-700">
            <h2 class="text-3xl font-montserrat text-[#212121] dark:text-white text-center mb-8">DebateMatchの特徴</h2>
            <ul class="list-disc list-inside max-w-2xl mx-auto text-[#333333] dark:text-gray-300 text-[16px]">
                <li class="mb-2">多様なトピックに対応</li>
                <li class="mb-2">レベル別マッチング機能</li>
                <li class="mb-2">ディベートスキルの向上をサポート</li>
            </ul>
        </section>

        <section class="py-16 px-4">
            <h2 class="text-3xl font-montserrat text-[#212121] dark:text-white text-center mb-8">よくある質問</h2>
            <div class="max-w-2xl mx-auto">
                <div class="mb-4">
                    <h3 class="text-[16px] text-[#00796B] dark:text-teal-400 cursor-pointer">DebateMatchはどのように利用できますか？</h3>
                    <p class="text-[14px] text-[#424242] dark:text-gray-400 mt-2">
                        DebateMatchは無料で登録でき、ブラウザから簡単にアクセスできます。登録後、すぐにディベートを始められます。</p>
                </div>
                <div class="mb-4">
                    <h3 class="text-[16px] text-[#00796B] dark:text-teal-400 cursor-pointer">初心者でも参加できますか？</h3>
                    <p class="text-[14px] text-[#424242] dark:text-gray-400 mt-2">はい、初心者の方も歓迎です。レベル別のマッチング機能があり、AIとの練習セッションも利用できます。</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-200 dark:bg-gray-800 py-8 px-4 text-center">
        <p class="text-[12px] text-[#9E9E9E] dark:text-gray-500 mb-2">© 2024 DebateMatch. All rights reserved.</p>
        <a href="/privacy-policy" class="text-[12px] text-[#00796B] dark:text-teal-400 mr-4">プライバシーポリシー</a>
        <span class="text-[12px] text-[#333333] dark:text-gray-300">お問い合わせ: contact@debatematch.com</span>
    </footer>
</body>

</html>
