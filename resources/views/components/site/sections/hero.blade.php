@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $image = $content['image'] ?? [];
    $ctas = $content['ctas'] ?? [];
@endphp

{{-- Neutrale placeholder-hero. Per project vrij te herontwerpen. --}}
<x-site.sections.wrapper :content="$content" class="relative {{ $bg }}">
    @if (! empty($image['src']))
        <img
            src="{{ $image['src'] }}"
            alt="{{ $image['alt'] ?? '' }}"
            class="absolute inset-0 h-full w-full object-cover"
            style="object-position: {{ $image['position'] ?? 'center 50%' }};"
        >
        <div class="absolute inset-0 bg-black/40"></div>
    @endif

    <div class="relative mx-auto max-w-4xl px-4 py-24 text-center {{ ! empty($image['src']) ? 'text-white' : '' }}">
        @if (! empty($content['eyebrow']))
            <p class="mb-3 text-sm font-medium uppercase tracking-wide opacity-80">{{ $content['eyebrow'] }}</p>
        @endif
        @if (! empty($content['heading']))
            <h1 class="text-4xl font-bold md:text-5xl">{{ $content['heading'] }}</h1>
        @endif
        @if (! empty($content['subtitle']))
            <div class="prose mx-auto mt-4 max-w-2xl opacity-90">{!! $content['subtitle'] !!}</div>
        @endif

        @if (! empty($ctas))
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @foreach ($ctas as $cta)
                    <a href="{{ $cta['href'] ?? '/' }}" class="rounded-full bg-primary-600 px-6 py-3 text-sm font-medium text-white">{{ $cta['label'] ?? '' }}</a>
                @endforeach
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
