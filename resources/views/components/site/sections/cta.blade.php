@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? 'primary');
    $ctas = $content['ctas'] ?? [];
@endphp

{{-- Neutrale placeholder. Per project vrij te herontwerpen. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-3xl px-4 py-20 text-center">
        @if (! empty($content['eyebrow']))
            <p class="mb-2 text-sm font-medium uppercase tracking-wide opacity-80">{{ $content['eyebrow'] }}</p>
        @endif
        @if (! empty($content['heading']))
            <h2 class="text-3xl font-bold">{{ $content['heading'] }}</h2>
        @endif
        @if (! empty($content['intro']))
            <div class="prose mx-auto mt-3 opacity-90">{!! $content['intro'] !!}</div>
        @endif

        @if (! empty($ctas))
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @foreach ($ctas as $cta)
                    <a href="{{ $cta['href'] ?? '/' }}" class="rounded-full bg-white px-6 py-3 text-sm font-medium text-gray-900">{{ $cta['label'] ?? '' }}</a>
                @endforeach
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
