<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <div class="flex flex-col h-screen bg-gray-100 text-gray-800">
        {{-- ヘッダー --}}
        <header class="bg-gray-800 text-primary-foreground p-4 flex justify-between items-center">
            <h1 class="text-white text-xl font-bold">ディベートルーム</h1>
            <Button class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                ディベート終了
            </Button>
        </header>

        <div class="flex flex-1">
            <div class="w-1/4 bg-white p-4 border-r border-gray-200 flex flex-col">
                {{-- 左側：ディベート情報 --}}
                <livewire:debate-info :debate="$debate" />
            </div>
        </div>
        {{-- 右側：チャットエリア --}}
        <livewire:debate-chat :debate="$debate" />

    </div>
</body>

</html>
