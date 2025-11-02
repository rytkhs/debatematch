<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- ページヘッダー -->
                    <div class="mb-8 text-center">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">
                            {{ __('Feedback') }}
                        </h1>
                        <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-2">
                            {{ __('Found a bug or have a feature request? Let us know! No name or email required.') }}
                        </p>
                        <p class="text-sm text-gray-500 max-w-2xl mx-auto">
                            {{ __('Need a reply?') }}
                            <a href="{{ route('contact.index') }}" class="text-primary hover:text-primary/80 underline">
                                {{ __('Contact us here') }}
                            </a>
                        </p>
                    </div>

                    <!-- 問題報告・機能リクエストフォーム -->
                    <livewire:issue-form />
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
