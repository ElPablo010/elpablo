@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $columns = (int) ($content['columns'] ?? 3);
    $colClass = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-3', '4' => 'sm:grid-cols-4'][$columns] ?? 'sm:grid-cols-3';
    $items = $content['items'] ?? [];
@endphp

{{-- Neutrale placeholder. Per project vrij te herontwerpen. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-20">
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

        <div class="grid grid-cols-2 gap-4 {{ $colClass }}">
            @foreach ($items as $item)
                @if (! empty($item['image']))
                    <img src="{{ $item['image'] }}" alt="{{ $item['alt'] ?? '' }}" class="aspect-square w-full rounded-lg object-cover" loading="lazy">
                @endif
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
