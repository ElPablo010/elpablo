<x-filament-panels::page>
    {{-- Layout-kritische styling staat inline: de app-Tailwind wordt niet in de
         Filament-bundle geladen (project-conventie). --}}

    <div style="display: grid; gap: 1.5rem;" wire:poll.10s>
        {{-- Event + teller --}}
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: end; justify-content: space-between;">
            <div style="min-width: 260px;">
                <label for="scan-event" style="display: block; font-size: .875rem; font-weight: 500; margin-bottom: .375rem;">Event</label>
                <select id="scan-event" wire:model.live="eventId"
                        style="width: 100%; border-radius: .5rem; border: 1px solid rgba(120,120,120,.4); padding: .5rem .75rem; background: transparent; cursor: pointer;">
                    <option value="">— Kies een event —</option>
                    @foreach ($this->eventOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 2rem; font-weight: 700; line-height: 1;">
                    {{ $this->stats['checked_in'] }} / {{ $this->stats['total'] }}
                </div>
                <div style="font-size: .8rem; opacity: .7;">ingecheckt</div>
            </div>
        </div>

        {{-- Scanresultaat --}}
        @if ($lastResult)
            @php
                $ok = $lastResult['status'] === 'ok';
                $warn = $lastResult['status'] === 'already';
                [$bg, $border] = $ok
                    ? ['rgba(34,197,94,.12)', 'rgba(34,197,94,.6)']
                    : ($warn ? ['rgba(245,158,11,.12)', 'rgba(245,158,11,.6)'] : ['rgba(239,68,68,.12)', 'rgba(239,68,68,.6)']);
            @endphp
            <div style="border-radius: .75rem; border: 2px solid {{ $border }}; background: {{ $bg }}; padding: 1rem 1.25rem;" role="status">
                <p style="font-size: 1.15rem; font-weight: 700; margin: 0;">
                    {{ $ok ? '✅' : ($warn ? '⚠️' : '⛔') }} {{ $lastResult['message'] }}
                </p>
                @if ($lastResult['name'])
                    <p style="margin: .25rem 0 0; opacity: .8;">
                        {{ $lastResult['name'] }}@if ($lastResult['type']) — {{ $lastResult['type'] }}@endif
                    </p>
                @endif
            </div>
        @endif

        {{-- Camera --}}
        <div x-data="ticketScanner($wire)" x-init="init()" wire:ignore>
            <div id="qr-reader" style="max-width: 480px; border-radius: .75rem; overflow: hidden;"></div>
            <p x-show="!started" style="opacity: .7; font-size: .875rem; margin-top: .5rem;">
                Camera wordt gestart… Geen toegang? Gebruik het invoerveld hieronder.
            </p>
        </div>

        {{-- Handmatige invoer (fallback) --}}
        <form onsubmit="event.preventDefault(); const i = this.querySelector('input'); if (i.value) { this.dispatchEvent(new CustomEvent('manual-scan', {bubbles: true, detail: i.value})); i.value = ''; }"
              x-data x-on:manual-scan="$wire.checkIn($event.detail)"
              style="display: flex; gap: .75rem; max-width: 480px;">
            <input type="text" placeholder="Token of ticket-URL handmatig invoeren"
                   style="flex: 1; border-radius: .5rem; border: 1px solid rgba(120,120,120,.4); padding: .5rem .75rem; background: transparent;">
            <x-filament::button type="submit">Controleren</x-filament::button>
        </form>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        function ticketScanner($wire) {
            return {
                started: false,
                lastCode: null,
                lastAt: 0,

                init() {
                    if (! window.Html5Qrcode) {
                        return;
                    }

                    const scanner = new Html5Qrcode('qr-reader');
                    scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 240, height: 240 } },
                        (decoded) => {
                            // Debounce: dezelfde code max. één keer per 3 seconden.
                            const now = Date.now();
                            if (decoded === this.lastCode && now - this.lastAt < 3000) {
                                return;
                            }
                            this.lastCode = decoded;
                            this.lastAt = now;
                            $wire.checkIn(decoded);
                        },
                        () => {},
                    ).then(() => { this.started = true; }).catch(() => { this.started = false; });
                },
            };
        }
    </script>
</x-filament-panels::page>
