{{--
    404-pagina in de site-huisstijl. Wordt ook getoond voor oude WordPress-URL's
    die bewust géén redirect kregen (zie RedirectSeeder), dus de doorverwijzingen
    hieronder wijzen naar waar die bezoekers vermoedelijk naartoe wilden.

    De locale is al gezet door PublicPageController vóór de abort(404); voor URL's
    die helemaal geen route matchen valt Laravel terug op de standaardtaal (nl).
--}}
<x-layouts.site
    :title="__('Pagina niet gevonden')"
    :description="__('Deze pagina bestaat niet (meer). Ontdek de mixtapes of boek El Pablo voor je feest.')"
    robots="noindex, follow"
>
    {{-- Geen flex-centrering: de header is fixed en zweeft over de content heen,
         dus de eerste regel moet met échte top-padding vrijgezet worden. Bij
         verticaal centreren wordt die padding "opgegeten" en schuift de eyebrow
         onder de menubalk. --}}
    <section class="relative overflow-hidden bg-ink-950">
        {{-- Zelfde scrim-taal als de hero: magenta gloed tegen het diepe zwart. --}}
        <div class="pointer-events-none absolute -top-32 -left-24 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 right-0 h-96 w-96 rounded-full bg-primary-600/10 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-7xl px-4 pb-24 pt-36 sm:pt-44 lg:px-6 lg:pb-32">
            <div class="max-w-2xl">
                <p class="eyebrow mb-5">{{ __('Foutmelding 404') }}</p>

                <p class="font-display text-[5rem] leading-none text-primary-600 sm:text-[8rem]">404</p>

                <h1 class="mt-4 font-display text-[2.4rem] leading-[0.95] text-white sm:text-5xl lg:text-6xl">
                    {{ __('Deze track staat niet meer in de set') }}
                </h1>

                <p class="mt-6 max-w-xl text-lg leading-relaxed text-gray-400">
                    {{ __('De pagina die je zocht bestaat niet meer of is verhuisd. Geen zorgen — de muziek speelt gewoon door.') }}
                </p>

                <div class="mt-9 flex flex-wrap gap-4">
                    <a href="{{ \App\Support\Locale::href('/') }}" class="btn-primary">
                        {{ __('Naar de homepage') }}
                        <x-lucide-arrow-right class="h-4 w-4" />
                    </a>
                    <a href="{{ \App\Support\Locale::href('/muziek') }}" class="btn-secondary">
                        {{ __('Beluister de mixes') }}
                    </a>
                    <a href="{{ \App\Support\Locale::href('/boeken') }}" class="btn-ghost">
                        {{ __('El Pablo boeken') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
