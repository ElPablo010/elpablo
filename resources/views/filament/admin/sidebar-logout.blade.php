{{-- Uitlogknop onderaan de zijbalk (render hook SIDEBAR_FOOTER). Inline
     styling: de app-Tailwind wordt niet in de admin geladen en Filament's
     interne utility-classes zijn geen stabiel API. Kleur erft van de zijbalk,
     dus dit werkt in licht én donker thema. --}}
<form method="POST" action="{{ filament()->getLogoutUrl() }}" style="padding: 0.5rem 1rem 1rem;">
    @csrf
    <button
        type="submit"
        style="display: flex; width: 100%; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; background: none; border: none; border-radius: 0.5rem; font: inherit; font-size: 0.875rem; font-weight: 500; color: inherit; opacity: 0.7; cursor: pointer; transition: opacity 0.15s;"
        onmouseover="this.style.opacity = 1"
        onmouseout="this.style.opacity = 0.7"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="height: 1.25rem; width: 1.25rem; flex-shrink: 0;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
        </svg>
        Uitloggen
    </button>
</form>
