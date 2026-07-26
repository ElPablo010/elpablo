@php
    use App\Filament\Resources\Pages\PageResource;
    use App\Support\Locale;

    $record = $getRecord();
    // Relaties, geen losse query per rij — de tabel eager-load ze (zie PagesTable).
    $siblings = $record->translations->keyBy('locale');

    // Layout-kritische styling staat bewust inline: de app-Tailwind wordt niet in
    // de Filament-bundle geladen, dus utility-classes zouden hier niets doen.
    $badge = 'display:inline-block;padding:.125rem .4rem;border-radius:.375rem;font-size:.6875rem;font-weight:600;line-height:1.25rem;text-decoration:none;';
    $styles = [
        'published' => $badge.'background:rgba(16,185,129,.14);color:#10b981;',
        'draft' => $badge.'background:rgba(217,119,6,.14);color:#f59e0b;',
        'missing' => $badge.'background:rgba(148,163,184,.16);color:#94a3b8;',
    ];
@endphp

<div style="display:flex;gap:.25rem;flex-wrap:wrap;align-items:center;">
    @if ($record->translation_of === null)
        {{-- Bronpagina: toon één badge per andere taal. --}}
        @foreach (Locale::supported() as $locale)
            @continue($locale === $record->locale)

            @php $translation = $siblings[$locale] ?? null; @endphp

            @if ($translation)
                <a
                    href="{{ PageResource::getUrl('edit', ['record' => $translation]) }}"
                    style="{{ $styles[$translation->published ? 'published' : 'draft'] }}"
                    title="{{ $translation->title }}{{ $translation->published ? '' : ' (concept)' }} — klik om te bewerken"
                >{{ strtoupper($locale) }}</a>
            @else
                <span
                    style="{{ $styles['missing'] }}"
                    title="Nog geen {{ strtoupper($locale) }}-vertaling"
                >{{ strtoupper($locale) }}</span>
            @endif
        @endforeach
    @elseif ($source = $record->sourceTranslation)
        {{-- Vertaling: verwijs terug naar de bron, zodat de koppeling ook
             zichtbaar blijft wanneer je op "Alle talen" filtert. --}}
        <a
            href="{{ PageResource::getUrl('edit', ['record' => $source]) }}"
            style="font-size:.75rem;text-decoration:none;opacity:.75;"
            title="Vertaling van deze pagina"
        >&#8618; {{ $source->title }}</a>
    @else
        <span style="opacity:.5;">&mdash;</span>
    @endif
</div>
