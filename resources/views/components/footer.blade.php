<footer class="bg-slate-700 text-white border-t border-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- ロゴとコピーライト -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center mb-4">
                    <svg class="h-8 w-8 text-primary" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4.5V6.5M12 17.5V19.5M4.5 12H6.5M17.5 12H19.5M16.5 16.5L15 15M16.5 7.5L15 9M7.5 16.5L9 15M7.5 7.5L9 9M12 15C13.6569 15 15 13.6569 15 12C15 10.3431 13.6569 9 12 9C10.3431 9 9 10.3431 9 12C9 13.6569 10.3431 15 12 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="ml-2 text-xl font-bold text-primary-light">DebateMatch</span>
                </div>
                <p class="text-gray-500 text-sm mt-6">
                    © {{ date('Y') }} DebateMatch. All rights reserved.
                </p>
            </div>

            <!-- サポート -->
            <div class="col-span-1">
                <h3 class="text-lg font-semibold mb-4 text-primary-light">{{ __('messages.support') }}</h3>
                <ul class="space-y-3">
                    <li>
                        <a href="https://docs.google.com/forms/d/e/1FAIpQLSeojaEKvwrH1Ewi49qqN8S6i3HqF9yoeMSCvKpGk58soFLuqA/viewform?usp=dialog" class="text-gray-400 hover:text-primary-light transition flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                            {{ __('messages.contact_us') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('terms') }}" class="text-gray-400 hover:text-primary-light transition flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            {{ __('messages.terms_of_service') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('privacy') }}" class="text-gray-400 hover:text-primary-light transition flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            {{ __('messages.privacy_policy') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
