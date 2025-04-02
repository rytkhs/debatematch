<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <!-- プロフィール情報カード -->
            <div class="p-6 sm:p-8 bg-white shadow-md sm:rounded-lg border border-gray-200 transition hover:shadow-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- パスワード更新カード -->
            <div class="p-6 sm:p-8 bg-white shadow-md sm:rounded-lg border border-gray-200 transition hover:shadow-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- アカウント削除カード -->
            <div class="p-6 sm:p-8 bg-white shadow-md sm:rounded-lg border border-gray-200 transition hover:shadow-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
