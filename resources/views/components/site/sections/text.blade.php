@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);

    // Kopniveau volgt de plaats op de pagina: staat deze sectie bovenaan (juridische
    // pagina's, die geen hero hebben), dan is dit de H1. Komt er een sectie vóór —
    // bv. een hero op een SEO-landingspagina — dan is de H1 al vergeven en zakt deze
    // kop naar H2, zodat een pagina nooit twee H1's krijgt.
    $headingTag = ($section?->position ?? 0) === 0 ? 'h1' : 'h2';
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
            <{{ $headingTag }} class="font-display text-4xl text-white break-words sm:text-5xl">{{ $content['heading'] }}</{{ $headingTag }}>
        @endif
        @if (! empty($content['body']))
            <div class="prose-invert-brand mt-8 leading-relaxed">{!! $content['body'] !!}</div>
        @endif
    </div>
</x-site.sections.wrapper>
