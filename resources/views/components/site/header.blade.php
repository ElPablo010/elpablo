@props(['page' => null])

@php
    use App\Support\Locale;

    // Zwevende "pill"-header: een afgeronde balk met een eigen halftransparante
    // achtergrond + rand, die over de content zweeft. Zo blijft de nav-tekst
    // altijd leesbaar (ook boven een lichte hero of over content bij het scrollen).
    // Wordt iets meer opaak zodra je scrolt.
    $header = \App\Support\SiteHeader::current();
    $menu = \App\Models\Menu::where('location', 'main')->with('items.children')->first();
    $cta = $header['cta'] ?? [];

    // Taalschakelaar — meertalig (NL/EN/ES). De schakelaar linkt naar dezelfde
    // pagina in de doeltaal (zelfde slug per taal). Het basis-pad komt uit het
    // request, zodat ook niet-builder-pagina's (events) correct wisselen.
    $locales = Locale::LABELS;
    $currentLocale = Locale::current();
    $switchBase = Locale::switchBase();
@endphp

<header
    x-data="{ scrolled: false, mobileOpen: false }"
    @scroll.window="scrolled = window.scrollY > 24"
    class="fixed inset-x-0 top-0 z-50 px-4 pt-3 sm:pt-4"
>
    <div class="mx-auto max-w-7xl">
        <div
            class="rounded-2xl border border-white/10 shadow-lg shadow-black/20 backdrop-blur-md transition-colors duration-300"
            :class="scrolled ? 'bg-ink-950/90' : 'bg-ink-950/70'"
        >
            <div class="flex items-center justify-between gap-6 px-4 py-3 sm:px-6">
                {{-- Logo + merknaam — het beeldmerk links, de naam ernaast. Zonder
                     logo staat de naam er alleen; met een logo dat de naam zelf al
                     bevat zet je "Naam naast het logo tonen" uit in de admin. --}}
                <a href="{{ Locale::href('/') }}" class="flex items-center gap-3 text-white">
                    @if (! empty($header['logo']))
                        <img src="{{ $header['logo'] }}" alt="{{ $header['name'] }}" class="h-9 w-auto">
                    @endif
                    @if (empty($header['logo']) || ($header['show_name'] ?? true))
                        <span class="flex flex-col leading-none">
                            <span class="font-display text-2xl tracking-tight">{{ $header['name'] }}</span>
                            @if (! empty($header['subtitle']))
                                <span class="mt-1 text-[0.65rem] font-medium uppercase tracking-[0.25em] text-primary-500">{{ $header['subtitle'] }}</span>
                            @endif
                        </span>
                    @endif
                </a>

                {{-- Desktop-navigatie --}}
                @if ($menu)
                    <nav class="hidden items-center gap-8 lg:flex">
                        @foreach ($menu->items as $item)
                            <a
                                href="{{ Locale::href($item->resolvedHref()) }}"
                                @if ($item->target_blank) target="_blank" rel="noopener" @endif
                                class="group relative text-sm font-medium uppercase tracking-wide text-gray-200 transition-colors hover:text-white"
                            >
                                {{ __($item->label) }}
                                <span class="absolute -bottom-1.5 left-0 h-0.5 w-0 bg-primary-500 transition-all duration-300 group-hover:w-full"></span>
                            </a>
                        @endforeach
                    </nav>
                @endif

                {{-- Rechts: taalschakelaar + CTA (desktop) + hamburger (mobiel) --}}
                <div class="flex items-center gap-3">
                    <div x-data="{ open: false }" class="relative hidden sm:block">
                        <button
                            type="button"
                            @click="open = ! open"
                            @click.outside="open = false"
                            class="flex cursor-pointer items-center gap-1.5 rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-200 transition-colors hover:text-white"
                        >
                            <x-lucide-globe class="h-4 w-4" />
                            {{ $locales[$currentLocale] }}
                            <x-lucide-chevron-down class="h-3.5 w-3.5 transition-transform" ::class="open ? 'rotate-180' : ''" />
                        </button>
                        <div
                            x-show="open"
                            x-cloak
                            x-transition
                            class="absolute right-0 mt-2 w-24 overflow-hidden rounded-xl border border-white/10 bg-ink-900 py-1 shadow-xl"
                        >
                            @foreach ($locales as $code => $label)
                                <a
                                    href="{{ Locale::href($switchBase, $code) }}"
                                    class="block px-4 py-2 text-xs font-semibold uppercase tracking-wide transition-colors {{ $code === $currentLocale ? 'text-primary-500' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}"
                                >{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Login voor beheer (Filament) --}}
                    <a
                        href="/admin"
                        aria-label="{{ __('Inloggen') }}"
                        title="{{ __('Inloggen') }}"
                        class="hidden h-9 w-9 items-center justify-center rounded-full text-gray-200 transition-colors hover:bg-white/10 hover:text-white sm:inline-flex"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"/></svg>
                    </a>

                    @if (! empty($cta['label']))
                        <a href="{{ Locale::href($cta['href'] ?? '/') }}" class="btn-primary hidden py-2.5 sm:inline-flex">{{ __($cta['label']) }}</a>
                    @endif

                    <button
                        type="button"
                        @click="mobileOpen = ! mobileOpen"
                        class="flex cursor-pointer items-center justify-center rounded-lg p-1.5 text-white lg:hidden"
                        aria-label="Menu"
                    >
                        <x-lucide-menu x-show="! mobileOpen" class="h-6 w-6" />
                        <x-lucide-x x-show="mobileOpen" x-cloak class="h-6 w-6" />
                    </button>
                </div>
            </div>

            {{-- Mobiel menu-paneel — binnen de pill, onder de balk --}}
            <div
                x-show="mobileOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="border-t border-white/10 px-4 py-5 lg:hidden"
            >
                @if ($menu)
                    <nav class="flex flex-col gap-1">
                        @foreach ($menu->items as $item)
                            <a
                                href="{{ Locale::href($item->resolvedHref()) }}"
                                @if ($item->target_blank) target="_blank" rel="noopener" @endif
                                class="rounded-lg px-3 py-3 text-base font-medium uppercase tracking-wide text-gray-200 transition-colors hover:bg-white/5 hover:text-white"
                            >{{ __($item->label) }}</a>
                        @endforeach
                    </nav>
                @endif

                <div class="mt-4 flex items-center justify-between gap-3 border-t border-white/10 pt-4">
                    <div class="flex items-center gap-1">
                        @foreach ($locales as $code => $label)
                            <a
                                href="{{ Locale::href($switchBase, $code) }}"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold uppercase tracking-wide {{ $code === $currentLocale ? 'bg-white/10 text-primary-500' : 'text-gray-300' }}"
                            >{{ $label }}</a>
                        @endforeach

                        {{-- Enkel het icoon — het label maakt de rij te druk op smalle schermen. --}}
                        <a
                            href="/admin"
                            aria-label="{{ __('Inloggen') }}"
                            title="{{ __('Inloggen') }}"
                            class="ml-1 flex h-9 w-9 items-center justify-center rounded-lg text-gray-300 transition-colors hover:bg-white/5 hover:text-white"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"/></svg>
                        </a>
                    </div>
                    @if (! empty($cta['label']))
                        <a href="{{ Locale::href($cta['href'] ?? '/') }}" class="btn-primary py-2.5">{{ __($cta['label']) }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
