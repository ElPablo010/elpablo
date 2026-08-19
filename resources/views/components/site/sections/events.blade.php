@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $limit = (int) ($content['limit'] ?? 3);

    $events = \App\Models\Event::query()
        ->published()
        ->upcoming()
        ->notCancelled()
        ->with(['translations'])
        ->orderBy('start_date')
        ->limit($limit)
        ->get();
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        @if ($events->isEmpty())
            <p class="mt-14 text-gray-400">{{ __('Er staan momenteel geen events gepland. Kom snel nog eens terug!') }}</p>
        @else
            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <a href="{{ $event->localizedPath() }}"
                       {{-- Geen hover:-translate-y: zie pages/events/index. --}}
                       class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-ink-900 transition-all duration-300 hover:border-primary-500/50 hover:shadow-xl hover:shadow-primary-600/10">
                        @if ($event->image_url)
                            {{-- transform-gpu + isolate: zie pages/events/index — houdt de
                                 overflow-clip in sync met de GPU-laag van het zoomende beeld. --}}
                            <div class="relative isolate aspect-[4/3] transform-gpu overflow-hidden bg-ink-900">
                                <picture>
                                    <source srcset="{{ $event->image_url }}" type="image/webp">
                                    <img src="{{ $event->image_url }}" alt="{{ $event->image_alt ?? $event->translated('name') }}" loading="lazy"
                                         {{-- Basis-scale 1.02: zie pages/events/index. --}}
                                         class="h-full w-full scale-[1.02] object-cover transition-transform duration-500 will-change-transform group-hover:scale-105">
                                </picture>
                                <div class="absolute inset-0 bg-gradient-to-t from-ink-900 via-ink-900/20 to-transparent"></div>
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col p-6">
                            <p class="text-sm font-semibold uppercase tracking-wide text-primary-500">
                                {{ $event->dateLabel() }}@if ($event->startTimeFormatted()) · {{ $event->startTimeFormatted() }}@endif
                            </p>
                            <h3 class="mt-2 font-display text-2xl text-white">{{ $event->translated('name') }}</h3>
                            @if ($event->venue_name || $event->venue_city)
                                <p class="mt-2 inline-flex items-center gap-1.5 text-sm text-gray-400">
                                    <x-lucide-map-pin class="h-4 w-4 shrink-0" />
                                    {{ collect([$event->venue_name, $event->venue_city])->filter()->join(', ') }}
                                </p>
                            @endif

                            <span class="mt-auto inline-flex items-center gap-2 pt-5 text-sm font-semibold uppercase tracking-wide text-primary-500">
                                {{ __('Tickets & info') }}
                                <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-12">
                <a href="{{ \App\Support\Locale::href('/events') }}" class="btn-secondary">
                    {{ filled($content['cta_label'] ?? null) ? $content['cta_label'] : __('Alle events') }}
                    <x-lucide-arrow-right class="h-4 w-4" />
                </a>
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
