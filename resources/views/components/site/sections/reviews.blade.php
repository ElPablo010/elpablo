@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $items = $content['items'] ?? [];
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($items as $item)
                <figure class="flex flex-col rounded-2xl border border-white/10 bg-ink-900 p-7">
                    <x-lucide-quote class="h-8 w-8 text-primary-600/50" />

                    @php $rating = (int) ($item['rating'] ?? 5); @endphp
                    @if ($rating)
                        <div class="mt-4 flex gap-0.5 text-primary-500">
                            @for ($i = 0; $i < $rating; $i++)
                                <x-lucide-star class="h-4 w-4 fill-current" />
                            @endfor
                        </div>
                    @endif

                    <blockquote class="mt-4 flex-1 text-gray-200">“{{ $item['quote'] ?? '' }}”</blockquote>

                    <figcaption class="mt-6 flex items-center gap-3 border-t border-white/10 pt-5">
                        @if (! empty($item['image']))
                            <picture>
                                <source srcset="{{ $item['image'] }}" type="image/webp">
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] ?? '' }}" loading="lazy"
                                     class="h-11 w-11 rounded-full object-cover">
                            </picture>
                        @else
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-primary-600/15 font-display text-lg text-primary-500">
                                {{ mb_substr($item['name'] ?? '?', 0, 1) }}
                            </span>
                        @endif
                        <div>
                            <div class="font-semibold text-white">{{ $item['name'] ?? '' }}</div>
                            @if (! empty($item['role']))
                                <div class="text-sm text-gray-400">{{ $item['role'] }}</div>
                            @endif
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
