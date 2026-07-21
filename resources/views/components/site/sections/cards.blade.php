@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $columns = (int) ($content['columns'] ?? 3);
    $colClass = ['2' => 'md:grid-cols-2', '3' => 'md:grid-cols-3', '4' => 'md:grid-cols-4'][$columns] ?? 'md:grid-cols-3';
    $cards = $content['cards'] ?? [];
@endphp

{{-- Neutrale placeholder. Per project vrij te herontwerpen. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-20">
        @if (! empty($content['heading']))
            <div class="mx-auto mb-12 max-w-2xl text-center">
                @if (! empty($content['eyebrow']))
                    <p class="mb-2 text-sm font-medium uppercase tracking-wide opacity-70">{{ $content['eyebrow'] }}</p>
                @endif
                <h2 class="text-3xl font-bold">{{ $content['heading'] }}</h2>
                @if (! empty($content['intro']))
                    <div class="prose mx-auto mt-3">{!! $content['intro'] !!}</div>
                @endif
            </div>
        @endif

        <div class="grid gap-6 {{ $colClass }}">
            @foreach ($cards as $card)
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    @if (($card['media_type'] ?? 'icon') === 'image' && ! empty($card['image']))
                        <img src="{{ $card['image'] }}" alt="{{ $card['title'] ?? '' }}" class="mb-4 aspect-video w-full rounded-lg object-cover">
                    @endif
                    <h3 class="text-lg font-semibold">{{ $card['title'] ?? '' }}</h3>
                    @if (! empty($card['subtitle']))
                        <p class="text-sm text-gray-500">{{ $card['subtitle'] }}</p>
                    @endif
                    @if (! empty($card['description']))
                        <p class="mt-2 text-sm text-gray-600">{{ $card['description'] }}</p>
                    @endif
                    @if (! empty($card['cta_label']))
                        <a href="{{ $card['href'] ?? '/' }}" class="mt-4 inline-block text-sm font-medium text-primary-600">{{ $card['cta_label'] }}</a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
