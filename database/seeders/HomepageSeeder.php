<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Mixtape;
use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteFooter;
use App\Support\SiteHeader;
use Illuminate\Database\Seeder;

/**
 * Demo-content voor El Pablo: een volledige, conversie-gerichte homepage +
 * kaderpagina's (Over/Muziek), een uitgewerkte Boeken- en Contact-pagina,
 * hoofdmenu, footermenu's en header/footer-instellingen.
 *
 * Funnel-logica: de homepage eindigt met één afsluit-CTA naar de boekingspagina
 * (het echte boekingsformulier). Algemene vragen lopen via het contactformulier
 * op de contactpagina. Placeholder-beelden komen van Unsplash — de klant vervangt
 * ze via de media-library.
 */
class HomepageSeeder extends Seeder
{
    /** Unsplash-placeholder → URL. */
    private function img(string $id, int $w = 1200): string
    {
        return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$w}&q=80";
    }

    public function run(): void
    {
        $this->seedMixtapes();

        $home = $this->seedHomepage();
        $over = $this->seedOverPage();
        $muziek = $this->seedMuziekPage();
        $boeken = $this->seedBookingPage();
        $contact = $this->seedContactPage();
        $this->seedLegalPages();

        $this->seedMenus($home, $over, $muziek, $boeken, $contact);
        $this->seedSettings($boeken);

        // Meertalig: dupliceer alle NL-pagina's naar EN/ES (placeholder-inhoud,
        // later te vertalen via AI).
        $this->seedTranslations();
    }

    /**
     * Demo-mixtapes (eigen posttype, taal-onafhankelijk). De mixes-secties
     * verwijzen ernaar via show_all of mixtape_ids.
     */
    private function seedMixtapes(): void
    {
        $latin = 'https://www.el-pablo.com/wp-content/uploads/2025/05/Latin-Vibes.mp3';
        $live = 'https://www.el-pablo.com/wp-content/uploads/2025/05/Live-set-Mokta-Mee.mp3';

        $mixtapes = [
            ['title' => 'Latin Vibes', 'subtitle' => 'Reggaeton & latin house', 'audio_url' => $latin, 'cover_url' => $this->img('1544986581-efac024faf62', 800), 'allow_download' => true],
            ['title' => 'Live set @ Mokta Mee', 'subtitle' => 'Urban & latin · live opname', 'audio_url' => $live, 'cover_url' => $this->img('1524368535928-5b5e00ddc76b', 800), 'allow_download' => true],
            ['title' => 'Reggaeton Heat', 'subtitle' => 'Reggaeton · 60 min', 'audio_url' => $latin, 'cover_url' => $this->img('1518972559570-7cc1309f3229', 800), 'allow_download' => true],
            ['title' => 'Beach Club Sunset', 'subtitle' => 'Latin house · zomerset', 'audio_url' => $live, 'cover_url' => $this->img('1533174072545-7a4b6ad7a6c3', 800), 'allow_download' => false],
            ['title' => 'Urban Night Vol. 3', 'subtitle' => 'Urban & afrobeats', 'audio_url' => $latin, 'cover_url' => $this->img('1470229722913-7c0e2dbbafd3', 800), 'allow_download' => true],
            ['title' => 'Carnaval Special', 'subtitle' => 'Feestset · latin', 'audio_url' => $live, 'cover_url' => $this->img('1492684223066-81342ee5ff30', 800), 'allow_download' => true],
        ];

        foreach ($mixtapes as $position => $mixtape) {
            Mixtape::updateOrCreate(
                ['title' => $mixtape['title']],
                [...$mixtape, 'published' => true, 'position' => $position],
            );
        }
    }

    /** De mixtape-ids voor een handmatige sectie-selectie, op titel. */
    private function mixtapeIds(string ...$titles): array
    {
        return array_values(array_filter(array_map(
            fn (string $title) => Mixtape::query()->where('title', $title)->value('id'),
            $titles,
        )));
    }

    private function seedHomepage(): Page
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => 'home'],
            [
                'title' => 'Home',
                'is_homepage' => true,
                'published' => true,
                'meta_title' => 'El Pablo — Urban Latin DJ uit Antwerpen',
                'meta_description' => 'Boek El Pablo, urban latin DJ uit Antwerpen, voor clubs, privéfeesten, bruiloften en festivals. Latin, reggaeton & urban vibes voor onvergetelijke nachten.',
            ],
        );

        $page->sections()->delete();

        $sections = [
            [
                'section_type' => 'hero',
                'content' => [
                    'eyebrow' => 'Urban Latin DJ · Antwerpen',
                    'heading' => 'Latin & urban vibes voor onvergetelijke nachten',
                    'subtitle' => '<p>Van clubnacht tot strandfeest: ik breng het publiek samen met reggaeton, latin house en urban beats. Klaar om jouw dansvloer te laten koken.</p>',
                    'image' => [
                        'src' => $this->img('1459749411175-04bf5292ceea', 2000),
                        'alt' => 'El Pablo achter de decks tijdens een clubnacht',
                        'position' => 'center 50%',
                    ],
                    'ctas' => [
                        ['label' => 'Boek El Pablo', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken'],
                        ['label' => 'Beluister mixes', 'variant' => 'secondary', 'link_type' => 'url', 'href' => '#mixes'],
                    ],
                ],
            ],
            [
                'section_type' => 'cards',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Wat ik doe',
                    'heading' => 'Voor elk feest de juiste vibe',
                    'intro' => '<p>Elke gelegenheid vraagt een eigen energie. Vertel me wat je plant, en ik zorg voor de soundtrack.</p>',
                    'columns' => '4',
                    'cards' => [
                        ['title' => 'Clubavonden', 'media_type' => 'image', 'image' => $this->img('1470229722913-7c0e2dbbafd3'), 'description' => 'House, reggaeton en urban sets die de dansvloer de hele nacht vollen houden.'],
                        ['title' => 'Privéfeesten', 'media_type' => 'image', 'image' => $this->img('1533174072545-7a4b6ad7a6c3'), 'description' => 'Verjaardag, tuinfeest of verrassing? Ik stem de muziek af op jouw gasten.'],
                        ['title' => 'Bruiloften', 'media_type' => 'image', 'image' => $this->img('1492684223066-81342ee5ff30'), 'description' => 'Van romantische openingsdans tot een dansvloer die niet meer leegloopt.'],
                        ['title' => 'Festivals & strandfeesten', 'media_type' => 'image', 'image' => $this->img('1470225620780-dba8ba36b745'), 'description' => 'Zomerse latin energie voor grote crowds, buiten én binnen.'],
                    ],
                ],
            ],
            [
                'section_type' => 'text_media',
                'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Wie is El Pablo',
                    'heading' => 'De DJ die de dansvloer laat koken',
                    'intro' => '<p>Geboren en getogen in Antwerpen, met latin ritmes in het bloed. Al meer dan tien jaar draai ik in clubs en op feesten door heel Vlaanderen — altijd met één doel: iederéén op de dansvloer krijgen en houden.</p><p>Mijn kracht? Een dansvloer lezen en het juiste nummer op het juiste moment droppen.</p>',
                    'media_type' => 'image',
                    'media_side' => 'left',
                    'media' => ['src' => $this->img('1516450360452-9312f5e86fc7', 1400), 'alt' => 'El Pablo aan de mengtafel'],
                    'ctas' => [
                        ['label' => 'Lees mijn verhaal', 'variant' => 'secondary', 'link_type' => 'url', 'href' => '/over'],
                    ],
                ],
            ],
            [
                'section_type' => 'mixes',
                'content' => [
                    'section_id' => 'mixes',
                    'background' => 'white',
                    'eyebrow' => 'Muziek',
                    'heading' => "Beluister m'n sets",
                    'intro' => '<p>Een DJ leeft van z\'n sets. Speel ze hier af — of download ze — en proef de sfeer voor je boekt.</p>',
                    'show_all' => false,
                    'mixtape_ids' => $this->mixtapeIds('Latin Vibes', 'Live set @ Mokta Mee'),
                    'ctas' => [
                        ['label' => 'Bekijk alle sets', 'variant' => 'secondary', 'link_type' => 'url', 'href' => '/muziek'],
                    ],
                ],
            ],
            [
                'section_type' => 'reviews',
                'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Wat ze zeggen',
                    'heading' => 'Organisatoren aan het woord',
                    'items' => [
                        ['quote' => 'El Pablo kreeg iederéén op de dansvloer en hield ze daar tot sluitingstijd. Onze club was uitverkocht en de sfeer was ongezien.', 'name' => 'Sofie Maes', 'role' => 'Organisator — Volmolen', 'rating' => '5'],
                        ['quote' => 'Voor ons trouwfeest gedroomd. Hij las de zaal perfect: rustig bij het diner, knallend na middernacht.', 'name' => 'Tom & Laura', 'role' => 'Bruiloft in Kontich', 'rating' => '5'],
                        ['quote' => 'Professioneel, stipt en muzikaal top. Onze gasten praten er weken later nog over. Zeker een aanrader.', 'name' => 'Karim El Fassi', 'role' => 'Privéfeest — Antwerpen', 'rating' => '5'],
                    ],
                ],
            ],
            [
                'section_type' => 'gallery',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Sfeer',
                    'heading' => 'Zo ziet een El Pablo-avond eruit',
                    'columns' => '3',
                    'items' => [
                        ['image' => $this->img('1429962714451-bb934ecdc4ec', 900), 'alt' => 'DJ booth met crowd'],
                        ['image' => $this->img('1493225457124-a3eb161ffa5f', 900), 'alt' => 'Handen in de lucht op de dansvloer'],
                        ['image' => $this->img('1506157786151-b8491531f063', 900), 'alt' => 'Koptelefoon op de mengtafel'],
                        ['image' => $this->img('1574391884720-bbc3740c59d1', 900), 'alt' => 'Draaitafel close-up'],
                        ['image' => $this->img('1470229722913-7c0e2dbbafd3', 900), 'alt' => 'Volle dansvloer'],
                        ['image' => $this->img('1459749411175-04bf5292ceea', 900), 'alt' => 'Podiumlichten'],
                    ],
                ],
            ],
            [
                'section_type' => 'faq',
                'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Goed om te weten',
                    'heading' => 'Veelgestelde vragen',
                    'items' => [
                        ['question' => 'In welke regio ben je beschikbaar?', 'answer' => '<p>Ik draai in heel Vlaanderen, met Antwerpen als thuisbasis. Voor feesten verder weg of in het buitenland: vraag gerust naar de mogelijkheden.</p>'],
                        ['question' => 'Voorzie je zelf geluid en licht?', 'answer' => '<p>Ik kan een volledige set-up voorzien of samenwerken met de installatie ter plaatse. We stemmen dit af bij de boeking.</p>'],
                        ['question' => 'Kan ik nummers doorgeven?', 'answer' => '<p>Zeker. Je kan vooraf een wenslijst doorsturen. Ik lees ook live de dansvloer en pas de set daarop aan.</p>'],
                        ['question' => 'Hoe boek ik een datum?', 'answer' => '<p>Vul het aanvraagformulier op de <a href="/boeken">boekingspagina</a> in. Ik antwoord meestal binnen 24 uur met een voorstel op maat.</p>'],
                    ],
                ],
            ],
            [
                'section_type' => 'cta',
                'content' => [
                    'background' => 'primary',
                    'heading' => 'Klaar om de dansvloer te vullen?',
                    'intro' => '<p>Check mijn beschikbaarheid en maak van jouw feest een onvergetelijke nacht.</p>',
                    'ctas' => [
                        ['label' => 'Boek El Pablo', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken'],
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

        return $page;
    }

    /**
     * Boekingspagina — de primaire conversie. Eindigt op het boekingsformulier.
     */
    private function seedBookingPage(): Page
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => 'boeken'],
            [
                'title' => 'Boeken',
                'is_homepage' => false,
                'published' => true,
                'meta_title' => 'Boek El Pablo — Urban Latin DJ',
                'meta_description' => 'Boek El Pablo voor je clubavond, privéfeest, bruiloft of festival. Vraag vrijblijvend je datum aan en ontvang snel een voorstel op maat.',
            ],
        );

        $page->sections()->delete();

        $sections = [
            [
                'section_type' => 'hero',
                'content' => [
                    'eyebrow' => 'Boeken',
                    'heading' => 'Boek El Pablo voor jouw feest',
                    'subtitle' => '<p>Vertel me over je gelegenheid en ontvang snel een voorstel op maat. Vrijblijvend en zonder verplichting.</p>',
                    'image' => ['src' => $this->img('1533174072545-7a4b6ad7a6c3', 2000), 'alt' => 'Feestende crowd', 'position' => 'center 50%'],
                    'ctas' => [['label' => 'Naar het aanvraagformulier', 'variant' => 'primary', 'link_type' => 'url', 'href' => '#aanvraag']],
                ],
            ],
            [
                'section_type' => 'cards',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Zo werkt het',
                    'heading' => 'In drie stappen geboekt',
                    'columns' => '3',
                    'cards' => [
                        ['title' => '1. Vraag aan', 'media_type' => 'icon', 'icon' => 'clipboard-list', 'description' => 'Vul het formulier in met je datum, locatie en type gelegenheid.'],
                        ['title' => '2. Voorstel op maat', 'media_type' => 'icon', 'icon' => 'calendar-check', 'description' => 'Je ontvangt meestal binnen 24 uur een concreet voorstel met prijs.'],
                        ['title' => '3. Feesten', 'media_type' => 'icon', 'icon' => 'party-popper', 'description' => 'We stemmen de details af en ik zorg voor een onvergetelijke nacht.'],
                    ],
                ],
            ],
            [
                'section_type' => 'faq',
                'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Goed om te weten',
                    'heading' => 'Praktische vragen',
                    'items' => [
                        ['question' => 'Hoe snel krijg ik antwoord?', 'answer' => '<p>Meestal binnen 24 uur. Voor last-minute aanvragen kan je me ook bellen.</p>'],
                        ['question' => 'Wat kost een boeking?', 'answer' => '<p>Dat hangt af van de datum, duur en locatie. Vul het formulier in voor een voorstel op maat.</p>'],
                        ['question' => 'Voorzie je geluid en licht?', 'answer' => '<p>Ik kan een volledige set-up voorzien of samenwerken met de installatie ter plaatse.</p>'],
                    ],
                ],
            ],
            [
                'section_type' => 'form',
                'content' => [
                    'section_id' => 'aanvraag',
                    'background' => 'white',
                    'eyebrow' => 'Aanvraag',
                    'heading' => 'Vraag je datum aan',
                    'intro' => '<p>Hoe meer je invult, hoe gerichter mijn voorstel. Alle velden met een datum of type helpen me meteen op weg.</p>',
                    'form_type' => 'booking',
                    'form_layout' => 'right',
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

        return $page;
    }

    /**
     * Contactpagina — algemene vragen via het contactformulier, met een
     * kruisverwijzing naar boeken voor wie meteen een datum wil vastleggen.
     */
    private function seedContactPage(): Page
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => 'contact'],
            [
                'title' => 'Contact',
                'is_homepage' => false,
                'published' => true,
                'meta_title' => 'Contact — El Pablo',
                'meta_description' => 'Vragen voor El Pablo, urban latin DJ uit Antwerpen? Stuur een bericht of bel rechtstreeks.',
            ],
        );

        $page->sections()->delete();

        $sections = [
            [
                'section_type' => 'hero',
                'content' => [
                    'eyebrow' => 'Contact',
                    'heading' => 'Neem contact op',
                    'subtitle' => '<p>Een vraag of gewoon zin om te overleggen? Stuur een bericht of bel rechtstreeks — ik hoor het graag.</p>',
                    'image' => ['src' => $this->img('1518972559570-7cc1309f3229', 2000), 'alt' => 'Clubsfeer', 'position' => 'center 50%'],
                ],
            ],
            [
                'section_type' => 'form',
                'content' => [
                    'section_id' => 'contact',
                    'background' => 'white',
                    'eyebrow' => 'Bericht',
                    'heading' => 'Stuur een bericht',
                    'intro' => '<p>Vul het formulier in en ik antwoord zo snel mogelijk. Wil je meteen een datum vastleggen? Gebruik dan het <a href="/boeken">boekingsformulier</a>.</p>',
                    'form_type' => 'contact',
                    'form_layout' => 'right',
                ],
            ],
            [
                'section_type' => 'cta',
                'content' => [
                    'background' => 'primary',
                    'heading' => 'Liever meteen een datum vastleggen?',
                    'intro' => '<p>Vraag vrijblijvend je boeking aan met alle details in één keer.</p>',
                    'ctas' => [['label' => 'Naar het boekingsformulier', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken']],
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

        return $page;
    }

    /**
     * Over-pagina — bio, stijl en sfeer. Bouwt vertrouwen op vóór de boeking.
     */
    private function seedOverPage(): Page
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => 'over'],
            [
                'title' => 'Over El Pablo',
                'is_homepage' => false,
                'published' => true,
                'meta_title' => 'Over El Pablo — Urban Latin DJ uit Antwerpen',
                'meta_description' => 'Maak kennis met El Pablo, urban latin DJ uit Antwerpen. Meer dan tien jaar dansvloeren vullen met reggaeton, latin house en urban beats.',
            ],
        );

        $page->sections()->delete();

        $sections = [
            [
                'section_type' => 'hero',
                'content' => [
                    'eyebrow' => 'Over El Pablo',
                    'heading' => 'De DJ achter de decks',
                    'subtitle' => '<p>Antwerpse roots, latin ritmes in het bloed. Dit is het verhaal achter de sets.</p>',
                    'image' => ['src' => $this->img('1516450360452-9312f5e86fc7', 2000), 'alt' => 'El Pablo aan de mengtafel', 'position' => 'center 50%'],
                    'ctas' => [['label' => 'Boek El Pablo', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken']],
                ],
            ],
            [
                'section_type' => 'text_media',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Het verhaal',
                    'heading' => 'Geboren om de dansvloer te vullen',
                    'intro' => '<p>El Pablo groeide op in Antwerpen, waar latin en urban muziek al vroeg een tweede taal werden. Wat begon als draaien voor vrienden, groeide uit tot een vaste waarde in clubs en op feesten door heel Vlaanderen.</p><p>Meer dan tien jaar later is de missie nog altijd dezelfde: iederéén op de dansvloer krijgen — en daar houden tot de lichten aangaan.</p>',
                    'media_type' => 'image',
                    'media_side' => 'right',
                    'media' => ['src' => $this->img('1429962714451-bb934ecdc4ec', 1400), 'alt' => 'El Pablo in de DJ-booth'],
                ],
            ],
            [
                'section_type' => 'cards',
                'content' => [
                    'background' => 'light',
                    'eyebrow' => 'Mijn stijl',
                    'heading' => 'Waar ik voor sta',
                    'intro' => '<p>Geen vaste playlist, wel een duidelijke signatuur.</p>',
                    'columns' => '4',
                    'cards' => [
                        ['title' => 'Latin & reggaeton', 'media_type' => 'icon', 'icon' => 'music', 'description' => 'Van klassieke salsa-vibes tot de nieuwste reggaeton-hits.'],
                        ['title' => 'Urban & afrobeats', 'media_type' => 'icon', 'icon' => 'headphones', 'description' => 'Strakke urban beats en afrobeats die de energie hoog houden.'],
                        ['title' => 'Ik lees de dansvloer', 'media_type' => 'icon', 'icon' => 'radio', 'description' => 'Het juiste nummer op het juiste moment — live aangevoeld.'],
                        ['title' => 'Voor iedereen', 'media_type' => 'icon', 'icon' => 'users', 'description' => 'Inclusief en vrouwvriendelijk: iedereen voelt zich welkom.'],
                    ],
                ],
            ],
            [
                'section_type' => 'gallery',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Sfeer',
                    'heading' => 'Momenten van de dansvloer',
                    'columns' => '3',
                    'items' => [
                        ['image' => $this->img('1470229722913-7c0e2dbbafd3', 900), 'alt' => 'Volle dansvloer'],
                        ['image' => $this->img('1493225457124-a3eb161ffa5f', 900), 'alt' => 'Handen in de lucht'],
                        ['image' => $this->img('1459749411175-04bf5292ceea', 900), 'alt' => 'Podiumlichten'],
                        ['image' => $this->img('1574391884720-bbc3740c59d1', 900), 'alt' => 'Draaitafel'],
                        ['image' => $this->img('1506157786151-b8491531f063', 900), 'alt' => 'Koptelefoon op de mengtafel'],
                        ['image' => $this->img('1524368535928-5b5e00ddc76b', 900), 'alt' => 'Clubsfeer'],
                    ],
                ],
            ],
            [
                'section_type' => 'cta',
                'content' => [
                    'background' => 'primary',
                    'heading' => 'Laat El Pablo jouw feest maken',
                    'intro' => '<p>Vraag vrijblijvend je datum aan en check de beschikbaarheid.</p>',
                    'ctas' => [['label' => 'Boek El Pablo', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken']],
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

        return $page;
    }

    /**
     * Muziek-pagina — het volledige sets-overzicht, met dezelfde inline speler
     * (afspelen + downloaden). De audiobestanden zijn placeholders (twee bestaande
     * sets) — vervang ze per set via de admin.
     */
    private function seedMuziekPage(): Page
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => 'muziek'],
            [
                'title' => 'Muziek',
                'is_homepage' => false,
                'published' => true,
                'meta_title' => 'Muziek & sets — El Pablo',
                'meta_description' => 'Beluister en download de sets van El Pablo: reggaeton, latin house, urban en afrobeats. Urban latin DJ uit Antwerpen.',
            ],
        );

        $page->sections()->delete();

        $sections = [
            [
                'section_type' => 'hero',
                'content' => [
                    'eyebrow' => 'Muziek',
                    'heading' => 'Sets & mixes',
                    'subtitle' => '<p>Speel de sets hier af of download ze. Proef de sfeer van een El Pablo-avond.</p>',
                    'image' => ['src' => $this->img('1470225620780-dba8ba36b745', 2000), 'alt' => 'El Pablo live', 'position' => 'center 50%'],
                    'ctas' => [['label' => 'Boek El Pablo', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken']],
                ],
            ],
            [
                'section_type' => 'mixes',
                'content' => [
                    'background' => 'white',
                    'eyebrow' => 'Alle sets',
                    'heading' => 'Beluister m\'n muziek',
                    'show_all' => true,
                    'mixtape_ids' => [],
                ],
            ],
            [
                'section_type' => 'cta',
                'content' => [
                    'background' => 'primary',
                    'heading' => 'Deze vibe op jouw feest?',
                    'intro' => '<p>Boek El Pablo en breng de dansvloer tot leven.</p>',
                    'ctas' => [['label' => 'Boek El Pablo', 'variant' => 'primary', 'link_type' => 'url', 'href' => '/boeken']],
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

        return $page;
    }

    /**
     * Juridische pagina's — cookiebeleid + privacybeleid. Bruikbaar GDPR-sjabloon
     * (geen lorem): veilig als vangnet, bedoeld om per klant na te lezen en aan te
     * scherpen. De footer en cookiebanner linken al naar deze slugs.
     */
    private function seedLegalPages(): void
    {
        $this->ensureLegalPage('cookiebeleid', 'Cookiebeleid', $this->cookieBody('nl'));
        $this->ensureLegalPage('privacybeleid', 'Privacybeleid', $this->privacyBody('nl'));
    }

    private function ensureLegalPage(string $slug, string $title, string $body): Page
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => $slug],
            [
                'title' => $title,
                'is_homepage' => false,
                'published' => true,
                'meta_title' => $title.' — El Pablo',
                'meta_robots' => 'noindex, follow',
            ],
        );

        // Alleen seeden als de pagina nog leeg is — nooit klant-edits overschrijven.
        if ($page->sections()->doesntExist()) {
            $page->sections()->create([
                'section_type' => 'text',
                'position' => 0,
                'content' => ['heading' => $title, 'body' => $body],
            ]);
        }

        return $page;
    }

    private function cookieBody(string $locale): string
    {
        $name = config('app.name');
        $email = config('mail.from.address');

        return match ($locale) {
            'en' => <<<HTML
                <p>This website ({$name}) uses cookies. Below you can read which cookies we place, what they are for and how you can adjust your choice at any time.</p>
                <h2>1. What are cookies?</h2>
                <p>Cookies are small text files that are stored on your device when you visit our website. They make sure the site works properly and help us, with your consent, to understand how the site is used.</p>
                <h2>2. Which cookies do we use?</h2>
                <p><strong>Functional cookies</strong> &mdash; necessary for the website to work. We always place these; your consent is not required. Example: a cookie that stores your cookie preference (180 days), so we don't show the consent window on every visit.</p>
                <p><strong>Analytical cookies</strong> &mdash; only with your consent. These let us measure anonymously how visitors use the site (for example via Google Analytics), so we can improve it.</p>
                <p><strong>Marketing cookies</strong> &mdash; only with your consent. These may be placed by third parties to make advertising more relevant and to measure its reach.</p>
                <h2>3. Your consent</h2>
                <p>On your first visit we ask, via a banner, which cookies you allow. Analytical and marketing cookies are only placed after you accept them. As long as you make no choice, they stay disabled.</p>
                <h2>4. Changing or withdrawing your choice</h2>
                <p>You can adjust your cookie preferences at any time via the <em>&laquo;&nbsp;Cookie settings&nbsp;&raquo;</em> link at the bottom of every page.</p>
                <h2>5. Deleting cookies via your browser</h2>
                <p>You can also delete or block already-placed cookies via your browser settings. Note: certain parts of the website may then no longer work optimally.</p>
                <h2>6. More information</h2>
                <p>How we process personal data is explained in our <a href="/en/privacybeleid">privacy policy</a>. Questions about this cookie policy? Feel free to get in touch via <a href="mailto:{$email}">{$email}</a>.</p>
                HTML,
            'es' => <<<HTML
                <p>Este sitio web ({$name}) utiliza cookies. A continuación puedes leer qué cookies colocamos, para qué sirven y cómo puedes ajustar tu elección en cualquier momento.</p>
                <h2>1. ¿Qué son las cookies?</h2>
                <p>Las cookies son pequeños archivos de texto que se guardan en tu dispositivo al visitar nuestro sitio web. Hacen que el sitio funcione correctamente y nos ayudan, con tu consentimiento, a entender cómo se utiliza.</p>
                <h2>2. ¿Qué cookies utilizamos?</h2>
                <p><strong>Cookies funcionales</strong> &mdash; necesarias para el funcionamiento del sitio. Siempre las colocamos; no se requiere tu consentimiento. Ejemplo: una cookie que guarda tu preferencia de cookies (180 días), para no mostrar el aviso en cada visita.</p>
                <p><strong>Cookies analíticas</strong> &mdash; solo con tu consentimiento. Nos permiten medir de forma anónima cómo usan el sitio los visitantes (por ejemplo, con Google Analytics), para poder mejorarlo.</p>
                <p><strong>Cookies de marketing</strong> &mdash; solo con tu consentimiento. Pueden ser colocadas por terceros para hacer la publicidad más relevante y medir su alcance.</p>
                <h2>3. Tu consentimiento</h2>
                <p>En tu primera visita te preguntamos, mediante un banner, qué cookies permites. Las cookies analíticas y de marketing solo se colocan después de que las aceptes. Mientras no elijas, permanecen desactivadas.</p>
                <h2>4. Cambiar o retirar tu elección</h2>
                <p>Puedes ajustar tus preferencias de cookies en cualquier momento mediante el enlace <em>&laquo;&nbsp;Configuración de cookies&nbsp;&raquo;</em> al final de cada página.</p>
                <h2>5. Eliminar cookies desde tu navegador</h2>
                <p>También puedes eliminar o bloquear las cookies ya colocadas desde la configuración de tu navegador. Nota: algunas partes del sitio podrían dejar de funcionar de forma óptima.</p>
                <h2>6. Más información</h2>
                <p>Cómo tratamos los datos personales se explica en nuestra <a href="/es/privacybeleid">política de privacidad</a>. ¿Preguntas sobre esta política de cookies? Contáctanos en <a href="mailto:{$email}">{$email}</a>.</p>
                HTML,
            default => <<<HTML
                <p>Deze website ({$name}) maakt gebruik van cookies. Hieronder lees je welke cookies we plaatsen, waarvoor ze dienen en hoe je je keuze op elk moment kunt aanpassen.</p>
                <h2>1. Wat zijn cookies?</h2>
                <p>Cookies zijn kleine tekstbestanden die bij een bezoek aan onze website op je toestel worden bewaard. Ze zorgen ervoor dat de site goed werkt en helpen ons, mits jouw toestemming, om te begrijpen hoe de site gebruikt wordt.</p>
                <h2>2. Welke cookies gebruiken we?</h2>
                <p><strong>Functionele cookies</strong> &mdash; noodzakelijk voor de werking van de website. Deze plaatsen we altijd; je toestemming is hiervoor niet vereist. Voorbeeld: een cookie die jouw cookievoorkeur bewaart (180 dagen), zodat we het toestemmingsvenster niet bij elk bezoek opnieuw tonen.</p>
                <p><strong>Analytische cookies</strong> &mdash; enkel met jouw toestemming. Hiermee meten we anoniem hoe bezoekers de site gebruiken (bijvoorbeeld via Google Analytics), zodat we ze kunnen verbeteren.</p>
                <p><strong>Marketing cookies</strong> &mdash; enkel met jouw toestemming. Deze kunnen door derde partijen geplaatst worden om advertenties relevanter te maken en het bereik ervan te meten.</p>
                <h2>3. Jouw toestemming</h2>
                <p>Bij je eerste bezoek vragen we via een banner welke cookies je toelaat. Analytische en marketing cookies worden pas geplaatst nadat je ze aanvaardt. Zolang je geen keuze maakt, blijven ze uitgeschakeld.</p>
                <h2>4. Je keuze wijzigen of intrekken</h2>
                <p>Je kunt je cookievoorkeuren op elk moment aanpassen via de link <em>&laquo;&nbsp;Cookie-instellingen&nbsp;&raquo;</em> onderaan elke pagina.</p>
                <h2>5. Cookies verwijderen via je browser</h2>
                <p>Je kunt reeds geplaatste cookies ook verwijderen of blokkeren via de instellingen van je browser. Let op: bepaalde onderdelen van de website werken dan mogelijk niet meer optimaal.</p>
                <h2>6. Meer informatie</h2>
                <p>Hoe we persoonsgegevens verwerken, lees je in ons <a href="/privacybeleid">privacybeleid</a>. Vragen over dit cookiebeleid? Neem gerust contact op via <a href="mailto:{$email}">{$email}</a>.</p>
                HTML,
        };
    }

    private function privacyBody(string $locale): string
    {
        $name = config('app.name');
        $email = config('mail.from.address');

        return match ($locale) {
            'en' => <<<HTML
                <p>{$name} attaches great importance to protecting your personal data and respects your privacy. This statement explains how we collect and use data, in accordance with the General Data Protection Regulation (GDPR).</p>
                <h2>1. Which data do we collect?</h2>
                <p>We only collect the data needed for our services, such as:</p>
                <ul>
                    <li>Name and contact details for a booking or contact request;</li>
                    <li>Email address for communication;</li>
                    <li>Technical data (such as IP address) when you visit the website.</li>
                </ul>
                <h2>2. What do we use your data for?</h2>
                <p>Your data is only used to process bookings and contact requests, and to improve our website and services.</p>
                <h2>3. Do we share your data?</h2>
                <p>We never pass your data to third parties, unless this is necessary for our services or legally required.</p>
                <h2>4. How long do we keep your data?</h2>
                <p>We do not keep your data longer than necessary for the purpose for which it was collected.</p>
                <h2>5. Cookies</h2>
                <p>Our website uses cookies. Read more about this in our <a href="/en/cookiebeleid">cookie policy</a>.</p>
                <h2>6. Your rights</h2>
                <p>You have the right to access, correct, delete or transfer your personal data, and you can withdraw a given consent. To do so, get in touch via <a href="mailto:{$email}">{$email}</a>.</p>
                <h2>7. Security</h2>
                <p>We take appropriate technical and organizational measures to protect your data against loss or unauthorized access.</p>
                HTML,
            'es' => <<<HTML
                <p>{$name} concede gran importancia a la protección de tus datos personales y respeta tu privacidad. En esta declaración explicamos cómo recopilamos y utilizamos los datos, de acuerdo con el Reglamento General de Protección de Datos (RGPD).</p>
                <h2>1. ¿Qué datos recopilamos?</h2>
                <p>Solo recopilamos los datos necesarios para nuestros servicios, como:</p>
                <ul>
                    <li>Nombre y datos de contacto para una reserva o solicitud de contacto;</li>
                    <li>Dirección de correo electrónico para la comunicación;</li>
                    <li>Datos técnicos (como la dirección IP) al visitar el sitio web.</li>
                </ul>
                <h2>2. ¿Para qué usamos tus datos?</h2>
                <p>Tus datos solo se utilizan para gestionar reservas y solicitudes de contacto, y para mejorar nuestro sitio web y servicios.</p>
                <h2>3. ¿Compartimos tus datos?</h2>
                <p>Nunca cedemos tus datos a terceros, salvo que sea necesario para nuestros servicios o esté legalmente exigido.</p>
                <h2>4. ¿Cuánto tiempo conservamos tus datos?</h2>
                <p>No conservamos tus datos más tiempo del necesario para el fin para el que se recopilaron.</p>
                <h2>5. Cookies</h2>
                <p>Nuestro sitio web utiliza cookies. Lee más al respecto en nuestra <a href="/es/cookiebeleid">política de cookies</a>.</p>
                <h2>6. Tus derechos</h2>
                <p>Tienes derecho a acceder, corregir, eliminar o transferir tus datos personales, y puedes retirar un consentimiento otorgado. Para ello, contáctanos en <a href="mailto:{$email}">{$email}</a>.</p>
                <h2>7. Seguridad</h2>
                <p>Adoptamos medidas técnicas y organizativas adecuadas para proteger tus datos contra pérdida o acceso no autorizado.</p>
                HTML,
            default => <<<HTML
                <p>{$name} hecht veel belang aan de bescherming van jouw persoonsgegevens en respecteert je privacy. In deze verklaring lees je hoe we gegevens verzamelen en gebruiken, in overeenstemming met de Algemene Verordening Gegevensbescherming (GDPR).</p>
                <h2>1. Welke gegevens verzamelen we?</h2>
                <p>We verzamelen enkel de gegevens die nodig zijn voor onze dienstverlening, zoals:</p>
                <ul>
                    <li>Naam en contactgegevens bij een boeking of contactaanvraag;</li>
                    <li>E-mailadres voor communicatie;</li>
                    <li>Technische gegevens (zoals IP-adres) bij een bezoek aan de website.</li>
                </ul>
                <h2>2. Waarvoor gebruiken we je gegevens?</h2>
                <p>Je gegevens worden enkel gebruikt voor het verwerken van boekingen en contactaanvragen, en om onze website en dienstverlening te verbeteren.</p>
                <h2>3. Delen we je gegevens?</h2>
                <p>We geven je gegevens nooit door aan derden, tenzij dit noodzakelijk is voor onze dienstverlening of wettelijk verplicht is.</p>
                <h2>4. Hoe lang bewaren we je gegevens?</h2>
                <p>We bewaren je gegevens niet langer dan nodig voor het doel waarvoor ze verzameld werden.</p>
                <h2>5. Cookies</h2>
                <p>Onze website gebruikt cookies. Meer daarover lees je in ons <a href="/cookiebeleid">cookiebeleid</a>.</p>
                <h2>6. Jouw rechten</h2>
                <p>Je hebt het recht op inzage, correctie, verwijdering of overdracht van je persoonsgegevens, en je kunt een gegeven toestemming intrekken. Neem hiervoor contact op via <a href="mailto:{$email}">{$email}</a>.</p>
                <h2>7. Beveiliging</h2>
                <p>We nemen passende technische en organisatorische maatregelen om je gegevens te beschermen tegen verlies of ongeoorloofde toegang.</p>
                HTML,
        };
    }

    /**
     * Dupliceer elke NL-pagina (met secties) naar EN en ES. De inhoud is
     * voorlopig een kopie van het Nederlands (placeholder) — de effectieve
     * vertaling gebeurt later via AI. Zelfde slug per taal (unique op [locale, slug]).
     */
    private function seedTranslations(): void
    {
        $legalSlugs = ['cookiebeleid', 'privacybeleid'];
        $sourcePages = Page::query()->where('locale', 'nl')->with('sections')->get();

        foreach ($sourcePages as $source) {
            foreach (['en', 'es'] as $locale) {
                $copy = Page::updateOrCreate(
                    ['locale' => $locale, 'slug' => $source->slug],
                    [
                        'title' => $this->tr($source->title, $locale),
                        'is_homepage' => $source->is_homepage,
                        'published' => $source->published,
                        'translation_of' => $source->id,
                        'meta_title' => $this->tr($source->meta_title, $locale),
                        'meta_description' => $this->tr($source->meta_description, $locale),
                        'meta_robots' => $source->meta_robots,
                    ],
                );

                $copy->sections()->delete();

                foreach ($source->sections as $section) {
                    $content = $this->translateContent($section->content ?? [], $locale);

                    // Juridische tekst: gebruik de volledig vertaalde body-methode.
                    if ($section->section_type === 'text' && in_array($source->slug, $legalSlugs, true)) {
                        $content['body'] = $source->slug === 'cookiebeleid'
                            ? $this->cookieBody($locale)
                            : $this->privacyBody($locale);
                    }

                    $copy->sections()->create([
                        'section_type' => $section->section_type,
                        'position' => $section->position,
                        'content' => $content,
                        'locale' => $locale,
                        'translation_of' => $section->id,
                    ]);
                }
            }
        }
    }

    /** Vertaal één string via de map (of geef 'm ongewijzigd terug). */
    private function tr(?string $value, string $locale): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->contentTranslations()[$value][$locale] ?? $value;
    }

    /** Vervang elke vertaalbare leaf-string in een content-bag door de vertaling. */
    private function translateContent(array $content, string $locale): array
    {
        $map = $this->contentTranslations();

        array_walk_recursive($content, function (&$value) use ($map, $locale): void {
            if (is_string($value) && isset($map[$value][$locale])) {
                $value = $map[$value][$locale];
            }
        });

        return $content;
    }

    /**
     * Vertaalmap: NL-string => ['en' => ..., 'es' => ...]. NL blijft de bron; wat
     * hier niet in staat (URL's, slugs, enum-waarden, eigennamen) blijft ongemoeid.
     * Interne links in rich-text zijn per taal geprefixt (/en, /es).
     *
     * @return array<string, array{en: string, es: string}>
     */
    private function contentTranslations(): array
    {
        return [
            // ── Pagina-titels ──
            'Home' => ['en' => 'Home', 'es' => 'Inicio'],
            'Over El Pablo' => ['en' => 'About El Pablo', 'es' => 'Sobre El Pablo'],
            'Muziek' => ['en' => 'Music', 'es' => 'Música'],
            'Boeken' => ['en' => 'Book', 'es' => 'Reservar'],
            'Contact' => ['en' => 'Contact', 'es' => 'Contacto'],
            'Cookiebeleid' => ['en' => 'Cookie policy', 'es' => 'Política de cookies'],
            'Privacybeleid' => ['en' => 'Privacy policy', 'es' => 'Política de privacidad'],

            // ── Meta ──
            'El Pablo — Urban Latin DJ uit Antwerpen' => ['en' => 'El Pablo — Urban Latin DJ from Antwerp', 'es' => 'El Pablo — DJ Urban Latino de Amberes'],
            'Over El Pablo — Urban Latin DJ uit Antwerpen' => ['en' => 'About El Pablo — Urban Latin DJ from Antwerp', 'es' => 'Sobre El Pablo — DJ Urban Latino de Amberes'],
            'Muziek & sets — El Pablo' => ['en' => 'Music & sets — El Pablo', 'es' => 'Música y sesiones — El Pablo'],
            'Boek El Pablo — Urban Latin DJ' => ['en' => 'Book El Pablo — Urban Latin DJ', 'es' => 'Reserva a El Pablo — DJ Urban Latino'],
            'Contact — El Pablo' => ['en' => 'Contact — El Pablo', 'es' => 'Contacto — El Pablo'],
            'Cookiebeleid — El Pablo' => ['en' => 'Cookie policy — El Pablo', 'es' => 'Política de cookies — El Pablo'],
            'Privacybeleid — El Pablo' => ['en' => 'Privacy policy — El Pablo', 'es' => 'Política de privacidad — El Pablo'],
            'Boek El Pablo, urban latin DJ uit Antwerpen, voor clubs, privéfeesten, bruiloften en festivals. Latin, reggaeton & urban vibes voor onvergetelijke nachten.' => ['en' => 'Book El Pablo, urban latin DJ from Antwerp, for club nights, private parties, weddings and festivals. Latin, reggaeton & urban vibes for unforgettable nights.', 'es' => 'Reserva a El Pablo, DJ urban latino de Amberes, para clubes, fiestas privadas, bodas y festivales. Reggaetón, latin y urban para noches inolvidables.'],
            'Maak kennis met El Pablo, urban latin DJ uit Antwerpen. Meer dan tien jaar dansvloeren vullen met reggaeton, latin house en urban beats.' => ['en' => 'Meet El Pablo, urban latin DJ from Antwerp. Over ten years of filling dance floors with reggaeton, latin house and urban beats.', 'es' => 'Conoce a El Pablo, DJ urban latino de Amberes. Más de diez años llenando pistas con reggaetón, latin house y ritmos urbanos.'],
            'Beluister en download de sets van El Pablo: reggaeton, latin house, urban en afrobeats. Urban latin DJ uit Antwerpen.' => ['en' => 'Listen to and download El Pablo\'s sets: reggaeton, latin house, urban and afrobeats. Urban latin DJ from Antwerp.', 'es' => 'Escucha y descarga las sesiones de El Pablo: reggaetón, latin house, urban y afrobeats. DJ urban latino de Amberes.'],
            'Boek El Pablo voor je clubavond, privéfeest, bruiloft of festival. Vraag vrijblijvend je datum aan en ontvang snel een voorstel op maat.' => ['en' => 'Book El Pablo for your club night, private party, wedding or festival. Request your date with no obligation and get a tailored proposal quickly.', 'es' => 'Reserva a El Pablo para tu noche de club, fiesta privada, boda o festival. Solicita tu fecha sin compromiso y recibe pronto una propuesta a medida.'],
            'Vragen voor El Pablo, urban latin DJ uit Antwerpen? Stuur een bericht of bel rechtstreeks.' => ['en' => 'Questions for El Pablo, urban latin DJ from Antwerp? Send a message or call directly.', 'es' => '¿Preguntas para El Pablo, DJ urban latino de Amberes? Envía un mensaje o llama directamente.'],

            // ── CTA-labels ──
            'Boek El Pablo' => ['en' => 'Book El Pablo', 'es' => 'Reserva a El Pablo'],
            'Beluister mixes' => ['en' => 'Listen to the mixes', 'es' => 'Escucha las sesiones'],
            'Lees mijn verhaal' => ['en' => 'Read my story', 'es' => 'Lee mi historia'],
            'Bekijk alle sets' => ['en' => 'View all sets', 'es' => 'Ver todas las sesiones'],
            'Naar het aanvraagformulier' => ['en' => 'Go to the request form', 'es' => 'Ir al formulario de solicitud'],
            'Naar het boekingsformulier' => ['en' => 'Go to the booking form', 'es' => 'Ir al formulario de reserva'],

            // ── Home: hero ──
            'Urban Latin DJ · Antwerpen' => ['en' => 'Urban Latin DJ · Antwerp', 'es' => 'DJ Urban Latino · Amberes'],
            'Latin & urban vibes voor onvergetelijke nachten' => ['en' => 'Latin & urban vibes for unforgettable nights', 'es' => 'Vibras latinas y urbanas para noches inolvidables'],
            '<p>Van clubnacht tot strandfeest: ik breng het publiek samen met reggaeton, latin house en urban beats. Klaar om jouw dansvloer te laten koken.</p>' => ['en' => '<p>From club night to beach party: I bring the crowd together with reggaeton, latin house and urban beats. Ready to set your dance floor on fire.</p>', 'es' => '<p>De la noche de club a la fiesta en la playa: uno al público con reggaetón, latin house y ritmos urbanos. Listo para encender tu pista de baile.</p>'],
            'El Pablo achter de decks tijdens een clubnacht' => ['en' => 'El Pablo behind the decks during a club night', 'es' => 'El Pablo tras los platos durante una noche de club'],

            // ── Home: cards (aanbod) ──
            'Wat ik doe' => ['en' => 'What I do', 'es' => 'Lo que hago'],
            'Voor elk feest de juiste vibe' => ['en' => 'The right vibe for every party', 'es' => 'La vibra perfecta para cada fiesta'],
            '<p>Elke gelegenheid vraagt een eigen energie. Vertel me wat je plant, en ik zorg voor de soundtrack.</p>' => ['en' => '<p>Every occasion calls for its own energy. Tell me what you\'re planning and I\'ll take care of the soundtrack.</p>', 'es' => '<p>Cada ocasión pide su propia energía. Cuéntame qué planeas y yo me encargo de la banda sonora.</p>'],
            'Clubavonden' => ['en' => 'Club nights', 'es' => 'Noches de club'],
            'Privéfeesten' => ['en' => 'Private parties', 'es' => 'Fiestas privadas'],
            'Bruiloften' => ['en' => 'Weddings', 'es' => 'Bodas'],
            'Festivals & strandfeesten' => ['en' => 'Festivals & beach parties', 'es' => 'Festivales y fiestas en la playa'],
            'House, reggaeton en urban sets die de dansvloer de hele nacht vollen houden.' => ['en' => 'House, reggaeton and urban sets that keep the dance floor packed all night.', 'es' => 'Sets de house, reggaetón y urban que mantienen la pista llena toda la noche.'],
            'Verjaardag, tuinfeest of verrassing? Ik stem de muziek af op jouw gasten.' => ['en' => 'Birthday, garden party or surprise? I tailor the music to your guests.', 'es' => '¿Cumpleaños, fiesta en el jardín o sorpresa? Adapto la música a tus invitados.'],
            'Van romantische openingsdans tot een dansvloer die niet meer leegloopt.' => ['en' => 'From a romantic first dance to a dance floor that never empties.', 'es' => 'Desde el romántico primer baile hasta una pista que no se vacía.'],
            'Zomerse latin energie voor grote crowds, buiten én binnen.' => ['en' => 'Summery latin energy for big crowds, outdoors and indoors.', 'es' => 'Energía latina veraniega para grandes multitudes, dentro y fuera.'],

            // ── Home: over-teaser ──
            'Wie is El Pablo' => ['en' => 'Who is El Pablo', 'es' => 'Quién es El Pablo'],
            'De DJ die de dansvloer laat koken' => ['en' => 'The DJ who sets the dance floor on fire', 'es' => 'El DJ que enciende la pista'],
            '<p>Geboren en getogen in Antwerpen, met latin ritmes in het bloed. Al meer dan tien jaar draai ik in clubs en op feesten door heel Vlaanderen — altijd met één doel: iederéén op de dansvloer krijgen en houden.</p><p>Mijn kracht? Een dansvloer lezen en het juiste nummer op het juiste moment droppen.</p>' => ['en' => '<p>Born and raised in Antwerp, with latin rhythms in my blood. For over ten years I\'ve been spinning in clubs and at parties across Flanders — always with one goal: getting everyone on the dance floor and keeping them there.</p><p>My strength? Reading a dance floor and dropping the right track at the right moment.</p>', 'es' => '<p>Nacido y criado en Amberes, con ritmos latinos en la sangre. Desde hace más de diez años pincho en clubes y fiestas por toda Flandes, siempre con un objetivo: llevar a todos a la pista y mantenerlos ahí.</p><p>¿Mi fuerza? Leer la pista y soltar el tema perfecto en el momento justo.</p>'],

            // ── Home: mixes ──
            'Beluister m\'n sets' => ['en' => 'Listen to my sets', 'es' => 'Escucha mis sesiones'],
            '<p>Een DJ leeft van z\'n sets. Speel ze hier af — of download ze — en proef de sfeer voor je boekt.</p>' => ['en' => '<p>A DJ lives off their sets. Play them here — or download them — and get a taste before you book.</p>', 'es' => '<p>Un DJ vive de sus sesiones. Escúchalas aquí — o descárgalas — y siente el ambiente antes de reservar.</p>'],

            // ── Home: reviews ──
            'Wat ze zeggen' => ['en' => 'What they say', 'es' => 'Lo que dicen'],
            'Organisatoren aan het woord' => ['en' => 'Organizers speak', 'es' => 'Los organizadores opinan'],
            'El Pablo kreeg iederéén op de dansvloer en hield ze daar tot sluitingstijd. Onze club was uitverkocht en de sfeer was ongezien.' => ['en' => 'El Pablo got everyone on the dance floor and kept them there until closing time. Our club was sold out and the atmosphere was unreal.', 'es' => 'El Pablo llevó a todos a la pista y los mantuvo ahí hasta el cierre. Nuestro club colgó el cartel de completo y el ambiente fue increíble.'],
            'Voor ons trouwfeest gedroomd. Hij las de zaal perfect: rustig bij het diner, knallend na middernacht.' => ['en' => 'A dream for our wedding. He read the room perfectly: calm during dinner, explosive after midnight.', 'es' => 'Un sueño para nuestra boda. Leyó la sala a la perfección: tranquilo en la cena, explosivo tras la medianoche.'],
            'Professioneel, stipt en muzikaal top. Onze gasten praten er weken later nog over. Zeker een aanrader.' => ['en' => 'Professional, punctual and musically top-notch. Our guests were still talking about it weeks later. Highly recommended.', 'es' => 'Profesional, puntual y musicalmente excelente. Nuestros invitados seguían hablando de ello semanas después. Muy recomendable.'],
            'Organisator — Volmolen' => ['en' => 'Organizer — Volmolen', 'es' => 'Organizadora — Volmolen'],
            'Bruiloft in Kontich' => ['en' => 'Wedding in Kontich', 'es' => 'Boda en Kontich'],
            'Privéfeest — Antwerpen' => ['en' => 'Private party — Antwerp', 'es' => 'Fiesta privada — Amberes'],

            // ── Home: gallery ──
            'Sfeer' => ['en' => 'Atmosphere', 'es' => 'Ambiente'],
            'Zo ziet een El Pablo-avond eruit' => ['en' => 'This is what an El Pablo night looks like', 'es' => 'Así es una noche con El Pablo'],
            'DJ booth met crowd' => ['en' => 'DJ booth with crowd', 'es' => 'Cabina de DJ con público'],
            'Handen in de lucht op de dansvloer' => ['en' => 'Hands in the air on the dance floor', 'es' => 'Manos en alto en la pista'],
            'Koptelefoon op de mengtafel' => ['en' => 'Headphones on the mixer', 'es' => 'Auriculares en la mesa de mezclas'],
            'Draaitafel close-up' => ['en' => 'Turntable close-up', 'es' => 'Primer plano del tocadiscos'],
            'Volle dansvloer' => ['en' => 'Packed dance floor', 'es' => 'Pista abarrotada'],
            'Podiumlichten' => ['en' => 'Stage lights', 'es' => 'Luces de escenario'],

            // ── Home: faq ──
            'Goed om te weten' => ['en' => 'Good to know', 'es' => 'Bueno saber'],
            'Veelgestelde vragen' => ['en' => 'Frequently asked questions', 'es' => 'Preguntas frecuentes'],
            'In welke regio ben je beschikbaar?' => ['en' => 'Which areas do you cover?', 'es' => '¿En qué zonas estás disponible?'],
            '<p>Ik draai in heel Vlaanderen, met Antwerpen als thuisbasis. Voor feesten verder weg of in het buitenland: vraag gerust naar de mogelijkheden.</p>' => ['en' => '<p>I play all across Flanders, with Antwerp as my home base. For parties further away or abroad: feel free to ask about the options.</p>', 'es' => '<p>Pincho por toda Flandes, con Amberes como base. Para fiestas más lejos o en el extranjero: pregunta sin compromiso por las opciones.</p>'],
            'Voorzie je zelf geluid en licht?' => ['en' => 'Do you provide sound and lighting?', 'es' => '¿Aportas sonido e iluminación?'],
            '<p>Ik kan een volledige set-up voorzien of samenwerken met de installatie ter plaatse. We stemmen dit af bij de boeking.</p>' => ['en' => '<p>I can provide a full set-up or work with the on-site installation. We\'ll sort this out when you book.</p>', 'es' => '<p>Puedo aportar un equipo completo o trabajar con la instalación del lugar. Lo acordamos al reservar.</p>'],
            'Kan ik nummers doorgeven?' => ['en' => 'Can I request songs?', 'es' => '¿Puedo pedir canciones?'],
            '<p>Zeker. Je kan vooraf een wenslijst doorsturen. Ik lees ook live de dansvloer en pas de set daarop aan.</p>' => ['en' => '<p>Absolutely. You can send a wish list in advance. I also read the dance floor live and adapt the set accordingly.</p>', 'es' => '<p>Claro. Puedes enviar una lista de deseos por adelantado. También leo la pista en directo y adapto la sesión.</p>'],
            'Hoe boek ik een datum?' => ['en' => 'How do I book a date?', 'es' => '¿Cómo reservo una fecha?'],
            '<p>Vul het aanvraagformulier op de <a href="/boeken">boekingspagina</a> in. Ik antwoord meestal binnen 24 uur met een voorstel op maat.</p>' => ['en' => '<p>Fill in the request form on the <a href="/en/boeken">booking page</a>. I usually reply within 24 hours with a tailored proposal.</p>', 'es' => '<p>Rellena el formulario en la <a href="/es/boeken">página de reservas</a>. Suelo responder en 24 horas con una propuesta a medida.</p>'],

            // ── Home: cta ──
            'Klaar om de dansvloer te vullen?' => ['en' => 'Ready to fill the dance floor?', 'es' => '¿Listo para llenar la pista?'],
            '<p>Check mijn beschikbaarheid en maak van jouw feest een onvergetelijke nacht.</p>' => ['en' => '<p>Check my availability and turn your party into an unforgettable night.</p>', 'es' => '<p>Consulta mi disponibilidad y convierte tu fiesta en una noche inolvidable.</p>'],

            // ── Over ──
            'De DJ achter de decks' => ['en' => 'The DJ behind the decks', 'es' => 'El DJ tras los platos'],
            '<p>Antwerpse roots, latin ritmes in het bloed. Dit is het verhaal achter de sets.</p>' => ['en' => '<p>Antwerp roots, latin rhythms in the blood. This is the story behind the sets.</p>', 'es' => '<p>Raíces de Amberes, ritmos latinos en la sangre. Esta es la historia tras las sesiones.</p>'],
            'El Pablo aan de mengtafel' => ['en' => 'El Pablo at the mixer', 'es' => 'El Pablo en la mesa de mezclas'],
            'Het verhaal' => ['en' => 'The story', 'es' => 'La historia'],
            'Geboren om de dansvloer te vullen' => ['en' => 'Born to fill the dance floor', 'es' => 'Nacido para llenar la pista'],
            '<p>El Pablo groeide op in Antwerpen, waar latin en urban muziek al vroeg een tweede taal werden. Wat begon als draaien voor vrienden, groeide uit tot een vaste waarde in clubs en op feesten door heel Vlaanderen.</p><p>Meer dan tien jaar later is de missie nog altijd dezelfde: iederéén op de dansvloer krijgen — en daar houden tot de lichten aangaan.</p>' => ['en' => '<p>El Pablo grew up in Antwerp, where latin and urban music became a second language early on. What started as spinning for friends grew into a fixture in clubs and at parties across Flanders.</p><p>More than ten years later the mission is still the same: getting everyone on the dance floor — and keeping them there until the lights come on.</p>', 'es' => '<p>El Pablo creció en Amberes, donde la música latina y urbana fue pronto un segundo idioma. Lo que empezó pinchando para amigos se convirtió en un referente en clubes y fiestas por toda Flandes.</p><p>Más de diez años después la misión sigue siendo la misma: llevar a todos a la pista — y mantenerlos ahí hasta que se enciendan las luces.</p>'],
            'Mijn stijl' => ['en' => 'My style', 'es' => 'Mi estilo'],
            'Waar ik voor sta' => ['en' => 'What I stand for', 'es' => 'Lo que represento'],
            '<p>Geen vaste playlist, wel een duidelijke signatuur.</p>' => ['en' => '<p>No fixed playlist, but a clear signature.</p>', 'es' => '<p>Sin playlist fija, pero con un sello claro.</p>'],
            'Latin & reggaeton' => ['en' => 'Latin & reggaeton', 'es' => 'Latino y reggaetón'],
            'Urban & afrobeats' => ['en' => 'Urban & afrobeats', 'es' => 'Urban y afrobeats'],
            'Ik lees de dansvloer' => ['en' => 'I read the dance floor', 'es' => 'Leo la pista'],
            'Voor iedereen' => ['en' => 'For everyone', 'es' => 'Para todos'],
            'Van klassieke salsa-vibes tot de nieuwste reggaeton-hits.' => ['en' => 'From classic salsa vibes to the latest reggaeton hits.', 'es' => 'Desde vibras clásicas de salsa hasta los últimos éxitos de reggaetón.'],
            'Strakke urban beats en afrobeats die de energie hoog houden.' => ['en' => 'Tight urban beats and afrobeats that keep the energy high.', 'es' => 'Ritmos urbanos y afrobeats que mantienen la energía alta.'],
            'Het juiste nummer op het juiste moment — live aangevoeld.' => ['en' => 'The right track at the right moment — felt live.', 'es' => 'El tema perfecto en el momento justo — sentido en directo.'],
            'Inclusief en vrouwvriendelijk: iedereen voelt zich welkom.' => ['en' => 'Inclusive and women-friendly: everyone feels welcome.', 'es' => 'Inclusivo y amable con las mujeres: todos se sienten bienvenidos.'],
            'Momenten van de dansvloer' => ['en' => 'Moments from the dance floor', 'es' => 'Momentos de la pista'],
            'Laat El Pablo jouw feest maken' => ['en' => 'Let El Pablo make your party', 'es' => 'Deja que El Pablo haga tu fiesta'],
            '<p>Vraag vrijblijvend je datum aan en check de beschikbaarheid.</p>' => ['en' => '<p>Request your date with no obligation and check availability.</p>', 'es' => '<p>Solicita tu fecha sin compromiso y consulta la disponibilidad.</p>'],

            // ── Muziek ──
            'Sets & mixes' => ['en' => 'Sets & mixes', 'es' => 'Sesiones y mixes'],
            '<p>Speel de sets hier af of download ze. Proef de sfeer van een El Pablo-avond.</p>' => ['en' => '<p>Play the sets here or download them. Get a taste of an El Pablo night.</p>', 'es' => '<p>Escucha las sesiones aquí o descárgalas. Siente el ambiente de una noche con El Pablo.</p>'],
            'El Pablo live' => ['en' => 'El Pablo live', 'es' => 'El Pablo en directo'],
            'Alle sets' => ['en' => 'All sets', 'es' => 'Todas las sesiones'],
            'Beluister m\'n muziek' => ['en' => 'Listen to my music', 'es' => 'Escucha mi música'],
            'Deze vibe op jouw feest?' => ['en' => 'This vibe at your party?', 'es' => '¿Esta vibra en tu fiesta?'],
            '<p>Boek El Pablo en breng de dansvloer tot leven.</p>' => ['en' => '<p>Book El Pablo and bring the dance floor to life.</p>', 'es' => '<p>Reserva a El Pablo y da vida a la pista.</p>'],

            // ── Boeken ──
            'Boek El Pablo voor jouw feest' => ['en' => 'Book El Pablo for your party', 'es' => 'Reserva a El Pablo para tu fiesta'],
            '<p>Vertel me over je gelegenheid en ontvang snel een voorstel op maat. Vrijblijvend en zonder verplichting.</p>' => ['en' => '<p>Tell me about your occasion and get a tailored proposal quickly. Free and with no obligation.</p>', 'es' => '<p>Cuéntame sobre tu evento y recibe pronto una propuesta a medida. Gratis y sin compromiso.</p>'],
            'Feestende crowd' => ['en' => 'Partying crowd', 'es' => 'Multitud de fiesta'],
            'Zo werkt het' => ['en' => 'How it works', 'es' => 'Cómo funciona'],
            'In drie stappen geboekt' => ['en' => 'Booked in three steps', 'es' => 'Reservado en tres pasos'],
            '1. Vraag aan' => ['en' => '1. Request', 'es' => '1. Solicita'],
            'Vul het formulier in met je datum, locatie en type gelegenheid.' => ['en' => 'Fill in the form with your date, location and type of occasion.', 'es' => 'Rellena el formulario con tu fecha, lugar y tipo de evento.'],
            '2. Voorstel op maat' => ['en' => '2. Tailored proposal', 'es' => '2. Propuesta a medida'],
            'Je ontvangt meestal binnen 24 uur een concreet voorstel met prijs.' => ['en' => 'You\'ll usually receive a concrete proposal with a price within 24 hours.', 'es' => 'Normalmente recibirás una propuesta concreta con precio en 24 horas.'],
            '3. Feesten' => ['en' => '3. Party', 'es' => '3. A la fiesta'],
            'We stemmen de details af en ik zorg voor een onvergetelijke nacht.' => ['en' => 'We align the details and I\'ll make it an unforgettable night.', 'es' => 'Ajustamos los detalles y me encargo de una noche inolvidable.'],
            'Praktische vragen' => ['en' => 'Practical questions', 'es' => 'Preguntas prácticas'],
            'Hoe snel krijg ik antwoord?' => ['en' => 'How quickly will I get a reply?', 'es' => '¿Con qué rapidez recibiré respuesta?'],
            '<p>Meestal binnen 24 uur. Voor last-minute aanvragen kan je me ook bellen.</p>' => ['en' => '<p>Usually within 24 hours. For last-minute requests you can also call me.</p>', 'es' => '<p>Normalmente en 24 horas. Para solicitudes de última hora también puedes llamarme.</p>'],
            'Wat kost een boeking?' => ['en' => 'What does a booking cost?', 'es' => '¿Cuánto cuesta una reserva?'],
            '<p>Dat hangt af van de datum, duur en locatie. Vul het formulier in voor een voorstel op maat.</p>' => ['en' => '<p>That depends on the date, duration and location. Fill in the form for a tailored proposal.</p>', 'es' => '<p>Depende de la fecha, la duración y el lugar. Rellena el formulario para una propuesta a medida.</p>'],
            'Voorzie je geluid en licht?' => ['en' => 'Do you provide sound and lighting?', 'es' => '¿Aportas sonido e iluminación?'],
            '<p>Ik kan een volledige set-up voorzien of samenwerken met de installatie ter plaatse.</p>' => ['en' => '<p>I can provide a full set-up or work with the on-site installation.</p>', 'es' => '<p>Puedo aportar un equipo completo o trabajar con la instalación del lugar.</p>'],
            'Aanvraag' => ['en' => 'Request', 'es' => 'Solicitud'],
            'Vraag je datum aan' => ['en' => 'Request your date', 'es' => 'Solicita tu fecha'],
            '<p>Hoe meer je invult, hoe gerichter mijn voorstel. Alle velden met een datum of type helpen me meteen op weg.</p>' => ['en' => '<p>The more you fill in, the more precise my proposal. Any field with a date or type helps me get started right away.</p>', 'es' => '<p>Cuanto más completes, más precisa será mi propuesta. Cada campo con fecha o tipo me ayuda a empezar enseguida.</p>'],

            // ── Contact ──
            'Neem contact op' => ['en' => 'Get in touch', 'es' => 'Ponte en contacto'],
            '<p>Een vraag of gewoon zin om te overleggen? Stuur een bericht of bel rechtstreeks — ik hoor het graag.</p>' => ['en' => '<p>A question or just want to chat? Send a message or call directly — I\'d love to hear from you.</p>', 'es' => '<p>¿Una pregunta o simplemente quieres hablar? Envía un mensaje o llama directamente — me encantará saber de ti.</p>'],
            'Clubsfeer' => ['en' => 'Club atmosphere', 'es' => 'Ambiente de club'],
            'Bericht' => ['en' => 'Message', 'es' => 'Mensaje'],
            'Stuur een bericht' => ['en' => 'Send a message', 'es' => 'Envía un mensaje'],
            '<p>Vul het formulier in en ik antwoord zo snel mogelijk. Wil je meteen een datum vastleggen? Gebruik dan het <a href="/boeken">boekingsformulier</a>.</p>' => ['en' => '<p>Fill in the form and I\'ll reply as soon as possible. Want to lock in a date right away? Use the <a href="/en/boeken">booking form</a>.</p>', 'es' => '<p>Rellena el formulario y responderé lo antes posible. ¿Quieres fijar una fecha ya? Usa el <a href="/es/boeken">formulario de reserva</a>.</p>'],
            'Liever meteen een datum vastleggen?' => ['en' => 'Prefer to lock in a date right away?', 'es' => '¿Prefieres fijar una fecha ya?'],
            '<p>Vraag vrijblijvend je boeking aan met alle details in één keer.</p>' => ['en' => '<p>Request your booking with no obligation and all details in one go.</p>', 'es' => '<p>Solicita tu reserva sin compromiso con todos los detalles de una vez.</p>'],
        ];
    }

    private function seedMenus(Page $home, Page $over, Page $muziek, Page $boeken, Page $contact): void
    {
        // Hoofdmenu — "Home" staat bewust NIET in de nav (het logo linkt naar
        // home), en "Boek El Pablo" zit al als CTA-knop in de header.
        $main = Menu::updateOrCreate(['location' => 'main'], ['name' => 'Hoofdmenu']);
        $main->allItems()->delete();
        foreach ([
            ['label' => 'Over', 'page_id' => $over->id],
            ['label' => 'Muziek', 'page_id' => $muziek->id],
            ['label' => 'Contact', 'page_id' => $contact->id],
        ] as $i => $item) {
            $main->allItems()->create([...$item, 'position' => $i]);
        }

        // Footer 1 — Navigatie (hier mag Boeken wél expliciet staan)
        $f1 = Menu::updateOrCreate(['location' => 'footer_1'], ['name' => 'Footer navigatie', 'title' => 'Navigatie']);
        $f1->allItems()->delete();
        foreach ([
            ['label' => 'Home', 'page_id' => $home->id],
            ['label' => 'Over El Pablo', 'page_id' => $over->id],
            ['label' => 'Muziek', 'page_id' => $muziek->id],
            ['label' => 'Boeken', 'page_id' => $boeken->id],
            ['label' => 'Contact', 'page_id' => $contact->id],
        ] as $i => $item) {
            $f1->allItems()->create([...$item, 'position' => $i]);
        }

        // Footer 2 — Aanbod (naar de boekingspagina)
        $f2 = Menu::updateOrCreate(['location' => 'footer_2'], ['name' => 'Footer aanbod', 'title' => 'Aanbod']);
        $f2->allItems()->delete();
        foreach ([
            ['label' => 'Clubavonden', 'url' => '/boeken'],
            ['label' => 'Privéfeesten', 'url' => '/boeken'],
            ['label' => 'Bruiloften', 'url' => '/boeken'],
            ['label' => 'Festivals', 'url' => '/boeken'],
        ] as $i => $item) {
            $f2->allItems()->create([...$item, 'position' => $i]);
        }

        // Footer 3 — Juridisch
        $f3 = Menu::updateOrCreate(['location' => 'footer_3'], ['name' => 'Footer juridisch', 'title' => 'Juridisch']);
        $f3->allItems()->delete();
        foreach ([
            ['label' => 'Cookiebeleid', 'url' => '/cookiebeleid'],
            ['label' => 'Privacybeleid', 'url' => '/privacybeleid'],
        ] as $i => $item) {
            $f3->allItems()->create([...$item, 'position' => $i]);
        }
    }

    private function seedSettings(Page $boeken): void
    {
        Setting::set(SiteHeader::KEY, [
            'logo' => null,
            'name' => 'El Pablo',
            'subtitle' => 'Urban Latin DJ',
            'cta' => [
                'label' => 'Boek El Pablo',
                'link_type' => 'page',
                'page_id' => $boeken->id,
                'href' => '/boeken',
            ],
        ]);

        Setting::set(SiteFooter::KEY, [
            'contact' => [
                'visit_label' => 'Antwerpen',
                'address' => 'Antwerpen, België',
                'reservations_label' => 'Bel',
                'phone' => '+32 497 19 09 83',
                'phone_hours' => '',
                'mail_label' => 'Mail',
                'email' => 'info@el-pablo.com',
                'email_subtext' => '',
            ],
            'brand' => [
                'logo' => null,
                'name' => 'El Pablo',
                'subtitle' => 'Urban Latin DJ',
                'tagline' => 'Urban latin DJ uit Antwerpen. Latin, reggaeton & urban vibes voor clubs, privéfeesten, bruiloften en festivals.',
            ],
            'social' => [
                'instagram' => 'https://instagram.com',
                'facebook' => 'https://facebook.com',
                'youtube' => 'https://youtube.com',
            ],
        ]);
    }
}
