<?php

namespace App\Livewire\Concerns;

/**
 * Bewaart de actieve locale in een Livewire-component en herstelt ze bij élke
 * request. Nodig omdat Livewire-updates naar /livewire/update posten (zonder de
 * /en- of /es-prefix), waardoor app()->getLocale() anders terugvalt op de
 * standaardtaal en labels/validatie in het Nederlands zouden verschijnen.
 *
 * Livewire roept mount-/booted-hooks met de trait-naam automatisch aan naast de
 * eigen hooks van de component, dus enkel `use PersistsLocale;` volstaat.
 */
trait PersistsLocale
{
    public string $locale = 'nl';

    public function mountPersistsLocale(): void
    {
        $this->locale = app()->getLocale();
    }

    public function bootedPersistsLocale(): void
    {
        app()->setLocale($this->locale);
    }
}
