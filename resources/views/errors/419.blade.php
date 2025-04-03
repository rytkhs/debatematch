<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary-light text-primary mb-4">
                        <i class="fa-solid fa-clock text-3xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">ページの有効期限が切れました (419)</h1>
                    <p class="text-lg text-gray-600">セッションの有効期限が切れたため、操作を完了できませんでした</p>
                </div>
                <div class="text-center mt-8">
                    <p class="text-gray-600 mb-6">ページを更新して再度お試しください</p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="{{ url()->current() }}" class="hero-button bg-primary text-white hover:bg-primary-dark">
                            <i class="fa-solid fa-redo mr-2"></i>
                            ページを更新
                        </a>
                        <a href="{{ url('/') }}" class="hero-button bg-white text-primary border-2 border-primary hover:bg-primary hover:text-white">
                            <i class="fa-solid fa-home mr-2"></i>
                            ホームに戻る
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
