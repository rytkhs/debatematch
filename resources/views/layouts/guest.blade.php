<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('favicon.svg') }}" sizes="any" type="image/svg+xml">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 ">
    <h2 class="text-2xl font-bold text-gray-800 mt-3 text-center">DebateMatch</h2>
        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}

            <p class="text-gray-500 text-sm mt-4 text-center">
                続行することで、以下に同意したものとみなされます
            </p>
            <p class="text-gray-500 text-sm text-center">
                <a href="#" class="text-gray-500 underline">利用規約</a> | <a href="#" class="text-gray-500 underline">プライバシーポリシー</a>
            </p>
        </div>
    </div>
</body>

</html>
