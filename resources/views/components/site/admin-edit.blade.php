@props(['page' => null])

@php
    use App\Enums\UserRole;
    use App\Filament\Resources\Pages\PageResource;

    // Zelfde poort als Filament zelf hanteert (User::canAccessPanel): enkel de
    // Admin-rol. Een ingelogde niet-admin krijgt de knop dus niet te zien.
    $isAdmin = auth()->user()?->role === UserRole::Admin;

    // Staat er een pagina op het scherm, spring dan rechtstreeks naar haar
    // bewerkscherm; anders (404, preview) naar het paginaoverzicht.
    $href = $page?->exists
        ? PageResource::getUrl('edit', ['record' => $page])
        : PageResource::getUrl('index');
@endphp

@if ($isAdmin)
    {{-- Zweeft net onder de fixed menubalk en lijnt rechts uit met dezelfde
         max-w-7xl-container als de header. pointer-events-none op de wrapper
         zodat de onzichtbare balk geen kliks van de pagina eronder opvangt. --}}
    <div class="pointer-events-none fixed inset-x-0 top-28 z-40 px-4">
        <div class="mx-auto flex max-w-7xl justify-end">
            <a
                href="{{ $href }}"
                title="{{ $page?->exists ? 'Bewerk deze pagina in de admin' : 'Naar het paginaoverzicht in de admin' }}"
                class="pointer-events-auto inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-ink-900/80 text-white shadow-lg shadow-black/30 backdrop-blur-md transition-colors duration-200 hover:bg-primary-600 hover:text-white"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                <span class="sr-only">{{ $page?->exists ? 'Bewerk deze pagina' : 'Naar de admin' }}</span>
            </a>
        </div>
    </div>
@endif
