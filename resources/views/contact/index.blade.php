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
                            {{ __('Contact Us') }}
                        </h1>
                        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                            {{ __('We\'d love to hear from you. Send us a message and we\'ll respond as soon as possible.') }}
                        </p>
                    </div>

                    <!-- お問い合わせフォーム -->
                    <livewire:contact-form />
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
