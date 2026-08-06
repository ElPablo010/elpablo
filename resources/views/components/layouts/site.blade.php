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
    'locale' => null,
    'alternates' => [],
])

@php
    // Site-brede structured data (LocalBusiness + WebSite) + pagina-specifieke nodes
    // in één @graph.
    $graph = array_merge(\App\Support\Seo::globalGraph(), $schema ?? []);
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Merk-fonts: Anton (koppen) + Inter (tekst). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
        :locale="$locale ?? app()->getLocale()"
        :alternates="$alternates"
    />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire (en de gebundelde Alpine) expliciet laden zodat Alpine op ÉLKE
         pagina beschikbaar is — ook op pagina's zonder Livewire-component. Zonder
         dit injecteert Livewire z'n scripts enkel wanneer er een component op de
         pagina staat, waardoor de cookiebanner, het mobiele menu, de FAQ-accordeon
         en de audiospeler stilvallen op formulier-loze pagina's. --}}
    @livewireStyles
</head>
<body class="min-h-screen bg-ink-950 font-sans text-gray-200 antialiased">
    <x-site.header :page="$page" />

    {{-- Snelkoppeling naar de admin, enkel zichtbaar voor ingelogde beheerders. --}}
    <x-site.admin-edit :page="$page" />

    <main>
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.cookie-consent />

    @livewireScripts
</body>
</html>
