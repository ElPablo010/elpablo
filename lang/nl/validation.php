<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Nederlandse validatieberichten
|--------------------------------------------------------------------------
|
| De admin-UI is Nederlands (zie CLAUDE.md), dus ook de foutmeldingen die
| Filament toont. Filament levert zijn eigen labels al in het Nederlands mee
| (onder vendor/filament, per pakket in resources/lang/nl); dit bestand dekt
| de validatieregels van Laravel zelf.
|
| Onder 'attributes' staan de veldnamen die in de berichten ingevuld worden.
| Filament gebruikt normaal het label van het veld, maar voor velden zonder
| duidelijk label valt het terug op de sleutelnaam — daarom een set gangbare
| namen uit dit project.
|
*/

return [
    'accepted' => 'Het :attribute-veld moet geaccepteerd worden.',
    'accepted_if' => 'Het :attribute-veld moet geaccepteerd worden wanneer :other gelijk is aan :value.',
    'active_url' => 'Het :attribute-veld is geen geldige URL.',
    'after' => 'Het :attribute-veld moet een datum na :date zijn.',
    'after_or_equal' => 'Het :attribute-veld moet een datum na of gelijk aan :date zijn.',
    'alpha' => 'Het :attribute-veld mag alleen letters bevatten.',
    'alpha_dash' => 'Het :attribute-veld mag alleen letters, cijfers, koppeltekens en underscores bevatten.',
    'alpha_num' => 'Het :attribute-veld mag alleen letters en cijfers bevatten.',
    'any_of' => 'Het :attribute-veld is ongeldig.',
    'array' => 'Het :attribute-veld moet een reeks zijn.',
    'ascii' => 'Het :attribute-veld mag alleen alfanumerieke tekens en symbolen van één byte bevatten.',
    'before' => 'Het :attribute-veld moet een datum vóór :date zijn.',
    'before_or_equal' => 'Het :attribute-veld moet een datum vóór of gelijk aan :date zijn.',
    'between' => [
        'array' => 'Het :attribute-veld moet tussen :min en :max items bevatten.',
        'file' => 'Het :attribute-veld moet tussen :min en :max kilobytes zijn.',
        'numeric' => 'Het :attribute-veld moet tussen :min en :max liggen.',
        'string' => 'Het :attribute-veld moet tussen :min en :max tekens bevatten.',
    ],
    'boolean' => 'Het :attribute-veld moet ja of nee zijn.',
    'can' => 'Het :attribute-veld bevat een niet-toegestane waarde.',
    'confirmed' => 'De bevestiging van het :attribute-veld komt niet overeen.',
    'contains' => 'Er ontbreekt een waarde in het :attribute-veld.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => 'Het :attribute-veld is geen geldige datum.',
    'date_equals' => 'Het :attribute-veld moet een datum gelijk aan :date zijn.',
    'date_format' => 'Het :attribute-veld komt niet overeen met het formaat :format.',
    'decimal' => 'Het :attribute-veld moet :decimal decimalen hebben.',
    'declined' => 'Het :attribute-veld moet geweigerd worden.',
    'declined_if' => 'Het :attribute-veld moet geweigerd worden wanneer :other gelijk is aan :value.',
    'different' => 'De velden :attribute en :other moeten verschillend zijn.',
    'digits' => 'Het :attribute-veld moet :digits cijfers bevatten.',
    'digits_between' => 'Het :attribute-veld moet tussen :min en :max cijfers bevatten.',
    'dimensions' => 'Het :attribute-veld heeft ongeldige afbeeldingsafmetingen.',
    'distinct' => 'Het :attribute-veld heeft een dubbele waarde.',
    'doesnt_contain' => 'Het :attribute-veld mag geen van de volgende bevatten: :values.',
    'doesnt_end_with' => 'Het :attribute-veld mag niet eindigen op een van de volgende: :values.',
    'doesnt_start_with' => 'Het :attribute-veld mag niet beginnen met een van de volgende: :values.',
    'email' => 'Het :attribute-veld moet een geldig e-mailadres zijn.',
    'encoding' => 'Het :attribute-veld heeft een ongeldige tekencodering.',
    'ends_with' => 'Het :attribute-veld moet eindigen op een van de volgende: :values.',
    'enum' => 'De geselecteerde :attribute is ongeldig.',
    'exists' => 'De geselecteerde :attribute is ongeldig.',
    'extensions' => 'Het :attribute-veld moet een van de volgende extensies hebben: :values.',
    'file' => 'Het :attribute-veld moet een bestand zijn.',
    'filled' => 'Het :attribute-veld moet een waarde bevatten.',
    'gt' => [
        'array' => 'Het :attribute-veld moet meer dan :value items bevatten.',
        'file' => 'Het :attribute-veld moet groter dan :value kilobytes zijn.',
        'numeric' => 'Het :attribute-veld moet groter dan :value zijn.',
        'string' => 'Het :attribute-veld moet meer dan :value tekens bevatten.',
    ],
    'gte' => [
        'array' => 'Het :attribute-veld moet :value items of meer bevatten.',
        'file' => 'Het :attribute-veld moet groter dan of gelijk aan :value kilobytes zijn.',
        'numeric' => 'Het :attribute-veld moet groter dan of gelijk aan :value zijn.',
        'string' => 'Het :attribute-veld moet :value tekens of meer bevatten.',
    ],
    'hex_color' => 'Het :attribute-veld moet een geldige hexadecimale kleurcode zijn.',
    'image' => 'Het :attribute-veld moet een afbeelding zijn.',
    'in' => 'De geselecteerde :attribute is ongeldig.',
    'in_array' => 'Het :attribute-veld moet voorkomen in :other.',
    'in_array_keys' => 'Het :attribute-veld moet minstens een van de volgende sleutels bevatten: :values.',
    'integer' => 'Het :attribute-veld moet een geheel getal zijn.',
    'ip' => 'Het :attribute-veld moet een geldig IP-adres zijn.',
    'ipv4' => 'Het :attribute-veld moet een geldig IPv4-adres zijn.',
    'ipv6' => 'Het :attribute-veld moet een geldig IPv6-adres zijn.',
    'json' => 'Het :attribute-veld moet een geldige JSON-tekst zijn.',
    'list' => 'Het :attribute-veld moet een lijst zijn.',
    'lowercase' => 'Het :attribute-veld mag alleen kleine letters bevatten.',
    'lt' => [
        'array' => 'Het :attribute-veld moet minder dan :value items bevatten.',
        'file' => 'Het :attribute-veld moet kleiner dan :value kilobytes zijn.',
        'numeric' => 'Het :attribute-veld moet kleiner dan :value zijn.',
        'string' => 'Het :attribute-veld moet minder dan :value tekens bevatten.',
    ],
    'lte' => [
        'array' => 'Het :attribute-veld mag niet meer dan :value items bevatten.',
        'file' => 'Het :attribute-veld moet kleiner dan of gelijk aan :value kilobytes zijn.',
        'numeric' => 'Het :attribute-veld moet kleiner dan of gelijk aan :value zijn.',
        'string' => 'Het :attribute-veld moet :value tekens of minder bevatten.',
    ],
    'mac_address' => 'Het :attribute-veld moet een geldig MAC-adres zijn.',
    'max' => [
        'array' => 'Het :attribute-veld mag niet meer dan :max items bevatten.',
        'file' => 'Het :attribute-veld mag niet groter dan :max kilobytes zijn.',
        'numeric' => 'Het :attribute-veld mag niet groter dan :max zijn.',
        'string' => 'Het :attribute-veld mag niet meer dan :max tekens bevatten.',
    ],
    'max_digits' => 'Het :attribute-veld mag niet meer dan :max cijfers bevatten.',
    'mimes' => 'Het :attribute-veld moet een bestand van het type :values zijn.',
    'mimetypes' => 'Het :attribute-veld moet een bestand van het type :values zijn.',
    'min' => [
        'array' => 'Het :attribute-veld moet minstens :min items bevatten.',
        'file' => 'Het :attribute-veld moet minstens :min kilobytes zijn.',
        'numeric' => 'Het :attribute-veld moet minstens :min zijn.',
        'string' => 'Het :attribute-veld moet minstens :min tekens bevatten.',
    ],
    'min_digits' => 'Het :attribute-veld moet minstens :min cijfers bevatten.',
    'missing' => 'Het :attribute-veld moet ontbreken.',
    'missing_if' => 'Het :attribute-veld moet ontbreken wanneer :other gelijk is aan :value.',
    'missing_unless' => 'Het :attribute-veld moet ontbreken tenzij :other gelijk is aan :value.',
    'missing_with' => 'Het :attribute-veld moet ontbreken wanneer :values aanwezig is.',
    'missing_with_all' => 'Het :attribute-veld moet ontbreken wanneer :values aanwezig zijn.',
    'multiple_of' => 'Het :attribute-veld moet een veelvoud van :value zijn.',
    'not_in' => 'De geselecteerde :attribute is ongeldig.',
    'not_regex' => 'Het formaat van het :attribute-veld is ongeldig.',
    'numeric' => 'Het :attribute-veld moet een getal zijn.',
    'password' => [
        'letters' => 'Het :attribute-veld moet minstens één letter bevatten.',
        'mixed' => 'Het :attribute-veld moet minstens één hoofdletter en één kleine letter bevatten.',
        'numbers' => 'Het :attribute-veld moet minstens één cijfer bevatten.',
        'symbols' => 'Het :attribute-veld moet minstens één symbool bevatten.',
        'uncompromised' => 'Het opgegeven :attribute-veld komt voor in een datalek. Kies een ander :attribute-veld.',
    ],
    'present' => 'Het :attribute-veld moet aanwezig zijn.',
    'present_if' => 'Het :attribute-veld moet aanwezig zijn wanneer :other gelijk is aan :value.',
    'present_unless' => 'Het :attribute-veld moet aanwezig zijn tenzij :other gelijk is aan :value.',
    'present_with' => 'Het :attribute-veld moet aanwezig zijn wanneer :values aanwezig is.',
    'present_with_all' => 'Het :attribute-veld moet aanwezig zijn wanneer :values aanwezig zijn.',
    'prohibited' => 'Het :attribute-veld is niet toegestaan.',
    'prohibited_if' => 'Het :attribute-veld is niet toegestaan wanneer :other gelijk is aan :value.',
    'prohibited_if_accepted' => 'Het :attribute-veld is niet toegestaan wanneer :other geaccepteerd is.',
    'prohibited_if_declined' => 'Het :attribute-veld is niet toegestaan wanneer :other geweigerd is.',
    'prohibited_unless' => 'Het :attribute-veld is niet toegestaan tenzij :other voorkomt in :values.',
    'prohibits' => 'Het :attribute-veld verhindert dat :other aanwezig is.',
    'regex' => 'Het formaat van het :attribute-veld is ongeldig.',
    'required' => 'Het :attribute-veld is verplicht.',
    'required_array_keys' => 'Het :attribute-veld moet waarden bevatten voor: :values.',
    'required_if' => 'Het :attribute-veld is verplicht wanneer :other gelijk is aan :value.',
    'required_if_accepted' => 'Het :attribute-veld is verplicht wanneer :other geaccepteerd is.',
    'required_if_declined' => 'Het :attribute-veld is verplicht wanneer :other geweigerd is.',
    'required_unless' => 'Het :attribute-veld is verplicht tenzij :other voorkomt in :values.',
    'required_with' => 'Het :attribute-veld is verplicht wanneer :values aanwezig is.',
    'required_with_all' => 'Het :attribute-veld is verplicht wanneer :values aanwezig zijn.',
    'required_without' => 'Het :attribute-veld is verplicht wanneer :values niet aanwezig is.',
    'required_without_all' => 'Het :attribute-veld is verplicht wanneer geen van :values aanwezig is.',
    'same' => 'De velden :attribute en :other moeten overeenkomen.',
    'size' => [
        'array' => 'Het :attribute-veld moet :size items bevatten.',
        'file' => 'Het :attribute-veld moet :size kilobytes zijn.',
        'numeric' => 'Het :attribute-veld moet :size zijn.',
        'string' => 'Het :attribute-veld moet :size tekens bevatten.',
    ],
    'starts_with' => 'Het :attribute-veld moet beginnen met een van de volgende: :values.',
    'string' => 'Het :attribute-veld moet een tekst zijn.',
    'timezone' => 'Het :attribute-veld moet een geldige tijdzone zijn.',
    'unique' => 'Het :attribute-veld is al in gebruik.',
    'uploaded' => 'Het uploaden van :attribute is mislukt.',
    'uppercase' => 'Het :attribute-veld mag alleen hoofdletters bevatten.',
    'url' => 'Het :attribute-veld moet een geldige URL zijn.',
    'ulid' => 'Het :attribute-veld moet een geldige ULID zijn.',
    'uuid' => 'Het :attribute-veld moet een geldige UUID zijn.',

    /*
    |--------------------------------------------------------------------------
    | Eigen validatieberichten
    |--------------------------------------------------------------------------
    |
    | Per veld + regel een eigen bericht, bv.:
    |   'email' => ['required' => 'Vul je e-mailadres in.'],
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Veldnamen
    |--------------------------------------------------------------------------
    |
    | Filament vult hier normaal het label van het veld in. Deze lijst vangt de
    | gevallen op waar dat niet lukt en de kale sleutelnaam zou verschijnen.
    |
    */

    'attributes' => [
        'audio' => 'audiobestand',
        'canonical_url' => 'canonical URL',
        'cover' => 'cover-afbeelding',
        'email' => 'e-mailadres',
        'message' => 'bericht',
        'meta_description' => 'meta-omschrijving',
        'meta_title' => 'meta-titel',
        'name' => 'naam',
        'password' => 'wachtwoord',
        'phone' => 'telefoonnummer',
        'seo_image_alt' => 'alt-tekst',
        'seo_image_url' => 'SEO-afbeelding',
        'slug' => 'slug',
        'title' => 'titel',
    ],
];
