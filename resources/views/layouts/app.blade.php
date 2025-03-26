<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons|Material+Icons+Outlined">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
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
