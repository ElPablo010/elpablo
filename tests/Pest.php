<?php

use Webgoeroe\Core\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    // De standaardtests van de site-basis en de Groei-module draaien vanuit
    // de packages mee.
    ->in('Feature', '../vendor/webgoeroe/core/tests/Feature', '../vendor/webgoeroe/seo-growth/tests/Feature');

/*
| Core-tests voor functies die deze site bewust anders doet: per test overgeslagen,
| nooit per bestand. Alles wat hier niet staat draait gewoon mee.
*/
pest()->beforeEach(function () {
    $skips = [
        'it_zet_het_contactformulier_om_naar_een_gewoon_formulier_met_blokken' => 'De test verwacht dat contact het enige vaste formulier is; deze site heeft ook het vaste type "booking". Het commando zelf: core:convert-contact-form --dry-run.',
        'pagina_in_het_Engels_onder_' => 'De site heeft een eigen 404-pagina in de huisstijl (resources/views/errors/404.blade.php) met eigen vertaling via lang/en.json.',
        'it_vult_een_lege_beeldvorm_in_bestaande_tekst_en_media_blokken_zodat_opslaan_lukt' => 'Deze site zet de beeldvorm van tekst-en-media uit (config/core.php), dus het veld media_shape bestaat niet.',
    ];

    foreach ($skips as $needle => $reason) {
        if (str_contains($this->name(), $needle)) {
            $this->markTestSkipped($reason);
        }
    }
})->in('../vendor/webgoeroe/core/tests/Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Een ingelogde Beheerder (de klant): alle inhoud, geen instellingen.
 */
function admin(): User
{
    return User::factory()->create(['role' => UserRole::Admin]);
}

/**
 * De Websitebouwer: ziet ook de instellingen (SuperAdminOnly).
 */
function superAdmin(): User
{
    return User::factory()->create(['role' => UserRole::SuperAdmin]);
}
