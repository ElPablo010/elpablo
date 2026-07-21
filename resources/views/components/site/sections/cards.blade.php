@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $columns = (int) ($content['columns'] ?? 3);
    $colClass = ['2' => 'md:grid-cols-2', '3' => 'md:grid-cols-3', '4' => 'md:grid-cols-2 lg:grid-cols-4'][$columns] ?? 'md:grid-cols-3';
    $cards = $content['cards'] ?? [];
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        <div class="mt-14 grid gap-6 {{ $colClass }}">
            @foreach ($cards as $card)
                @php $hasImage = ($card['media_type'] ?? 'icon') === 'image' && ! empty($card['image']); @endphp
                <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-ink-900 transition-all duration-300 hover:-translate-y-1 hover:border-primary-500/50 hover:shadow-xl hover:shadow-primary-600/10">
                    @if ($hasImage)
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <picture>
                                <source srcset="{{ $card['image'] }}" type="image/webp">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] ?? '' }}" loading="lazy"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </picture>
                            <div class="absolute inset-0 bg-gradient-to-t from-ink-900 via-ink-900/20 to-transparent"></div>
                        </div>
                    @endif

                    <div class="flex flex-1 flex-col p-6">
                        @if (! $hasImage && ! empty($card['icon']))
                            <span class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-600/15 text-primary-500">
                                <x-dynamic-component :component="'lucide-'.$card['icon']" class="h-6 w-6" />
                            </span>
                        @endif

                        <h3 class="text-xl font-bold text-white">{{ $card['title'] ?? '' }}</h3>
                        @if (! empty($card['subtitle']))
                            <p class="mt-1 text-sm font-medium text-primary-400">{{ $card['subtitle'] }}</p>
                        @endif
                        @if (! empty($card['description']))
                            <p class="mt-3 text-sm leading-relaxed text-gray-400">{{ $card['description'] }}</p>
                        @endif

                        @if (! empty($card['features']))
                            <ul class="mt-4 flex flex-wrap gap-2">
                                @foreach ($card['features'] as $feature)
                                    <li class="rounded-full bg-white/5 px-3 py-1 text-xs font-medium text-gray-300">{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if (! empty($card['cta_label']))
                            <a href="{{ $card['href'] ?? '/' }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold uppercase tracking-wide text-primary-500 transition-colors hover:text-primary-400">
                                {{ $card['cta_label'] }}
                                <x-lucide-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
