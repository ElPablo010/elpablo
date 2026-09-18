<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // De Anthropic-key komt primair uit Instellingen → Algemeen (database), zodat
    // de klant hem zelf kan beheren. Deze env-fallback is er voor CI/lokaal.
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),

        /*
         * Het model per soort klus, niet per klasse. Wie het hardcodeert waar
         * hij het gebruikt, krijgt na drie features drie modellen die niemand
         * koos, en de waarde die er toevallig stond blijft jaren meelopen.
         *
         * `reasoning` is denkwerk: SEO-advies en verbeteracties, waar de
         * kwaliteit telt. `bulk` is sleurwerk: korte strings vertalen, waar
         * niets te bedenken valt. Die stond hier eerder op `claude-opus-5` —
         * het duurste model dat er is, voor het omzetten van "Lees meer".
         * Geen beslissing, een default.
         *
         * Let op bij het wisselen van `bulk` — Haiku kent de `effort`-parameter
         * niet en weigert de héle request met een 400 als je 'm meestuurt.
         * ClaudeTranslator::supportsEffort() vangt dat af.
         */
        'models' => [
            'reasoning' => env('ANTHROPIC_MODEL_REASONING', 'claude-sonnet-5'),
            'bulk' => env('ANTHROPIC_MODEL_BULK', 'claude-haiku-4-5-20251001'),
        ],
    ],

    // Kit (e-maillijst): key primair uit Instellingen → E-mailmarketing; .env is terugval.
    'kit' => [
        'api_key' => env('KIT_API_KEY'),
    ],

    // Stripe-secrets komen primair uit Instellingen → Betalingen (database);
    // deze env-fallbacks zijn er voor CI/lokaal — zelfde patroon als Anthropic.
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
