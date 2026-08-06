<?php

namespace Database\Seeders;

use App\Models\Redirect;
use Illuminate\Database\Seeder;

/**
 * Redirects van de oude WordPress-site (www.el-pablo.com) naar de pagina's van
 * de nieuwe Laravel-site. Bron: de Yoast-sitemaps van de oude site
 * (page/post/mixtapes/events/downloads/party_shots/category/tag/author),
 * gecrawld op 2026-08-06.
 *
 * Paden staan RELATIEF opgeslagen (geen domein): oude en nieuwe site draaien na
 * go-live op hetzelfde domein, dus bij het live zetten hoeft er niets in de DB
 * vervangen te worden. De HandleRedirects-middleware matcht op $request->path()
 * en trimt slashes, dus de WordPress-URL's mét trailing slash matchen ook.
 *
 * Keuzes (afgestemd met Pieter):
 * - Mixtapes, mixtape-categorieën en downloads -> /muziek (de nieuwe site
 *   bundelt dat op één pagina).
 * - /contact-bookings splitste de oude site niet; de nieuwe site wel. De
 *   boekingspagina is de conversiepagina, dus -> /boeken.
 * - Events en party shots -> / (nog geen equivalent). Komt er later een
 *   events-pagina, wijzig dan het doel van deze regels naar /events in plaats
 *   van ze te verwijderen — dan blijven de oude links hun waarde doorgeven.
 * - Oude blogposts (/news, /hello-world, /el-pablo-pic-*, /marco-carola-*,
 *   /music-on-world-off, /music-off-world-on) krijgen BEWUST geen redirect:
 *   dat was grotendeels WordPress-ruis zonder tegenhanger. Ze mogen 404'en.
 * - /ajax was een technische WordPress-pagina en verdwijnt eveneens zonder
 *   redirect.
 *
 * Idempotent: updateOrCreate op 'from', dus los herdraaien (ook op live) is veilig.
 */
class RedirectSeeder extends Seeder
{
    public function run(): void
    {
        // Map: doel-pad (nieuw) => [oude paden].
        $map = [
            '/muziek' => [
                // Mixtapes-overzicht + alle losse mixtapes.
                '/mixtapes',
                '/mixtapes/pure-el-pablo-2020-vol-1',
                '/mixtapes/latin-sessions-2019-vol-1',
                '/mixtapes/reggaeton-sessions-2018-vol-1',
                '/mixtapes/urban-sessions-2019-vol-2',
                '/mixtapes/urban-sessions-2019-vol-1',
                '/mixtapes/urban-sessions-2018-vol-2',
                '/mixtapes/kizomba-sessions-2018-vol-2-2',
                '/mixtapes/salchata-2018-vol-1',
                '/mixtapes/afro-latin-sessions-2018-vol-1',
                '/mixtapes/urban-sessions-2018-vol-1',
                '/mixtapes/kizomba-sessions-2018-vol-1',
                '/mixtapes/latin-sessions-2017-vol-2',
                '/mixtapes/urban-sessions-2015-vol-2',
                '/mixtapes/reggaeton-sessions-2020-vol-1',
                '/mixtapes/kizomba-sessions-2020-vol-1',
                '/mixtapes/tropical-house-sessions-2020-vol-1',
                '/mixtapes/salchata-2021-vol-1',
                '/mixtapes/tropical-house-sessions-2021-vol-1',
                '/mixtapes/salchata-2021-vol-2',
                '/mixtapes/el-pablo-live-chique-beach-01-07-2022',

                // Mixtape-categorieën (genre-archieven).
                '/mixtapes_categorie/bachata',
                '/mixtapes_categorie/basshall',
                '/mixtapes_categorie/dancehall',
                '/mixtapes_categorie/kizomba',
                '/mixtapes_categorie/kizomba-douceur',
                '/mixtapes_categorie/latin',
                '/mixtapes_categorie/moombahton',
                '/mixtapes_categorie/rb',
                '/mixtapes_categorie/reggaeton',
                '/mixtapes_categorie/salsa',
                '/mixtapes_categorie/taraxxa',
                '/mixtapes_categorie/tropical-house',
                '/mixtapes_categorie/urban',
                '/mixtapes_categorie/urban-kiz',

                // Downloads (intro's/transitions) — staan nu op de muziekpagina.
                '/downloads',
                '/downloads/further-up-el-pablo-intro',
                '/downloads/rumba-transition-up',
            ],

            '/boeken' => [
                '/contact-bookings',
            ],

            '/over' => [
                '/urban-latin-dj',
            ],

            '/privacybeleid' => [
                '/privacy-policy',
            ],

            '/' => [
                // Events: geen equivalent op de nieuwe site. Wijzig deze naar
                // /events zodra die pagina bestaat.
                '/events',
                '/events/dance-holiday-2020',
                '/events/de-schrikkel-fuif',
                '/events/latin-vibes',
                '/events/ritmo-sabroso',
                '/events/rb-night',
                '/events/salsa-the-beach',

                // Party shots: idem, fotoreportages zonder tegenhanger.
                '/party_shots',
                '/party_shots/el-pablo-latin-lake',
                '/party_shots/el-pablo-dance-holiday-bodrum-2019',
                '/party_shots/ark-beach',

                // WordPress-archieven (categorie, tag, auteur).
                '/category/profiles-pics',
                '/category/uncategorized',
                '/tag/rnb',
                '/tag/urban',
                '/author/wgmaster',
            ],
        ];

        foreach ($map as $to => $fromPaths) {
            foreach ($fromPaths as $from) {
                Redirect::updateOrCreate(
                    ['from' => $from],
                    ['to' => $to, 'status_code' => 301],
                );
            }
        }
    }
}
