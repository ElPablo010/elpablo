<?php

use App\Support\FaqQuestionMatcher;

/**
 * Het vangnet tegen SEO-voorstellen die een bestaande FAQ-vraag herhalen.
 * Woord-overlap, geen exacte tekstmatch: een vraag die enkel een detail
 * toevoegt of licht herformuleert is dezelfde vraag; een andere intentie
 * (waar vs. wat kost, of een ander vraagwoord) is een écht andere vraag.
 */
beforeEach(function () {
    $this->matcher = new FaqQuestionMatcher();
});

it('marks a question with only an extra detail as the same question', function () {
    expect($this->matcher->overlaps(
        'Waar vind ik jullie tapasbar?',
        'Waar vind ik jullie tapasbar in de buurt van Antwerpen?'
    ))->toBeTrue();
});

it('ignores casing, accents and punctuation', function () {
    expect($this->matcher->overlaps(
        'Wat kost een privé-arrangement?',
        'wat kost een prive arrangement'
    ))->toBeTrue();
});

it('marks a light rephrasing as overlap', function () {
    expect($this->matcher->overlaps(
        'Moet ik vooraf een tafel reserveren voor een groep?',
        'Moet ik vooraf een tafel reserveren?'
    ))->toBeTrue();
});

it('keeps questions with a different subject apart', function () {
    expect($this->matcher->overlaps(
        'Waar kan ik parkeren?',
        'Waar kan ik reserveren?'
    ))->toBeFalse();
});

it('keeps questions with a different intent apart', function () {
    expect($this->matcher->overlaps(
        'Waar vind ik jullie zaak?',
        'Wat kost een tapasmenu?'
    ))->toBeFalse();
});

it('treats interrogatives as meaningful words', function () {
    // "waar" en "wanneer" dragen de intentie: locatie vs. moment.
    expect($this->matcher->overlaps(
        'Waar kan ik terecht voor een groepsdiner?',
        'Wanneer kan ik terecht voor een groepsdiner?'
    ))->toBeFalse();
});

it('returns the clashing existing question via firstOverlapping', function () {
    $existing = [
        'Kan ik glutenvrij eten bij jullie?',
        'Waar vind ik jullie tapasbar?',
    ];

    expect($this->matcher->firstOverlapping('Waar vind ik jullie tapasbar in Antwerpen?', $existing))
        ->toBe('Waar vind ik jullie tapasbar?');

    expect($this->matcher->firstOverlapping('Hebben jullie een terras?', $existing))->toBeNull();
});

it('covers a keyword when every meaningful word appears in the page text', function () {
    expect($this->matcher->keywordCoveredBy('tapas antwerpen', 'Tapasbar in Antwerpen tapasbar-antwerpen'))->toBeTrue();
    expect($this->matcher->keywordCoveredBy('paella catering', 'Paella catering aan huis paella-catering'))->toBeTrue();
});

it('does not cover a keyword with an unmatched meaningful word', function () {
    expect($this->matcher->keywordCoveredBy('tapas mechelen', 'Tapasbar in Antwerpen tapasbar-antwerpen'))->toBeFalse();
    expect($this->matcher->keywordCoveredBy('paella antwerpen', 'Tapasbar in Antwerpen tapasbar-antwerpen'))->toBeFalse();
});
