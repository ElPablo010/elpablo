<?php

namespace App\Filament\Schemas\Sections;

/**
 * Achtergrond-keuzes per sectie. Eén bron voor zowel de admin-dropdown
 * (options()) als de publieke styling (classes()).
 *
 * TODO (per project): stem deze keys + Tailwind-classes af op het merk-palet.
 * De keys hieronder zijn neutraal; vervang ze door je merkkleuren (bv.
 * 'sand' => 'bg-brand-sand text-brand-ink') zodra het kleurenschema vastligt.
 * Houd options(), classes() en isDark() in sync.
 */
class SectionBackground
{
    public const DEFAULT = 'white';

    /**
     * Dropdownopties — alfabetisch op label (UX-conventie), behalve de neutrale
     * basiskeuzes die bovenaan logischer staan.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        // Dark nightlife-palet: elke keuze is een donkere tint. Volgorde logisch
        // (neutrale basis eerst), niet alfabetisch — de bg-keuze heeft een
        // semantische ordening van licht-donker.
        return [
            'white' => 'Standaard (zwart)',
            'light' => 'Donkergrijs',
            'primary' => 'Magenta (merkkleur)',
            'dark' => 'Diep zwart',
            'transparent' => 'Transparant',
        ];
    }

    public static function classes(?string $key): string
    {
        return match ($key) {
            'light' => 'bg-ink-900 text-white',
            'primary' => 'bg-primary-600 text-white',
            'dark' => 'bg-black text-white',
            'transparent' => 'text-white',
            default => 'bg-ink-950 text-white',
        };
    }

    /**
     * De hele publieke site is donker — elke sectie-achtergrond is dark.
     * (Behouden zodat toekomstige lichte varianten hier hun uitzondering krijgen.)
     */
    public static function isDark(?string $key): bool
    {
        return true;
    }
}
