{{--
    "Bekijk website"-knop in de topbalk van de admin — altijd zichtbaar, op
    elke pagina. Filament's icon-button-component zorgt voor de juiste
    hover/focus-styling in licht én donker thema.
--}}
{{-- Inline-marge: de app-Tailwind wordt niet in de Filament-bundle geladen,
     dus ademruimte t.o.v. het accountmenu zetten we expliciet. --}}
<x-filament::icon-button
    icon="heroicon-o-eye"
    tag="a"
    :href="url('/')"
    target="_blank"
    rel="noopener"
    label="Bekijk website"
    tooltip="Bekijk website"
    color="primary"
    size="lg"
    style="margin-inline-start: 0.75rem;"
/>
