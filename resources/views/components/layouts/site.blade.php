@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => 'index, follow',
    'image' => null,
    'imageAlt' => null,
    'imageWidth' => null,
    'imageHeight' => null,
    'type' => 'website',
    'schema' => [],
    'page' => null,
])

@php
    // Site-brede structured data (LocalBusiness + WebSite) + pagina-specifieke nodes
    // in één @graph.
    $graph = array_merge(\App\Support\Seo::globalGraph(), $schema ?? []);
@endphp

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-site.meta
        :title="$title"
        :description="$description"
        :canonical="$canonical"
        :robots="$robots"
        :image="$image"
        :image-alt="$imageAlt"
        :image-width="$imageWidth"
        :image-height="$imageHeight"
        :type="$type"
        :graph="$graph"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white text-gray-900 antialiased">
    <x-site.header />

    <main>
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.cookie-consent />
</body>
</html>
