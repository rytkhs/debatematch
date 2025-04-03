<footer class="bg-slate-700 text-white">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- ロゴとコピーライト -->
            <div class="col-span-1 md:col-span-1">
                <div class="flex items-center mb-4">
                    <svg class="h-8 w-8 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4.5V6.5M12 17.5V19.5M4.5 12H6.5M17.5 12H19.5M16.5 16.5L15 15M16.5 7.5L15 9M7.5 16.5L9 15M7.5 7.5L9 9M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="ml-2 text-xl font-bold">DebateMatch</span>
                </div>
                <p class="text-gray-400 text-sm mt-4">
                    © 2025 DebateMatch. All rights reserved.
                </p>
            </div>

            <!-- リンク1 -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-4">DebateMatch</h3>
                <ul class="space-y-2">
                    <li><a href="{{ route('welcome') }}" class="text-gray-400 hover:text-white transition">ホーム</a></li>
                    <li><a href="{{ route('guide') }}" class="text-gray-400 hover:text-white transition">使い方</a></li>
                    <li><a href="{{ route('rooms.index') }}" class="text-gray-400 hover:text-white transition">ルームを探す</a></li>
                    <li><a href="{{ route('rooms.create') }}" class="text-gray-400 hover:text-white transition">ルームを作成</a></li>
                </ul>
            </div>

            <!-- リンク2 -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-4">アカウント</h3>
                <ul class="space-y-2">
                    @auth
                    <li><a href="{{ route('profile.edit') }}" class="text-gray-400 hover:text-white transition">プロフィール</a></li>
                    <li><a href="{{ route('records.index') }}" class="text-gray-400 hover:text-white transition">ディベート履歴</a></li>
                    @else
                    <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition">ログイン</a></li>
                    <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition">新規登録</a></li>
                    @endauth
                </ul>
            </div>

            <!-- リンク3 -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-4">サポート</h3>
                <ul class="space-y-2">
                    <li><a href="https://docs.google.com/forms/d/e/1FAIpQLSeojaEKvwrH1Ewi49qqN8S6i3HqF9yoeMSCvKpGk58soFLuqA/viewform?usp=dialog" class="text-gray-400 hover:text-white transition">お問い合わせ</a></li>
                    <li><a href="{{ route('terms') }}" class="text-gray-400 hover:text-white transition">利用規約</a></li>
                    <li><a href="{{ route('privacy') }}" class="text-gray-400 hover:text-white transition">プライバシーポリシー</a></li>
                </ul>
            </div>

            <!-- ソーシャルとコンタクト -->
            {{-- <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-4">お問い合わせ</h3>
                <ul class="space-y-2">
                    <li class="flex items-center">
                        <i class="fa-solid fa-envelope mr-2 text-gray-400"></i>
                        <a href="mailto:info@debatematch.com" class="text-gray-400 hover:text-white transition">info@debatematch.com</a>
                    </li>
                </ul> --}}

                {{-- <h3 class="text-lg font-semibold mb-2 mt-6">フォロー</h3>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fa-brands fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fa-brands fa-facebook text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fa-brands fa-instagram text-xl"></i>
                    </a>
                </div> --}}
            </div>
        </div>
    </div>
</footer>
