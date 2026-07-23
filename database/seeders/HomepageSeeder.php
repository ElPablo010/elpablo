<?php

namespace Database\Seeders;

use App\Models\Menu;
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

    private function seedHomepage(): Page
    {
        $page = Page::updateOrCreate(
            ['locale' => 'nl', 'slug' => 'home'],
            [
                'title' => 'Home',
                'is_homepage' => true,
                'published' => true,
                'meta_title' => 'El Pablo — Urban Latin DJ uit Antwerpen',
                'meta_description' => 'Boek El Pablo, urban latin DJ uit Antwerpen, voor clubavonden, privéfeesten, bruiloften en festivals. Latin, reggaeton & urban vibes voor onvergetelijke nachten.',
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
                    'items' => [
                        ['title' => 'Latin Vibes', 'subtitle' => 'Reggaeton & latin house', 'audio' => 'https://www.el-pablo.com/wp-content/uploads/2025/05/Latin-Vibes.mp3', 'cover' => $this->img('1544986581-efac024faf62', 800), 'allow_download' => true],
                        ['title' => 'Live set @ Mokta Mee', 'subtitle' => 'Urban & latin · live opname', 'audio' => 'https://www.el-pablo.com/wp-content/uploads/2025/05/Live-set-Mokta-Mee.mp3', 'cover' => $this->img('1524368535928-5b5e00ddc76b', 800), 'allow_download' => true],
                    ],
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

        $latin = 'https://www.el-pablo.com/wp-content/uploads/2025/05/Latin-Vibes.mp3';
        $live = 'https://www.el-pablo.com/wp-content/uploads/2025/05/Live-set-Mokta-Mee.mp3';

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
                    'items' => [
                        ['title' => 'Latin Vibes', 'subtitle' => 'Reggaeton & latin house', 'audio' => $latin, 'cover' => $this->img('1544986581-efac024faf62', 800), 'allow_download' => true],
                        ['title' => 'Live set @ Mokta Mee', 'subtitle' => 'Urban & latin · live opname', 'audio' => $live, 'cover' => $this->img('1524368535928-5b5e00ddc76b', 800), 'allow_download' => true],
                        ['title' => 'Reggaeton Heat', 'subtitle' => 'Reggaeton · 60 min', 'audio' => $latin, 'cover' => $this->img('1518972559570-7cc1309f3229', 800), 'allow_download' => true],
                        ['title' => 'Beach Club Sunset', 'subtitle' => 'Latin house · zomerset', 'audio' => $live, 'cover' => $this->img('1533174072545-7a4b6ad7a6c3', 800), 'allow_download' => false],
                        ['title' => 'Urban Night Vol. 3', 'subtitle' => 'Urban & afrobeats', 'audio' => $latin, 'cover' => $this->img('1470229722913-7c0e2dbbafd3', 800), 'allow_download' => true],
                        ['title' => 'Carnaval Special', 'subtitle' => 'Feestset · latin', 'audio' => $live, 'cover' => $this->img('1492684223066-81342ee5ff30', 800), 'allow_download' => true],
                    ],
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
        $this->ensureLegalPage('cookiebeleid', 'Cookiebeleid', $this->cookieBody());
        $this->ensureLegalPage('privacybeleid', 'Privacybeleid', $this->privacyBody());
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

    private function cookieBody(): string
    {
        $name = config('app.name');
        $email = config('mail.from.address');

        return <<<HTML
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
            HTML;
    }

    private function privacyBody(): string
    {
        $name = config('app.name');
        $email = config('mail.from.address');

        return <<<HTML
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
            HTML;
    }

    /**
     * Dupliceer elke NL-pagina (met secties) naar EN en ES. De inhoud is
     * voorlopig een kopie van het Nederlands (placeholder) — de effectieve
     * vertaling gebeurt later via AI. Zelfde slug per taal (unique op [locale, slug]).
     */
    private function seedTranslations(): void
    {
        $sourcePages = Page::query()->where('locale', 'nl')->with('sections')->get();

        foreach ($sourcePages as $source) {
            foreach (['en', 'es'] as $locale) {
                $copy = Page::updateOrCreate(
                    ['locale' => $locale, 'slug' => $source->slug],
                    [
                        'title' => $source->title,
                        'is_homepage' => $source->is_homepage,
                        'published' => $source->published,
                        'translation_of' => $source->id,
                        'meta_title' => $source->meta_title,
                        'meta_description' => $source->meta_description,
                        'meta_robots' => $source->meta_robots,
                    ],
                );

                $copy->sections()->delete();

                foreach ($source->sections as $section) {
                    $copy->sections()->create([
                        'section_type' => $section->section_type,
                        'position' => $section->position,
                        'content' => $section->content,
                        'locale' => $locale,
                        'translation_of' => $section->id,
                    ]);
                }
            }
        }
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
