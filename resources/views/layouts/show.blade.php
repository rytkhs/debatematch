<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-seo
            :title="$title"
            :description="$description"
            :canonical="$canonical"
            :image="$image"
            :type="$type"
            :twitter-card="$twitterCard"
            :site-name="$siteName"
            :noindex="$noindex"
        />

        <!-- Fonts -->
        <!-- Favicon - Google検索結果で適切に表示されるよう複数サイズを提供 -->
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
        <link rel="icon" href="{{ asset('favicon.svg') }}" sizes="any" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon-48x48.png') }}" sizes="48x48" type="image/png">
        <link rel="icon" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" type="image/png">
        <link rel="icon" href="{{ asset('favicon-144x144.png') }}" sizes="144x144" type="image/png">
        <link rel="icon" href="{{ asset('favicon-192x192.png') }}" sizes="192x192" type="image/png">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined">
        @stack('styles')
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

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

        <!-- PWA関連メタタグを追加 -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#4F46E5">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.svg') }}">
    </head>
    <body class="font-sans antialiased" >
        <div class="min-h-screen bg-gray-100 flex flex-col">
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
        @stack('scripts')
        @livewireScripts
    </body>
</html>
