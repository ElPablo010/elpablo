@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $items = $content['items'] ?? [];

    $platformLabels = [
        'mixcloud' => 'Mixcloud',
        'soundcloud' => 'SoundCloud',
        'spotify' => 'Spotify',
        'youtube' => 'YouTube',
    ];
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <a
                    href="{{ $item['url'] ?? '#' }}"
                    target="_blank"
                    rel="noopener"
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-ink-900 transition-all duration-300 hover:-translate-y-1 hover:border-primary-500/50 hover:shadow-xl hover:shadow-primary-600/10"
                >
                    <div class="relative aspect-square overflow-hidden bg-ink-800">
                        @if (! empty($item['cover']))
                            <picture>
                                <source srcset="{{ $item['cover'] }}" type="image/webp">
                                <img src="{{ $item['cover'] }}" alt="{{ $item['title'] ?? '' }}" loading="lazy"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </picture>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-ink-950/90 via-transparent to-transparent"></div>

                        {{-- Play-knop --}}
                        <span class="absolute inset-0 flex items-center justify-center">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg shadow-primary-600/40 transition-transform duration-300 group-hover:scale-110">
                                <x-lucide-play class="h-6 w-6 translate-x-0.5 fill-current" />
                            </span>
                        </span>

                        @if (! empty($item['platform']))
                            <span class="absolute left-4 top-4 rounded-full bg-black/50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white backdrop-blur">
                                {{ $platformLabels[$item['platform']] ?? $item['platform'] }}
                            </span>
                        @endif
                    </div>

                    <div class="p-6">
                        <h3 class="text-lg font-bold text-white transition-colors group-hover:text-primary-400">{{ $item['title'] ?? '' }}</h3>
                        @if (! empty($item['subtitle']))
                            <p class="mt-1 text-sm text-gray-400">{{ $item['subtitle'] }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
