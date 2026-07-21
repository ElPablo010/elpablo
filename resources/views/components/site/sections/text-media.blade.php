@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $mediaType = $content['media_type'] ?? 'image';
    $mediaSide = $content['media_side'] ?? 'right';
    $ctas = $content['ctas'] ?? [];
@endphp

{{-- Neutrale placeholder. Per project vrij te herontwerpen. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-20 md:grid-cols-2">
        <div class="{{ $mediaSide === 'left' ? 'md:order-2' : '' }}">
            @if (! empty($content['eyebrow']))
                <p class="mb-2 text-sm font-medium uppercase tracking-wide opacity-70">{{ $content['eyebrow'] }}</p>
            @endif
            @if (! empty($content['heading']))
                <h2 class="text-3xl font-bold">{{ $content['heading'] }}</h2>
            @endif
            @if (! empty($content['intro']))
                <div class="prose mt-4 max-w-none">{!! $content['intro'] !!}</div>
            @endif
            @if (! empty($ctas))
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($ctas as $cta)
                        <a href="{{ $cta['href'] ?? '/' }}" class="rounded-full bg-primary-600 px-5 py-2.5 text-sm font-medium text-white">{{ $cta['label'] ?? '' }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="{{ $mediaSide === 'left' ? 'md:order-1' : '' }}">
            @if ($mediaType === 'video' && ! empty($content['video_url']))
                <video src="{{ $content['video_url'] }}" controls class="w-full rounded-xl"></video>
            @elseif ($mediaType === 'images')
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($content['images'] ?? [] as $img)
                        @if (! empty($img['src']))
                            <img src="{{ $img['src'] }}" alt="{{ $img['alt'] ?? '' }}" class="w-full rounded-lg object-cover">
                        @endif
                    @endforeach
                </div>
            @elseif (! empty($content['media']['src']))
                <img src="{{ $content['media']['src'] }}" alt="{{ $content['media']['alt'] ?? '' }}" class="w-full rounded-xl object-cover">
            @endif
        </div>
    </div>
</x-site.sections.wrapper>
