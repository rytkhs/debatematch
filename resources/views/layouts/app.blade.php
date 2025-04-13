<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="icon" href="{{ asset('favicon.svg') }}" sizes="any" type="image/svg+xml">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined">
        @stack('styles')
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Clarity -->
        <script type="text/javascript">
            (function(c,l,a,r,i,t,y){
                c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
            })(window, document, "clarity", "script", "qy064ia96z");
        </script>

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-F13NEPXT56"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-F13NEPXT56');
        </script>
    </head>
    <body class="font-sans antialiased" >
        <div class="min-h-screen bg-gray-100 flex flex-col mt-16">
            <!-- Page Heading -->
            @isset($header)
            <header>
                {{ $header }}
            </header>
            @endisset

            <!-- フラッシュメッセージ -->
            <x-flash-message />
            <!-- フラッシュメッセージ (Livewire) -->
            <livewire:flash-message />

            <!-- Page Content -->
            <main class="flex-grow">
                {{ $slot }}
            </main>

            @isset($footer)
            <footer>
                {{ $footer }}
            </footer>
            @endisset
        </div>
        <script>
            window.translations = @json(__('messages'));
        </script>
        @stack('scripts')
        @livewireScripts
    </body>
</html>
