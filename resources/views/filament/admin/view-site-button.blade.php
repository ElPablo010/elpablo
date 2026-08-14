{{--
    "Bekijk website"-knop in de topbalk van de admin — altijd zichtbaar, op
    elke pagina. Filament's icon-button-component zorgt voor de juiste
    hover/focus-styling in licht én donker thema.
--}}
<x-filament::icon-button
    icon="heroicon-o-eye"
    tag="a"
    :href="url('/')"
    target="_blank"
    rel="noopener"
    label="Bekijk website"
    tooltip="Bekijk website"
    color="gray"
    size="lg"
/>
