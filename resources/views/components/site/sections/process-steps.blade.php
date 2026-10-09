@props(['section' => null, 'content' => []])

@php
    $bg = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $steps = array_values(array_filter($content['steps'] ?? [], fn ($item) => filled($item['title'] ?? null)));
    $colClass = match (count($steps)) {
        1 => '',
        2, 4 => 'md:grid-cols-2',
        default => 'md:grid-cols-3',
    };
@endphp

{{-- Werkwijze: grote flyer-nummers (volgen de volgorde in de admin) met een
     magenta lijn die de stappen verbindt. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-6xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        @if ($steps !== [])
            <ol class="mt-14 grid gap-12 md:gap-10 {{ $colClass }}">
                @foreach ($steps as $index => $step)
                    <li>
                        <div class="flex items-center gap-4">
                            <span class="font-display text-6xl leading-none text-primary-500" aria-hidden="true">{{ sprintf('%02d', $index + 1) }}</span>
                            <span class="h-px flex-1 bg-gradient-to-r from-primary-500/60 to-transparent"></span>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-white">{{ $step['title'] }}</h3>
                        @if (! empty($step['description']))
                            <p class="mt-2 text-sm leading-relaxed text-gray-400">{{ $step['description'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        <x-site.section-closing :content="$content" />
    </div>
</x-site.sections.wrapper>
