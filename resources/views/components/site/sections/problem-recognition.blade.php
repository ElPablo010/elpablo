@props(['section' => null, 'content' => []])

@php
    $bg = \Webgoeroe\Core\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $problems = array_values(array_filter($content['problems'] ?? [], fn ($item) => filled($item['title'] ?? null)));
@endphp

{{-- Probleemherkenning: de bezoeker herkent zich in een situatie. Bewust geen
     knoppen per kaart; de enige CTA staat in de afsluiter. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-6xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        @if ($problems !== [])
            <div class="mt-14 grid gap-5 md:grid-cols-2">
                @foreach ($problems as $problem)
                    <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] p-7 transition-colors duration-300 hover:border-primary-500/40">
                        <div class="pointer-events-none absolute inset-y-0 left-0 w-px bg-gradient-to-b from-primary-500 via-primary-500/30 to-transparent opacity-60 transition-opacity group-hover:opacity-100"></div>

                        <div class="flex items-start gap-4">
                            <x-site.section-icon :name="$problem['icon'] ?? null" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-600/15 text-primary-500" />
                            <h3 class="pt-2 text-lg font-bold leading-snug text-white">{{ $problem['title'] }}</h3>
                        </div>

                        @if (! empty($problem['description']))
                            <p class="mt-4 flex-1 text-sm leading-relaxed text-gray-400">{{ $problem['description'] }}</p>
                        @endif

                        @if (! empty($problem['tags']))
                            <ul class="mt-5 flex flex-wrap gap-2">
                                @foreach ((array) $problem['tags'] as $tag)
                                    <li class="rounded-full border border-white/10 px-3 py-1 text-xs font-medium text-gray-400">{{ $tag }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <x-site.section-closing :content="$content" />
    </div>
</x-site.sections.wrapper>
