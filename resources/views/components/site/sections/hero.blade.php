@props(['section' => null, 'content' => []])

@php
    $image = $content['image'] ?? [];
    $ctas = $content['ctas'] ?? [];

    // Knop-variant → utility-klasse (zie app.css). Eerste CTA valt terug op primair.
    $btnClass = fn (?string $v) => match ($v) {
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        default => 'btn-primary',
    };
@endphp

<x-site.sections.wrapper :content="$content" class="relative flex min-h-[92vh] items-end overflow-hidden bg-ink-950">
    @if (! empty($image['src']))
        <picture>
            <source srcset="{{ $image['src'] }}" type="image/webp">
            <img
                src="{{ $image['src'] }}"
                alt="{{ $image['alt'] ?? '' }}"
                class="absolute inset-0 h-full w-full object-cover"
                style="object-position: {{ $image['position'] ?? 'center 50%' }};"
                fetchpriority="high"
            >
        </picture>
    @endif

    {{-- Scrim: donker onderaan (leesbaarheid) + magenta accent, geen glass pill. --}}
    <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/70 to-ink-950/30"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-ink-950/80 via-transparent to-transparent"></div>
    <div class="pointer-events-none absolute -bottom-40 -left-20 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>

    <div class="relative mx-auto w-full min-w-0 max-w-7xl px-4 pb-20 pt-40 lg:px-6 lg:pb-28">
        <div class="max-w-3xl">
            @if (! empty($content['eyebrow']))
                <p class="eyebrow mb-5">{{ $content['eyebrow'] }}</p>
            @endif

            @if (! empty($content['heading']))
                <h1 class="font-display text-[2.6rem] leading-[0.95] text-white break-words sm:text-6xl lg:text-8xl">{{ $content['heading'] }}</h1>
            @endif

            @if (! empty($content['subtitle']))
                <div class="prose-invert-brand mt-6 max-w-xl text-lg leading-relaxed">{!! $content['subtitle'] !!}</div>
            @endif

            @if (! empty($ctas))
                <div class="mt-9 flex flex-wrap gap-4">
                    @foreach ($ctas as $cta)
                        <a href="{{ \App\Support\Locale::href($cta['href'] ?? '/') }}" class="{{ $btnClass($cta['variant'] ?? 'primary') }}">
                            {{ $cta['label'] ?? '' }}
                            @if (($cta['variant'] ?? 'primary') === 'primary')
                                <x-lucide-arrow-right class="h-4 w-4" />
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Scroll-hint --}}
    <div class="absolute bottom-6 left-1/2 hidden -translate-x-1/2 animate-bounce text-white/40 lg:block">
        <x-lucide-chevron-down class="h-6 w-6" />
    </div>
</x-site.sections.wrapper>
