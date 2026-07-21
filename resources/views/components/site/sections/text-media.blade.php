@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $mediaType = $content['media_type'] ?? 'image';
    $mediaSide = $content['media_side'] ?? 'right';
    $ctas = $content['ctas'] ?? [];

    $btnClass = fn (?string $v) => match ($v) {
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        default => 'btn-primary',
    };
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-24 lg:grid-cols-2 lg:gap-16 lg:px-6">
        {{-- Tekst --}}
        <div class="{{ $mediaSide === 'left' ? 'lg:order-2' : '' }}">
            <div class="flex items-center gap-3">
                @if (! empty($content['number']))
                    <span class="font-display text-xl text-primary-500/80">{{ $content['number'] }}</span>
                @endif
                @if (! empty($content['eyebrow']))
                    <span class="eyebrow">{{ $content['eyebrow'] }}</span>
                @endif
            </div>
            @if (! empty($content['heading']))
                <h2 class="mt-4 font-display text-[2rem] leading-[0.95] text-white break-words sm:text-5xl">{{ $content['heading'] }}</h2>
            @endif
            @if (! empty($content['intro']))
                <div class="prose-invert-brand mt-5 text-lg leading-relaxed">{!! $content['intro'] !!}</div>
            @endif
            @if (! empty($ctas))
                <div class="mt-8 flex flex-wrap gap-4">
                    @foreach ($ctas as $cta)
                        <a href="{{ $cta['href'] ?? '/' }}" class="{{ $btnClass($cta['variant'] ?? 'primary') }}">{{ $cta['label'] ?? '' }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Media --}}
        <div class="{{ $mediaSide === 'left' ? 'lg:order-1' : '' }}">
            @if ($mediaType === 'video' && ! empty($content['video_url']))
                <video src="{{ $content['video_url'] }}" controls class="w-full rounded-2xl border border-white/10"></video>
            @elseif ($mediaType === 'images')
                <div class="grid grid-cols-2 gap-4">
                    @foreach ($content['images'] ?? [] as $img)
                        @if (! empty($img['src']))
                            <picture>
                                <source srcset="{{ $img['src'] }}" type="image/webp">
                                <img src="{{ $img['src'] }}" alt="{{ $img['alt'] ?? '' }}" loading="lazy"
                                     class="aspect-[3/4] w-full rounded-xl border border-white/10 object-cover {{ $loop->index % 2 ? 'mt-8' : '' }}">
                            </picture>
                        @endif
                    @endforeach
                </div>
            @elseif (! empty($content['media']['src']))
                <div class="relative">
                    <div class="pointer-events-none absolute -inset-3 -z-10 rounded-3xl bg-primary-600/20 blur-2xl"></div>
                    <picture>
                        <source srcset="{{ $content['media']['src'] }}" type="image/webp">
                        <img src="{{ $content['media']['src'] }}" alt="{{ $content['media']['alt'] ?? '' }}" loading="lazy"
                             class="w-full rounded-2xl border border-white/10 object-cover">
                    </picture>
                </div>
            @endif
        </div>
    </div>
</x-site.sections.wrapper>
