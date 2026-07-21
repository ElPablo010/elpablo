@php
    // Minimale, neutrale header. Per project volledig vrij te herontwerpen — leest
    // de instellingen uit de admin (Header-pagina) + het hoofdmenu uit de DB.
    $header = \App\Support\SiteHeader::current();
    $menu = \App\Models\Menu::where('location', 'main')->with('items.children')->first();
    $cta = $header['cta'] ?? [];
@endphp

<header class="border-b border-gray-200">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4 py-4">
        <a href="/" class="flex items-center gap-3">
            @if (! empty($header['logo']))
                <img src="{{ $header['logo'] }}" alt="{{ $header['name'] }}" class="h-10 w-auto">
            @endif
            <span class="flex flex-col leading-tight">
                <span class="font-semibold">{{ $header['name'] }}</span>
                @if (! empty($header['subtitle']))
                    <span class="text-xs text-gray-500">{{ $header['subtitle'] }}</span>
                @endif
            </span>
        </a>

        @if ($menu)
            <nav class="hidden items-center gap-6 md:flex">
                @foreach ($menu->items as $item)
                    <a
                        href="{{ $item->resolvedHref() }}"
                        @if ($item->target_blank) target="_blank" rel="noopener" @endif
                        class="text-sm hover:text-primary-600"
                    >{{ $item->label }}</a>
                @endforeach
            </nav>
        @endif

        @if (! empty($cta['label']))
            <a
                href="{{ $cta['href'] ?? '/' }}"
                class="rounded-full bg-primary-600 px-4 py-2 text-sm font-medium text-white"
            >{{ $cta['label'] }}</a>
        @endif
    </div>
</header>
