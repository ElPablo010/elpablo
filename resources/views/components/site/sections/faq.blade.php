@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $items = $content['items'] ?? [];
@endphp

{{-- Neutrale placeholder. Per project vrij te herontwerpen. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-3xl px-4 py-20">
        @if (! empty($content['heading']))
            <div class="mb-10 text-center">
                @if (! empty($content['eyebrow']))
                    <p class="mb-2 text-sm font-medium uppercase tracking-wide opacity-70">{{ $content['eyebrow'] }}</p>
                @endif
                <h2 class="text-3xl font-bold">{{ $content['heading'] }}</h2>
                @if (! empty($content['intro']))
                    <div class="prose mx-auto mt-3">{!! $content['intro'] !!}</div>
                @endif
            </div>
        @endif

        <div class="divide-y divide-gray-200">
            @foreach ($items as $item)
                <details class="group py-4">
                    <summary class="cursor-pointer list-none font-medium">{{ $item['question'] ?? '' }}</summary>
                    <div class="prose mt-2 max-w-none text-sm text-gray-600">{!! $item['answer'] ?? '' !!}</div>
                </details>
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
