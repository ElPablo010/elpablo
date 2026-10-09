@props(['content' => []])

{{-- Afsluiter van probleemherkenning, voordelen en werkwijze: een verbindende
     boodschap (rich text) met optioneel één primaire knop. --}}
@php
    $href = \Webgoeroe\Core\Support\Url::resolveCtaHref($content, '');
    $hasCta = ! empty($content['cta_label']) && $href !== '';
@endphp

@if (! empty($content['closing']) || $hasCta)
    <div class="mx-auto mt-16 max-w-2xl text-center">
        @if (! empty($content['closing']))
            <div class="text-xl leading-snug text-gray-300 sm:text-2xl [&_strong]:font-bold [&_strong]:text-white [&_em]:text-primary-400">{!! $content['closing'] !!}</div>
        @endif

        @if ($hasCta)
            <a href="{{ \Webgoeroe\Core\Support\Locale::href($href) }}" class="btn-primary mt-8">
                {{ $content['cta_label'] }}
                <x-lucide-arrow-right class="h-4 w-4" />
            </a>
        @endif
    </div>
@endif
