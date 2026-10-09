@props(['section' => null, 'content' => []])

@php
    $bg = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $items = array_values(array_filter($content['items'] ?? [], fn ($item) => filled($item['title'] ?? null)));
@endphp

{{-- Voordelen ("waarom El Pablo"): kop links, argumenten rechts in een open
     raster met icoon — bewust lichter dan de kaarten-grids. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-24 lg:px-6">
        <div class="grid gap-14 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-4">
                <div class="lg:sticky lg:top-32">
                    <x-site.section-heading
                        align="left"
                        :eyebrow="$content['eyebrow'] ?? null"
                        :heading="$content['heading'] ?? null"
                        :intro="$content['intro'] ?? null"
                        :number="$content['number'] ?? null"
                    />
                </div>
            </div>

            @if ($items !== [])
                <div class="grid gap-x-10 gap-y-10 sm:grid-cols-2 lg:col-span-8">
                    @foreach ($items as $item)
                        <div class="border-t border-white/10 pt-6">
                            <x-site.section-icon :name="$item['icon'] ?? null" class="mb-4 inline-flex text-primary-500" size="h-7 w-7" />
                            <h3 class="text-lg font-bold text-white">{{ $item['title'] }}</h3>
                            @if (! empty($item['description']))
                                <p class="mt-2 text-sm leading-relaxed text-gray-400">{{ $item['description'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <x-site.section-closing :content="$content" />
    </div>
</x-site.sections.wrapper>
