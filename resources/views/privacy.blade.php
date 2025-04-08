<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="min-h-screen bg-gray-50 py-8 sm:py-12">
        <div class="max-w-none sm:max-w-none mx-auto px-4 sm:px-6 lg:px-8 leading-normal text-gray-700 prose prose-sm sm:prose">
            {!! Str::markdown(file_get_contents(resource_path('markdown/privacy.md'))) !!}
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
