<x-layouts.site
    :title="$seo['title']"
    :description="$seo['description']"
    :canonical="$seo['canonical']"
    :robots="$seo['robots']"
    :type="$seo['type']"
    :schema="$seo['schema']"
    :locale="$seo['locale']"
    :alternates="$seo['alternates']"
>
    <section class="relative overflow-hidden bg-ink-950">
        <div class="pointer-events-none absolute -top-32 -left-24 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-7xl px-4 pb-24 pt-36 sm:pt-44 lg:px-6">
            <p class="eyebrow mb-5">{{ __('Agenda') }}</p>
            <h1 class="font-display text-[2.4rem] leading-[0.95] text-white sm:text-5xl lg:text-6xl">
                {{ __('Events') }}
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-gray-400">
                {{ __('Hier draait El Pablo binnenkort. Scoor je tickets en kom dansen.') }}
            </p>

            {{-- Aankomende events --}}
            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($upcoming as $event)
                    <a href="{{ $event->localizedPath() }}"
                       class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-ink-900 transition-all duration-300 hover:-translate-y-1 hover:border-primary-500/50 hover:shadow-xl hover:shadow-primary-600/10">
                        @if ($event->image_url)
                            <div class="relative aspect-[4/3] overflow-hidden">
                                <picture>
                                    <source srcset="{{ $event->image_url }}" type="image/webp">
                                    <img src="{{ $event->image_url }}" alt="{{ $event->image_alt ?? $event->translated('name') }}" loading="lazy"
                                         class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                </picture>
                                <div class="absolute inset-0 bg-gradient-to-t from-ink-900 via-ink-900/20 to-transparent"></div>
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col p-6">
                            @if ($event->isCancelled())
                                <span class="mb-3 self-start rounded-full bg-red-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-red-400">{{ __('Afgelast') }}</span>
                            @endif

                            <p class="text-sm font-semibold uppercase tracking-wide text-primary-500">
                                {{ $event->dateLabel() }}@if ($event->startTimeFormatted()) · {{ $event->startTimeFormatted() }}@endif
                            </p>
                            <h2 class="mt-2 font-display text-2xl text-white">{{ $event->translated('name') }}</h2>
                            @if ($event->venue_name || $event->venue_city)
                                <p class="mt-2 inline-flex items-center gap-1.5 text-sm text-gray-400">
                                    <x-lucide-map-pin class="h-4 w-4 shrink-0" />
                                    {{ collect([$event->venue_name, $event->venue_city])->filter()->join(', ') }}
                                </p>
                            @endif
                            @if ($event->translated('short_description'))
                                <p class="mt-3 text-sm leading-relaxed text-gray-400">{{ $event->translated('short_description') }}</p>
                            @endif

                            <span class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-semibold uppercase tracking-wide text-primary-500">
                                {{ __('Tickets & info') }}
                                <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="text-gray-400 md:col-span-2 lg:col-span-3">{{ __('Er staan momenteel geen events gepland. Kom snel nog eens terug!') }}</p>
                @endforelse
            </div>

            {{-- Voorbije events --}}
            @if ($past->isNotEmpty())
                <div class="mt-20">
                    <h2 class="font-display text-2xl text-white">{{ __('Voorbije events') }}</h2>
                    <ul class="mt-6 divide-y divide-white/10 border-y border-white/10">
                        @foreach ($past as $event)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-3 text-gray-400">
                                <span class="font-medium text-gray-300">{{ $event->translated('name') }}</span>
                                <span class="text-sm">{{ $event->dateLabel() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>
</x-layouts.site>
