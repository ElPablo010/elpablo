@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $columns = (int) ($content['columns'] ?? 3);
    $colClass = ['2' => 'sm:grid-cols-2', '3' => 'sm:grid-cols-2 lg:grid-cols-3', '4' => 'sm:grid-cols-2 lg:grid-cols-4'][$columns] ?? 'sm:grid-cols-2 lg:grid-cols-3';
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

        <div class="mt-14 grid grid-cols-2 gap-3 {{ $colClass }}">
            @foreach ($items as $item)
                @if (! empty($item['image']))
                    <div class="group relative overflow-hidden rounded-xl {{ $loop->index % 5 === 0 ? 'row-span-2 aspect-[3/4]' : 'aspect-square' }}">
                        <picture>
                            <source srcset="{{ $item['image'] }}" type="image/webp">
                            <img src="{{ $item['image'] }}" alt="{{ $item['alt'] ?? '' }}" loading="lazy"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </picture>
                        <div class="absolute inset-0 bg-primary-600/0 transition-colors duration-300 group-hover:bg-primary-600/20"></div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
