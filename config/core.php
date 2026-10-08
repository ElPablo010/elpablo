<?php

/*
 * Site-basis (webgoeroe/core) — enkel wat op El Pablo afwijkt van de package.
 * De rest: vendor/webgoeroe/core/config/core.php.
 */

return [

    // Meertalig: NL op de root, EN/ES onder /en en /es (routing, hreflang,
    // sitemap-alternates en de taalschakelaar volgen automatisch).
    'locales' => [
        'nl' => 'NL',
        'en' => 'EN',
        'es' => 'ES',
    ],

    // Paden zonder taalvariant: wie daar van taal wisselt, landt op home.
    'language_switch' => [
        'unlocalized' => ['/t/', '/design/'],
    ],

    'routes' => [
        // Nooit via de catch-all: de eigen routes van deze site (events,
        // mixtape-detail, ticketstatus, Stripe) staan in routes/web.php.
        // 'mixtapes/' mét slash: de oude WordPress-URL's /mixtapes en
        // /mixtapes_categorie/* moeten wél op de catch-all landen, zodat de
        // redirect-middleware ze vangt (die draait pas als een route matcht).
        'exclude' => ['admin', 'login', 'livewire', 'storage', '_debugbar', 'design', 'events', 'mixtapes/', 't/', 'stripe'],
    ],

    // Donker nightlife-design: elke achtergrond is donker. De sleutels zijn
    // historisch (`white` = de standaard zwarte achtergrond): nooit hernoemen.
    'backgrounds' => [
        'default' => 'white',
        'options' => [
            'white' => ['label' => 'Standaard (zwart)', 'classes' => 'bg-ink-950 text-white'],
            'light' => ['label' => 'Donkergrijs', 'classes' => 'bg-ink-900 text-white'],
            'primary' => ['label' => 'Magenta (merkkleur)', 'classes' => 'bg-primary-600 text-white'],
            'dark' => ['label' => 'Diep zwart', 'classes' => 'bg-black text-white'],
            'transparent' => ['label' => 'Transparant', 'classes' => 'text-white'],
        ],
        'dark' => ['white', 'light', 'primary', 'dark', 'transparent'],
    ],

    'blocks' => [
        // Geen El Pablo-view (nog): agenda, probleemherkenning, voordelen en
        // werkwijze. De standaardviews van de core zijn licht en hun classes
        // zitten niet in de Tailwind-build van deze site.
        'disabled' => ['booking', 'problem_recognition', 'advantages', 'process_steps'],
        'options' => [
            'reviews' => [
                'columns' => false,
                'highlight' => false,
            ],
            'cards' => [
                'badge' => false,
            ],
            'text_media' => [
                'media_shape' => false,
            ],
            'cta' => [
                'note' => false,
            ],
        ],
    ],

    // Contact (core) + boeking (App\Livewire\Forms\BookingForm).
    'form_types' => [
        'booking' => [
            'label' => 'Boekingsformulier',
            'component' => 'forms.booking-form',
            'submission_label' => 'Boekingsaanvraag',
        ],
    ],

    // Favicon en "naam naast het logo" op de Header-pagina; "naam naast het
    // logo" in de footer; geen LinkedIn.
    'header' => [
        'favicon' => true,
        'show_name' => true,
    ],

    'footer' => [
        'linkedin' => false,
        'show_name' => true,
    ],

    // Lege tekstvelden ("<p></p>") gelden als leeg: core-standaard op elke
    // site (beslissing Pieter, 8 oktober 2026).

];
