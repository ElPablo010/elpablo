@php
    // Minimale, neutrale footer. Per project volledig vrij te herontwerpen — leest
    // de instellingen uit de admin (Footer-pagina) + de footermenu's uit de DB.
    $footer = \App\Support\SiteFooter::current();
    $contact = $footer['contact'] ?? [];
    $brand = $footer['brand'] ?? [];
    $social = $footer['social'] ?? [];
    $footerMenus = \App\Models\Menu::whereIn('location', ['footer_1', 'footer_2', 'footer_3'])
        ->with('items')
        ->get()
        ->keyBy('location');
@endphp

<footer class="mt-24 border-t border-gray-200 bg-gray-50">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 md:grid-cols-4">
        <div>
            <div class="font-semibold">{{ $brand['name'] ?? config('app.name') }}</div>
            @if (! empty($brand['tagline']))
                <p class="mt-2 text-sm text-gray-500">{{ $brand['tagline'] }}</p>
            @endif
        </div>

        @foreach (['footer_1', 'footer_2', 'footer_3'] as $location)
            @php $menu = $footerMenus->get($location); @endphp
            @if ($menu && $menu->items->isNotEmpty())
                <div>
                    @if (! empty($menu->title))
                        <div class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $menu->title }}</div>
                    @endif
                    <ul class="space-y-2 text-sm">
                        @foreach ($menu->items as $item)
                            <li>
                                <a
                                    href="{{ $item->resolvedHref() }}"
                                    @if ($item->target_blank) target="_blank" rel="noopener" @endif
                                    class="hover:text-primary-600"
                                >{{ $item->label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 border-t border-gray-200 py-4 text-center text-xs text-gray-400">
        <span>© {{ now()->year }} {{ $brand['name'] ?? config('app.name') }}</span>
        {{-- Heropent de cookiebanner; verplicht zodat een bezoeker z'n keuze kan herzien. --}}
        <button
            type="button"
            onclick="window.dispatchEvent(new CustomEvent('open-cookie-preferences'))"
            class="cursor-pointer transition-colors hover:text-gray-600"
        >Cookie-instellingen</button>
    </div>
</footer>
