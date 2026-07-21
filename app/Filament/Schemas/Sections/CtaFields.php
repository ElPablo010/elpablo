<?php

namespace App\Filament\Schemas\Sections;

/**
 * CTA — een call-to-action banner: kop + korte tekst + één of meer knoppen.
 */
class CtaFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(),

            CtaLinkSchema::repeater(),
        ];
    }
}
