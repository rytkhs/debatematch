@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'type' => 'website',
    'twitterCard' => 'summary_large_image',
    'siteName' => null,
    'noindex' => false,
])

@php
    $siteName = $siteName ?: config('app.name', 'DebateMatch');
    $description = $description ?: __('misc.app_meta_description');
    $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
    $path = request()->getPathInfo() ?: '/';
    $canonical = $canonical ?: $baseUrl . ($path === '/' ? '/' : $path);
    $image = $image ?: asset('images/og-image.png');
    $computedTitle = $title ? $title . ' - ' . $siteName : $siteName;
@endphp

<title>{{ $computedTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="author" content="DebateMatch">
<link rel="canonical" href="{{ $canonical }}">
@if ($noindex)
    <meta name="robots" content="noindex,follow">
@endif

<meta property="og:title" content="{{ $computedTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:width" content="1024">
<meta property="og:image:height" content="1024">
<meta property="og:image:alt" content="{{ $computedTitle }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:title" content="{{ $computedTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:image:alt" content="{{ $computedTitle }}">
