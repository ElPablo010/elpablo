@php
    use App\Support\Locale;

    // Dark nightlife-footer. Leest instellingen uit de admin (Footer-pagina) +
    // de footermenu's uit de DB.
    $footer = \App\Support\SiteFooter::current();
    $contact = $footer['contact'] ?? [];
    $brand = $footer['brand'] ?? [];
    $social = array_filter($footer['social'] ?? []);
    $footerMenus = \App\Models\Menu::whereIn('location', ['footer_1', 'footer_2', 'footer_3'])
        ->with('items')
        ->get()
        ->keyBy('location');

    $socialIcons = ['instagram' => 'lucide-instagram', 'facebook' => 'lucide-facebook', 'youtube' => 'lucide-youtube'];
@endphp

<footer class="relative overflow-hidden border-t border-white/10 bg-ink-950 text-gray-400">
    {{-- Subtiele magenta gloed bovenaan --}}
    <div class="pointer-events-none absolute -top-32 left-1/2 h-64 w-[40rem] -translate-x-1/2 rounded-full bg-primary-600/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-7xl px-4 py-16 lg:px-6">
        <div class="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
            {{-- Brand + social --}}
            <div>
                <a href="{{ Locale::href('/') }}" class="inline-flex flex-col leading-none text-white">
                    @if (! empty($brand['logo']))
                        <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] ?? config('app.name') }}" class="h-10 w-auto">
                    @else
                        <span class="font-display text-3xl tracking-tight">{{ $brand['name'] ?? config('app.name') }}</span>
                        @if (! empty($brand['subtitle']))
                            <span class="mt-1 text-[0.65rem] font-medium uppercase tracking-[0.25em] text-primary-500">{{ $brand['subtitle'] }}</span>
                        @endif
                    @endif
                </a>
                @if (! empty($brand['tagline']))
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-gray-400">{{ __($brand['tagline']) }}</p>
                @endif

                @if (! empty($social))
                    <div class="mt-6 flex gap-3">
                        @foreach ($social as $platform => $url)
                            <a
                                href="{{ $url }}"
                                target="_blank"
                                rel="noopener"
                                aria-label="{{ ucfirst($platform) }}"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 text-gray-300 transition-all hover:border-primary-500 hover:bg-primary-600 hover:text-white"
                            >
                                <x-dynamic-component :component="$socialIcons[$platform] ?? 'lucide-link'" class="h-4.5 w-4.5" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Footermenu's --}}
            @foreach (['footer_1', 'footer_2'] as $location)
                @php $fm = $footerMenus->get($location); @endphp
                @if ($fm && $fm->items->isNotEmpty())
                    <div>
                        @if (! empty($fm->title))
                            <div class="mb-4 text-xs font-semibold uppercase tracking-[0.2em] text-white">{{ __($fm->title) }}</div>
                        @endif
                        <ul class="space-y-3 text-sm">
                            @foreach ($fm->items as $item)
                                <li>
                                    <a
                                        href="{{ Locale::href($item->resolvedHref()) }}"
                                        @if ($item->target_blank) target="_blank" rel="noopener" @endif
                                        class="text-gray-400 transition-colors hover:text-primary-400"
                                    >{{ __($item->label) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach

            {{-- Contactblok --}}
            <div>
                <div class="mb-4 text-xs font-semibold uppercase tracking-[0.2em] text-white">{{ __('Contact') }}</div>
                <ul class="space-y-3 text-sm">
                    @if (! empty($contact['phone']))
                        <li class="flex items-center gap-3">
                            <x-lucide-phone class="h-4 w-4 shrink-0 text-primary-500" />
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" class="text-gray-300 transition-colors hover:text-white">{{ $contact['phone'] }}</a>
                        </li>
                    @endif
                    @if (! empty($contact['email']))
                        <li class="flex items-center gap-3">
                            <x-lucide-mail class="h-4 w-4 shrink-0 text-primary-500" />
                            <a href="mailto:{{ $contact['email'] }}" class="text-gray-300 transition-colors hover:text-white">{{ $contact['email'] }}</a>
                        </li>
                    @endif
                    @if (! empty($contact['address']))
                        <li class="flex items-start gap-3">
                            <x-lucide-map-pin class="mt-0.5 h-4 w-4 shrink-0 text-primary-500" />
                            <span class="text-gray-400">{{ __($contact['address']) }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>

    <div class="relative border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-6 text-xs text-gray-500 sm:flex-row lg:px-6">
            <span>© {{ now()->year }} {{ $brand['name'] ?? config('app.name') }}. {{ __('Alle rechten voorbehouden.') }}</span>
            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
                @php $legal = $footerMenus->get('footer_3'); @endphp
                @if ($legal && $legal->items->isNotEmpty())
                    @foreach ($legal->items as $item)
                        <a href="{{ Locale::href($item->resolvedHref()) }}" class="transition-colors hover:text-gray-300">{{ __($item->label) }}</a>
                    @endforeach
                @endif
                {{-- Heropent de cookiebanner; verplicht zodat een bezoeker z'n keuze kan herzien. --}}
                <button
                    type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-cookie-preferences'))"
                    class="cursor-pointer transition-colors hover:text-gray-300"
                >{{ __('Cookie-instellingen') }}</button>
            </div>
        </div>
    </div>
</footer>
