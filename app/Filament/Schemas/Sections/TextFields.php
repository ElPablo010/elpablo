<?php

namespace App\Filament\Schemas\Sections;

use Filament\Forms\Components\RichEditor;

/**
 * Kale tekstsectie: boventitel + titel + een ruime rich-text body. Geschikt
 * voor lopende tekst (juridische pagina's, info-pagina's). Geen media, geen
 * CTA's — houd het bewust simpel; wie beeld naast tekst wil, gebruikt de
 * "Tekst en media"-sectie.
 */
class TextFields
{
    public static function make(): array
    {
        return [
            ...HeadingFields::make(headingRequired: false, withIntro: false),

            RichEditor::make('body')
                ->label('Tekst')
                ->toolbarButtons([
                    ['h2', 'h3'],
                    ['bold', 'italic', 'link'],
                    ['bulletList', 'orderedList', 'blockquote'],
                    ['undo', 'redo'],
                ]),
        ];
    }
}
