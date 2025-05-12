<footer class="bg-white py-6 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center md:flex-row md:justify-between">
            <!-- ロゴとコピーライト -->
            <div class="flex items-center mb-4 md:mb-0">
                <a href="{{ route('welcome') }}" class="flex items-center">
                    <svg class="h-6 w-6 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4.5V6.5M12 17.5V19.5M4.5 12H6.5M17.5 12H19.5M16.5 16.5L15 15M16.5 7.5L15 9M7.5 16.5L9 15M7.5 7.5L9 9M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <span class="ml-2 text-sm text-gray-500">&copy; {{ date('Y') }} DebateMatch. All rights reserved.</span>
            </div>

            <!-- ナビゲーションリンク -->
            <div class="flex flex-wrap justify-center gap-4 mb-4 md:mb-0">
                <a href="https://docs.google.com/forms/d/e/1FAIpQLSeojaEKvwrH1Ewi49qqN8S6i3HqF9yoeMSCvKpGk58soFLuqA/viewform?usp=dialog" class="text-sm text-gray-600 hover:text-primary transition-colors">
                    {{ __('messages.contact_us') }}
                </a>
                <a href="{{ route('terms') }}" class="text-sm text-gray-600 hover:text-primary transition-colors">
                    {{ __('messages.terms_of_service') }}
                </a>
                <a href="{{ route('privacy') }}" class="text-sm text-gray-600 hover:text-primary transition-colors">
                    {{ __('messages.privacy_policy') }}
                </a>
            </div>

            <!-- ソーシャルメディアリンク -->
            <div class="flex space-x-4">
                @php
                    // ロケールによってXアカウントのURLを切り替える
                    $xUrl = app()->getLocale() === 'ja'
                        ? 'https://x.com/debatematch_jp'
                        : 'https://x.com/debatematch_en';
                @endphp
                <a href="{{ $xUrl }}" class="text-gray-500 hover:text-primary transition-colors" aria-label="Twitter">
                    <i class="fa-brands fa-x-twitter text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</footer>
