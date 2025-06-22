<x-app-layout>
    <x-slot name="header">
        <x-header></x-header>
    </x-slot>

    <div class="min-h-screen bg-gray-50 py-8 sm:py-12">
        <div class="max-w-4xl mx-auto px-6 sm:px-8 lg:px-10 leading-tight text-gray-600 prose prose-xs font-serif">
            @php
                $locale = App::getLocale();
                $markdownPath = resource_path("markdown/{$locale}/terms.md");
                $fallbackLocale = 'en';
                $fallbackPath = resource_path("markdown/{$fallbackLocale}/terms.md");

                if (!file_exists($markdownPath)) {
                    $markdownPath = $fallbackPath;
                }
            @endphp

            @if(file_exists($markdownPath))
                {!! Str::markdown(file_get_contents($markdownPath)) !!}
            @else
                <p class="text-red-500">Terms of service content is currently unavailable.</p>
            @endif
        </div>
    </div>

    <x-slot name="footer">
        <x-footer></x-footer>
    </x-slot>
</x-app-layout>
