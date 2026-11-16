@props(['page' => null, 'editUrl' => null])

@php
    use Webgoeroe\Core\Core;

    // Bewerkknop van de core (Core::adminBar), in de stijl van El Pablo.
    // Enkel voor wie het admin-panel mag zien; voor een bezoeker bestaat de knop
    // niet in de HTML. Een view die zelf weet waar zijn record in de admin woont
    // (bv. een event) geeft dat mee via editUrl: dat vervangt het hoofddoel.
    $links = Core::adminBar()->links($page);

    if ($links !== [] && $editUrl) {
        $links[0] = ['url' => $editUrl, 'label' => 'Bewerk in de admin', 'icon' => 'pencil'];
    }
@endphp

@if ($links !== [])
    {{-- Zweeft net onder de fixed menubalk en lijnt rechts uit met dezelfde
         max-w-7xl-container als de header. pointer-events-none op de wrapper
         zodat de onzichtbare balk geen kliks van de pagina eronder opvangt. --}}
    <div class="pointer-events-none fixed inset-x-0 top-28 z-40 px-4 print:hidden">
        <div class="mx-auto flex max-w-7xl flex-row-reverse justify-start gap-2">
            @foreach ($links as $link)
                <a
                    href="{{ $link['url'] }}"
                    title="{{ $link['label'] }}"
                    aria-label="{{ $link['label'] }}"
                    class="pointer-events-auto inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-ink-900/80 text-white shadow-lg shadow-black/30 backdrop-blur-md transition-colors duration-200 hover:bg-primary-600 hover:text-white"
                >
                    <x-dynamic-component :component="'lucide-'.$link['icon']" class="size-4" />
                </a>
            @endforeach
        </div>
    </div>
@endif
