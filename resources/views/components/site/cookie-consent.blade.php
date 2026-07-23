{{--
    Cookie-consent banner + voorkeuren-paneel. Puur client-side (Alpine): de
    keuze wordt bewaard in een cookie `cookie_consent` (180 dagen).

    ── Gedragscontract (NIET wijzigen bij het herstylen) ─────────────────────
    Tracking-scripts (Google Analytics, Meta Pixel, LinkedIn Insight Tag, …)
    checken vóór het laden:

        window.cookieConsent.has('analytics' | 'marketing' | 'functional')

    of luisteren naar het window-event `cookie-consent-changed`
    (`event.detail` = hetzelfde object). `functional` is altijd true.

    Heropenen vanaf elders (bv. de "Cookie-instellingen"-link in de footer):

        window.dispatchEvent(new CustomEvent('open-cookie-preferences'))

    ── Animatie: de banner fadet als ÉÉN blok ────────────────────────────────
    Drie dingen horen bij elkaar; laat ze staan, anders valt de banner
    stuk-voor-stuk weg of komt de inhoud een tel later na:
      1. enter- én leave-transitie op deze container (leave vergeten = harde knip);
      2. alleen `transition-opacity` — geen translate, dus geen naar-beneden-schuiven;
      3. het voorkeuren-paneel hieronder heeft bewust GEEN eigen x-transition, en
         `detailed` reset pas ná de leave-duur (zie `saveConsent`).
--}}
<div
    x-data="{
        open: false,
        detailed: false,
        prefs: { analytics: true, marketing: true },

        init() {
            const stored = this.readConsent();
            if (stored) {
                this.prefs.analytics = !! stored.analytics;
                this.prefs.marketing = !! stored.marketing;
                this.applyConsent(stored);
            } else {
                this.open = true;
            }

            window.addEventListener('open-cookie-preferences', () => {
                this.detailed = true;
                this.open = true;
            });
        },

        readConsent() {
            const match = document.cookie.match(/(?:^|; )cookie_consent=([^;]*)/);
            if (! match) return null;
            try {
                return JSON.parse(decodeURIComponent(match[1]));
            } catch (e) {
                return null;
            }
        },

        applyConsent(consent) {
            window.cookieConsent = {
                functional: true,
                analytics: !! consent.analytics,
                marketing: !! consent.marketing,
                has(category) {
                    return category === 'functional' ? true : !! this[category];
                },
            };
            window.dispatchEvent(new CustomEvent('cookie-consent-changed', { detail: window.cookieConsent }));
        },

        saveConsent(consent) {
            const value = encodeURIComponent(JSON.stringify(consent));
            const maxAge = 60 * 60 * 24 * 180; // 180 dagen
            document.cookie = `cookie_consent=${value}; max-age=${maxAge}; path=/; samesite=lax`;
            this.applyConsent(consent);
            this.open = false;
            // Pas resetten nadat de banner is uitgefade, anders zie je het
            // voorkeuren-paneel eerst apart wegvallen tijdens de leave-transitie.
            setTimeout(() => this.detailed = false, 300);
        },

        acceptAll() {
            this.prefs.analytics = true;
            this.prefs.marketing = true;
            this.saveConsent({ functional: true, analytics: true, marketing: true });
        },

        rejectAll() {
            this.prefs.analytics = false;
            this.prefs.marketing = false;
            this.saveConsent({ functional: true, analytics: false, marketing: false });
        },

        savePreferences() {
            this.saveConsent({ functional: true, analytics: this.prefs.analytics, marketing: this.prefs.marketing });
        },
    }"
    x-show="open"
    x-cloak
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-x-0 bottom-0 z-[100] flex justify-center px-4 pb-4 sm:px-6"
    role="dialog"
    aria-modal="true"
    aria-label="Cookievoorkeuren"
>
    <div class="w-full max-w-2xl rounded-2xl border border-white/10 bg-ink-900 p-6 shadow-2xl shadow-black/50">
        <div class="mb-3 flex items-start gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-600/15 text-primary-500">
                <x-lucide-cookie class="h-4.5 w-4.5" />
            </span>
            <div>
                <div class="font-semibold text-white">{{ __('Wij respecteren jouw privacy') }}</div>
                <p class="mt-1 text-sm text-gray-400">
                    {{ __('We gebruiken cookies om onze site goed te laten werken en, met jouw toestemming, om bezoekersstatistieken te verzamelen. Lees ons') }}
                    <a href="{{ \App\Support\Locale::href('/cookiebeleid') }}" class="text-primary-400 hover:underline">{{ __('cookiebeleid') }}</a> {{ __('voor meer info.') }}
                </p>
            </div>
        </div>

        <div
            {{-- Geen eigen x-transition: het paneel moet mee in-/uitfaden met de
                 banner als geheel, niet als een tweede animatie erbovenop. --}}
            x-show="detailed"
            x-cloak
            class="mt-4 space-y-3 rounded-xl border border-white/10 bg-ink-950 p-4"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-medium text-gray-200">{{ __('Functionele cookies') }}</div>
                    <div class="text-xs text-gray-500">{{ __('Noodzakelijk voor de werking van de website') }}</div>
                </div>
                <span class="text-xs font-medium text-gray-500">{{ __('Altijd actief') }}</span>
            </div>
            @foreach ([['analytics', 'Analytische cookies', 'Helpen ons begrijpen hoe bezoekers de site gebruiken'], ['marketing', 'Marketing cookies', 'Worden gebruikt om gepersonaliseerde advertenties te tonen']] as [$key, $label, $description])
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-200">{{ __($label) }}</div>
                        <div class="text-xs text-gray-500">{{ __($description) }}</div>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="prefs.{{ $key }}"
                        @click="prefs.{{ $key }} = ! prefs.{{ $key }}"
                        class="relative h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors"
                        :class="prefs.{{ $key }} ? 'bg-primary-600' : 'bg-white/15'"
                    >
                        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="prefs.{{ $key }} ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <button
                type="button"
                @click="detailed = ! detailed"
                class="flex cursor-pointer items-center gap-1 text-sm font-medium text-gray-400 transition-colors hover:text-white"
            >
                <x-lucide-chevron-up x-show="detailed" class="h-4 w-4" />
                <x-lucide-chevron-down x-show="! detailed" class="h-4 w-4" />
                <span x-text="detailed ? @js(__('Minder')) : @js(__('Voorkeuren aanpassen'))"></span>
            </button>

            <div class="flex flex-wrap gap-2">
                <button x-show="detailed" type="button" @click="savePreferences()" class="cursor-pointer rounded-lg border border-white/15 px-4 py-2 text-sm font-medium text-gray-200 transition-colors hover:bg-white/5">
                    {{ __('Voorkeuren opslaan') }}
                </button>
                <button type="button" @click="rejectAll()" class="cursor-pointer rounded-lg border border-white/15 px-4 py-2 text-sm font-medium text-gray-200 transition-colors hover:bg-white/5">
                    {{ __('Weigeren') }}
                </button>
                <button type="button" @click="acceptAll()" class="cursor-pointer rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-500">
                    {{ __('Accepteren') }}
                </button>
            </div>
        </div>
    </div>
</div>
