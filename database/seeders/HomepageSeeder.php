<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Zet één gepubliceerde homepage met een paar voorbeeld-secties neer, zodat de
 * site meteen iets toont en de builder-flow zichtbaar is. Pas de inhoud per
 * project aan (of verwijder deze seeder zodra de echte content er staat).
 */
class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => 'home'],
            [
                'title' => 'Welkom',
                'is_homepage' => true,
                'published' => true,
                'meta_title' => config('app.name'),
                'meta_description' => 'Welkom op de nieuwe website.',
            ],
        );

        $page->sections()->delete();

        $sections = [
            [
                'section_type' => 'hero',
                'content' => [
                    'eyebrow' => 'Welkom',
                    'heading' => config('app.name'),
                    'subtitle' => '<p>Dit is een voorbeeld-hero. Bewerk deze sectie in de admin onder Pagina\'s → Secties.</p>',
                ],
            ],
            [
                'section_type' => 'text_media',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Over ons',
                    'heading' => 'Een korte introductie',
                    'intro' => '<p>Vervang deze tekst en het beeld via de website-builder.</p>',
                    'media_type' => 'image',
                    'media_side' => 'right',
                ],
            ],
            [
                'section_type' => 'faq',
                'content' => [
                    'background' => 'light',
                    'heading' => 'Veelgestelde vragen',
                    'items' => [
                        ['question' => 'Hoe bewerk ik deze pagina?', 'answer' => '<p>Ga naar /admin → Pagina\'s → Welkom → tab Secties.</p>'],
                    ],
                ],
            ],
            [
                'section_type' => 'form',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Contact',
                    'heading' => 'Stuur ons een bericht',
                    'intro' => '<p>Vragen of een aanvraag? Vul het formulier in, we antwoorden snel.</p>',
                    'form_type' => 'contact',
                    'form_layout' => 'right',
                ],
            ],
            [
                'section_type' => 'cta',
                'content' => [
                    'background' => 'primary',
                    'heading' => 'Klaar om te starten?',
                    'ctas' => [
                        ['label' => 'Neem contact op', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/'],
                    ],
                ],
            ],
        ];

        foreach ($sections as $position => $section) {
            $page->sections()->create([
                'section_type' => $section['section_type'],
                'position' => $position,
                'content' => $section['content'],
            ]);
        }
    }
}
