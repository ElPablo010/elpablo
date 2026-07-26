<?php

use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Page;
use Livewire\Livewire;

/**
 * De site is meertalig: elke pagina bestaat per taal als eigen rij, gekoppeld via
 * `translation_of`. Deze tests bewaken dat die koppeling zichtbaar blijft in de
 * admin en dat een vertaling naar haar eigen URL wijst.
 */
it('builds locale-aware public URLs', function () {
    $nl = Page::create(['title' => 'Over', 'slug' => 'over', 'locale' => 'nl', 'published' => true]);
    $en = Page::create(['title' => 'About', 'slug' => 'over', 'locale' => 'en', 'published' => true, 'translation_of' => $nl->id]);
    $enHome = Page::create(['title' => 'Home', 'slug' => 'home', 'locale' => 'en', 'is_homepage' => true, 'published' => true]);

    expect($nl->publicUrl())->toEndWith('/over')
        ->and($en->publicUrl())->toEndWith('/en/over')
        ->and($enHome->publicUrl())->toEndWith('/en');
});

it('lists only source pages by default, with the translation as a badge', function () {
    $nl = Page::create(['title' => 'Muziek', 'slug' => 'muziek', 'locale' => 'nl', 'published' => true]);
    $en = Page::create(['title' => 'Music', 'slug' => 'muziek', 'locale' => 'en', 'published' => true, 'translation_of' => $nl->id]);

    $this->actingAs(admin());

    // De NL-bron is een rij; de EN-vertaling niet — die hangt als badge aan de
    // bronrij. Op rij-niveau asserten, niet op ruwe HTML: de badge draagt de
    // titel van de vertaling in zijn tooltip, dus die tekst stáát wel in de pagina.
    Livewire::test(ListPages::class)
        ->assertCanSeeTableRecords([$nl])
        ->assertCanNotSeeTableRecords([$en]);

    // Met het filter op "alle talen" komt de vertaling wél als eigen rij terug.
    Livewire::test(ListPages::class)
        ->filterTable('translation_scope', 'all')
        ->assertCanSeeTableRecords([$nl, $en]);
});

it('links a translation back to its source page', function () {
    $nl = Page::create(['title' => 'Boeken', 'slug' => 'boeken', 'locale' => 'nl', 'published' => true]);
    $en = Page::create(['title' => 'Book', 'slug' => 'boeken', 'locale' => 'en', 'published' => true, 'translation_of' => $nl->id]);

    expect($en->sourceTranslation->is($nl))->toBeTrue()
        ->and($nl->translations->pluck('locale')->all())->toBe(['en']);
});
