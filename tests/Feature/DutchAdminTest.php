<?php

use Illuminate\Support\Facades\Validator;

/**
 * De admin-UI is Nederlands (zie CLAUDE.md) — inclusief de knoppen van Filament
 * en de validatieberichten van Laravel. Dat hangt aan APP_LOCALE=nl; staat die
 * op 'en', dan verschijnen er weer "Save changes"-knoppen en Engelse fouten.
 */
it('runs on the Dutch locale by default', function () {
    expect(app()->getLocale())->toBe('nl');
    expect(config('app.fallback_locale'))->toBe('nl');
});

it('shows Laravel validation messages in Dutch', function () {
    $validator = Validator::make(
        ['titel' => '', 'meta_description' => str_repeat('x', 200)],
        ['titel' => 'required', 'meta_description' => 'max:160']
    );

    $validator->fails();
    $messages = $validator->errors()->all();

    expect($messages[0])->toContain('is verplicht');
    expect($messages[1])->toContain('mag niet meer dan 160 tekens bevatten');
});

it('translates the field name in validation messages', function () {
    $validator = Validator::make(['audio' => ''], ['audio' => 'required']);
    $validator->fails();

    // 'attributes' in lang/nl/validation.php maakt er "audiobestand" van.
    expect($validator->errors()->first('audio'))->toContain('audiobestand');
});

it('shows Filament buttons in Dutch', function (string $key, string $expected) {
    expect(__($key))->toBe($expected);
})->with([
    ['filament-panels::resources/pages/edit-record.form.actions.save.label', 'Wijzigingen opslaan'],
    ['filament-panels::resources/pages/edit-record.form.actions.cancel.label', 'Annuleren'],
]);

it('keeps the public site translated per locale', function (string $locale, string $expected) {
    app()->setLocale($locale);

    expect(__('Over'))->toBe($expected);
})->with([
    ['nl', 'Over'],
    ['en', 'About'],
    ['es', 'Sobre'],
]);
