@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
@endphp

{{--
    Kale tekstsectie: één leesbare prose-kolom, geen media. Bedoeld voor lopende
    tekst zoals juridische pagina's (cookiebeleid, privacybeleid). Bewust GEEN
    editorial sectienummer — dat hoort bij marketingsecties.
--}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-3xl px-4 py-24 lg:px-6">
        @if (! empty($content['eyebrow']))
            <p class="eyebrow mb-4">{{ $content['eyebrow'] }}</p>
        @endif
        @if (! empty($content['heading']))
            <h1 class="font-display text-4xl text-white break-words sm:text-5xl">{{ $content['heading'] }}</h1>
        @endif
        @if (! empty($content['body']))
            <div class="prose-invert-brand mt-8 leading-relaxed">{!! $content['body'] !!}</div>
        @endif
    </div>
</x-site.sections.wrapper>
