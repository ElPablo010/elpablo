# El Pablo — projectinstructies

Publieke website voor **El Pablo**, een Urban Latin DJ uit Antwerpen. Vervangt de
huidige site op **el-pablo.com**. Opgezet met de `new-website`-skill (Filament-admin
+ TALL-stack website-builder).

## Project-keuzes

| Keuze | Waarde |
|---|---|
| Admin-UI taal | Nederlands |
| Publieke site | **Meertalig**: NL (hoofd), EN, ES — via `locale` + `translation_of` |
| Domein | el-pablo.com (bestaande site wordt vervangen) |
| Klant-accounts | Nee — Filament (`/admin`) is het enige login-systeem |
| Database | MySQL (lokaal via Herd, `elpablo_new` / `elpablo_new_test`) |
| Hosting / deploy | Combell shared hosting (`deploy-combell`-skill) |
| Primaire kleur | `#E01B4B` (magenta-rood) — Tailwind `primary-600` + Filament panel |
| Secundaire kleur | `#443f3f` (donker warmgrijs) — Tailwind `secondary-700` |
| Lettertype | Nog te bepalen bij het design (nu default Instrument Sans) |

## Stack & conventies

Zie de user-level `~/.claude/CLAUDE.md` en de website-architectuur-context voor de
volledige conventies (media-velden via `MediaPickerField`, dropdowns alfabetisch,
buttons `cursor-pointer`, enums met `HasLabel`+`HasColor`, code/commits Engels /
admin-UI + validatie Nederlands).

Kleurschaal staat in `resources/css/app.css` (`@theme`): volledige `primary`- en
`secondary`-schaal. `SectionBackground` gebruikt `bg-primary-600` voor de
merk-achtergrond.

## Meertaligheid — aandachtspunt

De site is meertalig (NL/EN/ES). Pagina's dragen een `locale` en koppelen via
`translation_of`. Bij het bouwen van de publieke frontend en menu's: houd rekening
met een taalwissel en per-locale content. De hoofdtaal is NL.

## Frontend & meertaligheid — stand van zaken

Design volledig uitgewerkt met `design-website` (dark urban nightlife, Anton +
Inter). Pagina's: Home, Over, Muziek (inline audiospelers + download), Boeken
(apart boekingsformulier), Contact, + juridisch (Cookiebeleid, Privacybeleid).

**Meertalig (NL/EN/ES)** is volledig opgezet én vertaald:
- NL draait op de root; EN/ES onder `/en` en `/es` (zie `routes/web.php` +
  `PublicPageController` + `App\Support\Locale`).
- Pagina's delen dezelfde slug per taal (`unique(['locale','slug'])`); interne
  links worden gelokaliseerd via `Locale::href()`.
- **Pagina-content** (koppen, teksten, FAQ, reviews, legal) is vertaald via een
  vertaalmap in `HomepageSeeder` (`contentTranslations()`): NL blijft de bron, de
  EN/ES-secties krijgen de vertaalde waarden. Legal-bodies via `cookieBody()`/
  `privacyBody()` per taal.
- **UI-chrome** (nav-labels, footer, cookiebanner, knoppen) via Laravel
  `lang/en.json` + `lang/es.json` met `__()`.
- **Formulieren** (contact + boeking): labels, placeholders, keuzelijsten én
  Livewire-validatieberichten vertaald. De trait `App\Livewire\Concerns\PersistsLocale`
  bewaart de locale in de component en herstelt ze bij élke Livewire-render, zodat
  de taal klopt ook na een re-render (Livewire post naar /livewire/update zonder
  locale-prefix).

## AI-vertaling (make-multilingual)

De AI-vertaallaag uit ark-van-noe is geïnstalleerd via de `make-multilingual`-skill
(2026-08-14), aangepast aan dit project:

- **"Vertalen met AI"** (🗣-knop) op de pagina- én events-lijst, plus een bulk-actie
  die als **queued batch** draait (`TranslateRecordJob`, database-queue; de
  scheduler start elke minuut `queue:work --stop-when-empty` — live is daarvoor de
  bestaande `schedule:run`-cron genoeg). Klaar-melding in het belletje.
- **Pagina's**: `PageTranslator` vertaalt paginavelden + sectie-JSON en bewaart de
  vertaling als gekoppelde rij met **dezelfde slug** (gedeelde slug per taal —
  links lokaliseren bij het renderen via `Locale::href()`, dus opgeslagen
  `href`/`page_id` blijven bewust NL-vormig; er is géén link-remapping zoals in ark).
- **Events**: `EventTranslator` schrijft naar de bestaande `event_translations`-rijen
  (naam, korte + lange beschrijving; HTML blijft intact).
- **API-sleutel**: `Setting 'anthropic_api_key'` (Instellingen → Algemeen, gedeeld
  met de SEO-adviseur); `.env ANTHROPIC_API_KEY` is de terugval. Model via
  `ANTHROPIC_MODEL` (default `claude-opus-5`).
- **Harde regel**: nieuw sectieveld dat géén tekst is (kleur, layout, id, URL)?
  Voeg de sleutel toe aan `$skipKeys` in
  `app/Services/Translation/Concerns/TranslatesContentArrays.php`, anders wordt
  bv. een kleurwaarde mee vertaald en stort de sectie stil in.
- Vaste UI-teksten blijven via `lang/en.json`/`lang/es.json` (`__()`) — die vertaal
  je nog handmatig; alleen pagina- en eventcontent loopt via de AI-knoppen.

- **Extra's van een event** (groepstafel, drankkaart) gaan in dezelfde call mee
  en landen in `name_{locale}`/`description_{locale}` op `event_extras`.
- **Menu's** (*Website → Menu's*): één set items voor alle talen, enkel het label
  (`label_en`/`label_es`) en de footertitel (`title_en`/`title_es`) verschillen.
  Knop **"Vertalen met AI"** vult de EN/ES-velden in het formulier (nog niet in
  de DB) — controleren en dan Opslaan. Leeg veld = terugval op `lang/{locale}.json`
  en daarna het NL-label (`MenuItem::labelFor()`, `Menu::titleFor()`).
- De paginakeuze in `PageLinkField` (menu's, CTA's, footer) toont **enkel
  NL-pagina's**: opgeslagen links zijn NL-vormig en worden per taal gelokaliseerd.

Tests: `tests/Feature/AiTranslationTest.php`, `tests/Feature/MenuTranslationTest.php`.

## SEO-monitoring (seo-analytics)

De `seo-analytics`-skill draait: DataForSEO-tracking, een wekelijkse AI-briefing
en een goedkeuringsdashboard dat adviezen omzet in publiceerbare content.

Admin: sidebar-groep **SEO** → *Overzicht, Keywords, Acties, Instellingen*.
De weekcron staat in `routes/console.php` (maandag 7:00, `seo:weekly-report`) en
vereist een draaiende **queue-worker** voor "Ververs cijfers" op het dashboard.

**Project-specifieke afwijkingen van de skill** (belangrijk bij het porten van
bugfixes):
- De skill verwacht een `GeneralSettings`-pagina uit een nieuwere `new-website`;
  die ontbrak hier en is toegevoegd als `App\Filament\Pages\GeneralSettings`
  (*Instellingen → Algemeen*): merknaam, omschrijving, Anthropic-key en "feiten
  voor AI". De SEO-laag leest die sleutels alleen. Bewust **regel voor regel
  gelijk** aan de versie in `webgoeroe` (enkel de twee helperText-voorbeelden
  verschillen), zodat de latere extractie naar een gedeelde plugin schoon blijft.
- Het sectietype `rich_text` bestaat hier niet — de actie-applier schrijft
  `hero → text → faq → cta`. Wijzigt het sectiecontract, pas dan
  `SeoActionApplier` én `SeoAdvisorService::buildLandingSections()` samen aan.
- **Meertaligheid**: de SEO-acties werken uitsluitend op NL (`Locale::DEFAULT`).
  Pagina-resolutie, slug-uniekheid, de homepage-CTA en de grounding zijn expliciet
  op die locale gescoped — zonder dat zou een actie op een EN/ES-vertaling kunnen
  landen (alle talen delen dezelfde slugs).
- `text.blade.php` kiest zijn kopniveau nu op basis van `position`: bovenaan een
  pagina H1 (juridische pagina's), eronder H2. Zo krijgt een gegenereerde
  landingspagina met hero geen tweede H1.

- **Inhouds-guards in `normalizeAction()`** (geport uit bl-members, aug 2026):
  `App\Support\FaqQuestionMatcher` filtert FAQ-voorstellen die inhoudelijk
  overlappen met bestaande vragen (subset of >2/3 gedeelde kernwoorden;
  vraagwoorden blijven betekenisvol), `FAQ_MAX_QUESTIONS` (7) begrenst het
  aantal vragen per blok, `create_page` wordt geweigerd als een bestaande
  NL-pagina het keyword al dekt via titel/slug, en `optimize_meta` mag op een
  pagina die < `RECENT_DAYS` (60) geleden aangeraakt is enkel nog lege
  metavelden invullen. De grounding voedt het model met de bestaande
  FAQ-vragen per pagina (+ VOL-markering) en NIEUW/RECENT-datummarkeringen.
  Fingerprints bevatten sindsdien de inhoud (`page-{id}|{contentKey}`), zodat
  de dedup niet meer permanent per pagina is.

Bewaakt door `tests/Feature/SeoActionsTest.php`,
`tests/Feature/SeoActionGuardsTest.php` en `tests/Unit/FaqQuestionMatcherTest.php`.

### Nog in te vullen (admin)
- [ ] **Instellingen → Algemeen**: merknaam, omschrijving, Anthropic API-key en
      "feiten voor AI".
- [ ] **SEO → Instellingen**: DataForSEO login/wachtwoord, doeldomein
      el-pablo.com, locatiecode 2056 (België), taalcode `nl`, rapport-ontvanger
      en de GEO-prompts.
- [ ] **SEO → Keywords**: de eerste kernzinnen toevoegen, daarna "Ververs cijfers".

## Taal van de admin — APP_LOCALE=nl

De admin-UI is volledig Nederlands. Dat hangt aan één instelling:

```
APP_LOCALE=nl
APP_FALLBACK_LOCALE=nl
```

Stond eerder op `en`, waardoor Filament "Save changes", "Cancel", "Collapse all"
en Engelse validatiefouten toonde. Met `nl` pikken drie lagen tegelijk de
Nederlandse teksten op:

1. **Filament** — levert NL mee onder `vendor/filament` (per pakket in
   `resources/lang/nl`). Niets te publiceren.
2. **FilePond** (de upload-dropzone) — Filament kiest de JS-locale op
   `app.locale`; `nl-nl` zit al in de bundel.
3. **Laravel-validatie** — `lang/nl/` (validation, auth, passwords, pagination)
   staat in de repo. Onder `attributes` staan veldnamen voor de gevallen waarin
   Filament geen label kan doorgeven.

De **publieke** site zet zijn eigen locale per request (`PublicPageController` →
`app()->setLocale()`), dus NL/EN/ES blijven werken. De fallback staat bewust ook
op `nl`: ontbreekt een sleutel in `lang/en.json` of `lang/es.json`, dan verschijnt
de Nederlandse brontekst in plaats van een rauwe sleutel.

Bewaakt door `tests/Feature/DutchAdminTest.php`.

## Mediavelden: nooit een kale FileUpload

Media horen als **URL-string** in de content-bag (zie de architectuur-conventie).
Een kale `FileUpload` breekt dat: die kan alleen een pad op zijn eigen disk tonen.
Staat er een absolute URL in — een geseede demo, of content van de oude site —
dan vindt hij het bestand niet, toont een leeg veld, en schrijft bij opslaan
`null` terug. Met `->required()` erbij blokkeert dat het bewaren van de héle
pagina, met de foutmelding op een tab waar je niet werkt.

Gebruik daarom altijd een picker-veld:
- Afbeeldingen → `MediaPickerField::make()`
- Audio → `AudioPickerField::make()`

Beide zijn een readonly `TextInput` met de URL + upload-actie eronder. Helptekst
via de **parameter**, niet `->helperText()` (die overschrijft dezelfde
`belowContent`-slot als de acties, waardoor de knoppen verdwijnen).

`tests/Feature/SeededContentIsEditableTest.php` opent elke geseede pagina in de
admin en slaat haar op — dat vangt dit soort blokkades vóór de klant ze vindt.

## Grote mp3-uploads (DJ-sets)

Een DJ-set van een uur is al snel 80-150MB. Drie limieten moeten meebewegen —
loopt er één achter, dan faalt de upload met "Error during upload":

| Laag | Waar | Staat op |
|---|---|---|
| Filament-veld | `AudioPickerField` (upload-modal) → `->maxSize(262144)` | 256 MB |
| Livewire tijdelijke upload | `config/livewire.php` → `temporary_file_upload.rules` | 256 MB |
| PHP | `upload_max_filesize` + `post_max_size` | 256 MB (lokaal én live) |

Livewire's **default is 12MB** — die was hier de blokkade. PHP's default is 2M.

- **Lokaal (Herd)**: `~/Library/Application Support/Herd/config/php/83/php.ini`.
  Na wijzigen Herd herstarten.
- **Productie (Combell)**: `public/.user.ini` in de repo — `deploy.sh` symlinkt
  alle top-level `public/`-bestanden (ook dotfiles) naar de `/www`-docroot, waar
  PHP-FPM de `.user.ini` per map oppikt (cache ~5 min na deploy).

`post_max_size` moet altijd ≥ `upload_max_filesize` zijn (de POST bevat naast het
bestand ook de andere formuliervelden).

## Mixtapes als eigen posttype

Mixtapes/DJ-sets leven sinds 2026-08-19 als eigen posttype (`mixtapes`-tabel,
**Website → Mixtapes** in de admin, versleepbare volgorde) — niet meer als
repeater-items in de sectie-content. De mixes-sectie op een pagina heeft een
toggle **"Toon alle mixtapes"** (volgt de admin-volgorde) of, uitgezet, een
multiselect `mixtape_ids` (de site toont ze in selectievolgorde). Beide sleutels
staan in de `$skipKeys` van `TranslatesContentArrays`.

Mixtapes zijn bewust **taal-onafhankelijk** (één mp3, cover én ondertitel voor
alle talen — ondertitels zijn genre/duur en dus universeel). Het vroegere
`mixes:sync-media`-commando is daarmee overbodig en verwijderd. De migration
`create_mixtapes_table` zette bestaande sectie-items om (NL als bron, dedupe op
titel) — draai `php artisan migrate` bij de deploy en het live-content komt
vanzelf mee. Bewaakt door `tests/Feature/MixtapesSectionTest.php`.

**Deelbare detailpagina per mixtape** (`/mixtapes/{slug}`, ook onder `/en`/`/es`):
eigen OG-meta met de cover, speler + download, hreflang voor alle talen, en
opgenomen in de sitemap. De slug wordt bij aanmaak uniek afgeleid uit de titel
en blijft daarna stabiel (deelbare links mogen niet breken). In de admin-tabel:
oog-knop (bekijk op site) en een **kopieerknop** die de publieke URL naar het
klembord schrijft. Let op de oude WP-redirects: `/mixtapes` en
`/mixtapes_categorie/*` moeten op de catch-all blijven landen (de
redirect-middleware draait pas als een route matcht), daarom sluit de catch-all
alleen `mixtapes/` mét slash uit. Oude `/mixtapes/{wp-slug}`-URL's redirecten
via de middleware vóór de nieuwe route-controller iets doet. Bewaakt door
`tests/Feature/MixtapePageTest.php` + `RedirectsTest`.

## Leads-meetlaag (first-party attributie)

Enkel het **meetgedeelte** van de Groei-laag uit de `seo-analytics`-skill — de
actielaag (Groei-hernoeming, keyword-voorstellen, queue-acties) is hier bewust
niet bijgewerkt (04/09/2026). Bewust geen GA4: consent mode maakt die cijfers
onvolledig, de eigen database kent de conversies exact.

- **Herkomst**: `CaptureFirstTouch` (web-groep, ná `HandleRedirects`) legt bij het
  eerste GET van een sessie kanaal, landingspagina, referrer en utm's vast
  (`App\Support\Attribution`, sessiekey `wg_first_touch`). Sessie-only, geen
  cookie; bots, `/admin`, `/t/*` (QR-scans) en `/stripe/*` krijgen niets.
- **Leads** (`leads`-tabel, `Lead`-model): één rij per conversie, met morph naar
  het bronrecord, bedrag en herkomst. `Lead::record()` faalt nooit hard.
  Twee conversiepunten:
  1. **Formulieren** — `FormSubmission::booted()` registreert élke inzending
     (contact én booking) als lead; nieuwe formuliertypes tellen dus vanzelf mee.
  2. **Ticketaankopen** — `TicketCheckoutService` zet `Attribution::current()`
     in de pending-payload (sleutel `attribution`, enkel als er een snapshot is:
     de webhook heeft geen sessie), en `TicketOrderFulfillment` schrijft de lead
     (`ticket_order`, bedrag = `total_inc_vat`, taal van de koper) **bínnen de
     transactie ná de Paid-check** — webhook en bedankpagina tellen nooit dubbel.
- **Harde regel**: elk nieuw conversiepunt roept `Lead::record()` aan op het
  punt waar de conversie definitief wordt, binnen de idempotency-guard als er
  een webhook in het spel is. Formulieren via `FormSubmission` hoeven niets.
- **Verkeer** (*SEO → Verkeer*, `SearchConsole`): het **gemeten** Google-verkeer
  uit Search Console — niet de DataForSEO-schatting. Clicks/vertoningen/CTR/
  positie (28 d. t.o.v. 28 d. ervoor), weekverloop, top-zoektermen/-pagina's en
  kansen (≥ 20 vertoningen, positie 4-20). Koppeling via OAuth op het eigen
  Google-account (`gsc_oauth_client_id/secret`, consent-flow via
  `SearchConsoleOAuthController` op `/admin/search-console/oauth/*`, refresh
  token in `gsc_refresh_token`, property `gsc_site_url` automatisch gekozen).
  Sync `seo:sync-search-console` dagelijks 6:00 (16 maanden backfill bij de
  eerste run, daarna rollend 7-dagenvenster). Valkuilen: `access_type=offline`
  + `prompt=consent` verplicht, en de OAuth-app in Google Cloud op "In productie".
- **Admin**: *SEO → Leads* (`SeoLeads`): kop-cijfers t.o.v. het maanddoel,
  leads per maand met doellijn en livegang-markering, verdeling per
  kanaal/type/landingspagina (90 d.), recentste 50, en de nulmeting-velden
  (`seo_live_since`, `seo_goal_leads_month`, `seo_leads_baseline`). Cijfers uit
  `App\Support\LeadStats`. Datums `dd/mm/jjjj`.

Bewaakt door `tests/Feature/LeadAttributionTest.php`,
`tests/Feature/SeoLeadsPageTest.php`, `tests/Feature/SearchConsoleTest.php` en
`tests/Feature/Events/TicketOrderLeadTest.php`.

### Nog in te vullen (admin)
- [ ] **SEO → Leads**: livegang-datum (06/08/2026), maanddoel en de opgave van vóór de meting.
- [ ] **SEO → Verkeer**: OAuth-client aanmaken in Google Cloud (omleidings-URI staat op de pagina), koppelen, "Ververs nu".

## Events & ticketverkoop

Volwaardig events-posttype met tickets, kortingen en Stripe-checkout — vers
gebouwd naar het model van bailando-latino, aangepast aan dit project (gasten
zonder account, geen danser-rollen, meertalig met gedeelde voorraad).

**Model.** Eén `events`-rij per event (gedeelde voorraad over alle talen); EN/ES
in `event_translations` (rijen zonder inhoud tellen niet — `hasContent()`).
Tickettypes zijn een globale catalogus (`ticket_types`); prijs/btw/verkoopvenster/
**capaciteit** per event op de pivot `event_ticket_types` (eigen model — wordt
bij checkout ge-`lockForUpdate` voor de capaciteitscheck). Bestellingen
(`ticket_orders` + items + `event_tickets`, 1 rij = 1 ticket met ULID-token)
ontstaan al bij het aanmaken van de Stripe-sessie als **Pending met
Reserved-tickets** — zo is capaciteit exact. Reserveringen verlopen lokaal na
40 min (`events:release-expired-reservations`, elke 5 min via de scheduler); de
Stripe-sessie zelf verloopt na 30 min, dus betalen-na-vrijgave kan niet.

**Prijslogica.** `Event::lineTotalFor()` is de enige waarheid (port uit
bailando): automatische promo's (`event_ticket_discounts`: vaste promoprijs of
koop-X+Y-gratis) — laagste regeltotaal wint bij overlap, nooit stapelen; BOGO
wordt over de stuksprijs uitgesmeerd. Kortingscodes (`discount_codes`, met
event-binding, limieten totaal/per e-mail — enkel **betaalde** orders tellen)
via `DiscountCodeValidator`. Btw per orderregel (nooit hardcoded op de header).
De Livewire-checkout (`App\Livewire\Events\TicketCheckout`, met `PersistsLocale`)
rekent alles server-side — er is géén JS-spiegel van de prijslogica.

**Betaalflow.** `TicketCheckoutService` → Stripe Checkout (hosted; card,
Bancontact, iDEAL, Link, PayPal; één geaggregeerd line item; `client_reference_id`
= uuid van `pending_stripe_sessions`). Fulfilment (`TicketOrderFulfillment`) is
idempotent en loopt via twee kanalen: de webhook (`POST /stripe/webhook`,
CSRF-vrij, faalpad = HTTP 500 zodat Stripe retryt + alarmmail 1×/24u) én de
bedankpagina als fallback. Alles achter `App\Contracts\PaymentGateway`
(`StripeGateway` in productie, `Tests\Fakes\FakePaymentGateway` in tests);
secrets uit **Instellingen → Betalingen** (DB wint, .env is fallback).
Totalen < € 0,50 na korting worden geweigerd (Stripe-minimum); een
gratis-order-bypass is bewust niet gebouwd.

**Tickets.** Bevestigingsmail in de taal van de koper (`SendTicketOrderEmailJob`,
queue + dedupe per order, `force` voor opnieuw verzenden) met per ticket een
PDF-bijlage (dompdf, QR als **SVG**-data-URI — geen imagick op Combell). QR wijst
naar `/t/{token}` (publieke statuspagina). Check-in via **Events → Scannen**
(html5-qrcode) of handmatig/omkeerbaar in de bestelling; `TicketScanner` kent
ok/already/wrong_event/refunded/unpaid/not_found. Refund = actie op de
bestelling (volledig, via Stripe; tickets worden ongeldig en geven capaciteit
vrij). Events afgelasten = toggle (blijft zichtbaar met banner, verkoop stopt;
terugbetalen blijft handmatig per bestelling).

**Publiek.** `/events` + `/events/{slug}` (+ `/en`, `/es`; gedeelde slug), routes
vóór de catch-all én vóór de `{slug}`-route in de locale-groep. `Seo::fromEvent()`
levert meta + schema.org `Event`/`Offer`-JSON-LD; hreflang volgt `hasContent()`.
Sitemap en llms.txt nemen events mee. Sectieblok `events` (teaser aankomende
events) is registreerbaar op elke pagina. Oude WP `/events/*`-URL's redirecten
nu naar `/events` (de `/events`-redirect zelf wordt door de seeder verwijderd —
draai hem bij deploy).

**Rich results — de drie valkuilen in `eventNode()`.** Search Console meldde
(22/09/2026) twee ontbrekende velden; alle drie de punten hieronder leven in
dezelfde functie in `app/Support/Seo.php`:

- **`performer`** — de artiest. `Event::performerNames()` splitst het
  `lineup`-veld (*Basis*-tab, komma-gescheiden) en valt zonder line-up terug op
  `Seo::brandName()`. De DJ zelf krijgt een vast `@id` (`/#performer`) plus de
  socials uit de footer, zodat Google al zijn optredens aan één entiteit hangt;
  een gastartiest blijft een kale `Person`-naam.
- **`offers.validFrom`** — vanaf wanneer het ticket te koop is. Voedt zich uit
  de nieuwe kolom `sales_start_date` op `event_ticket_types` (*Verkoop vanaf*,
  tegenhanger van `sales_end_date`), en valt zonder voorverkoop terug op de
  `created_at` van de pivotrij. Het venster loopt via
  `Event::ticketSalesOpenFor($start, $end)`; `salesPending()` onderscheidt
  "nog niet begonnen" van "afgesloten" — de checkout toont dan
  *Verkoop vanaf dd/mm/jjjj* in plaats van een gesloten deur.
- **Tijdzone** — de app draait op `UTC`, maar een uur in de admin is een uur
  aan de deur. `startDate`/`endDate` gaan daarom door `shiftTimezone(Seo::TIMEZONE)`,
  anders vertrekt 22:00 als `+00:00` en maakt Google er middernacht van.

Bewaakt door `tests/Feature/Events/EventPublicPagesTest.php`.

**Extra's (geen tickets).** Naast de tickets kan een event **extra's** aanbieden
— een gratis groepstafel, later een drankkaart. Bewust een eigen laag
(`event_extras` + `ticket_order_extras`, tab *Extra's* op het event), want een
extra is géén bezoeker: ze maakt **geen rij in `event_tickets`**, krijgt geen
QR-PDF en laat elk ticketaantal (eventlijst, bestelling, scanner, capaciteit)
exact het aantal mensen blijven.

Per extra stel je in: prijs (0 = gratis) + btw, **voorraad**, **max. per
bestelling**, en **"beschikbaar vanaf … tickets"** — eventueel geteld op één
tickettype (`ticket_type_id`, leeg = alle tickets samen). Onder de drempel toont
de checkout de extra grijs met "vanaf 12 tickets" (bewust zichtbaar: dat is de
reden om tickets bij te nemen); zakt het aantal weer, dan valt de keuze er
vanzelf af. Alles wordt server-side hervalideerd in `TicketCheckoutService`
tegen de **hervalideerde** ticketaantallen, en de voorraad in dezelfde
vergrendelde transactie als de tickets (eerst `event_ticket_types` op
ticket_type_id, dán `event_extras` op id — vaste volgorde, dus geen deadlock).

Twee ontwerpkeuzes om te onthouden:
- `ticket_order_extras` draagt **geen eigen status**. Of een claim voorraad
  bezet volgt uit de bestelling (`TicketOrder::scopeOccupying()`: betaald, of
  een reservering die nog loopt). Een verlopen reservering of terugbetaling
  geeft de tafel dus vanzelf vrij, zonder tweede statusveld.
- **Kortingscodes rekenen op het tickettotaal**, niet op de extra's (anders
  krijg je "20% korting op een gratis tafel"). `subtotal_inc_vat` op de
  bestelling is wél tickets + extra's; de korting is bij `calculateDiscount()`
  al geplafonneerd op het tickettotaal.

De extra verschijnt in de bevestigingsmail en op de bedankpagina, en als regel
*Inclusief* op elk ticket-PDF (zodat de deur niets hoeft op te zoeken). In de
admin: *Extra's* in de bestelling, een eigen kolom en het filter **Extra** op de
bestellingenlijst, en "geclaimd / voorraad" per extra op het event.

`MAX_PER_TYPE` in `TicketCheckout` staat op **30** (was 10) en de stepper heeft
een invoerveld — anders kom je nooit aan een groep van 12 of 24 tickets.

**Bij go-live niet vergeten:**
- [ ] Stripe-keys invullen (Instellingen → Betalingen) en in het
      Stripe-dashboard de webhook `https://www.el-pablo.com/stripe/webhook`
      registreren met events `checkout.session.completed` +
      `checkout.session.expired`; signing secret overnemen.
- [ ] Queue-worker én scheduler-cron actief (mail + reserveringsvrijgave).
- [ ] `php artisan db:seed --class=RedirectSeeder` (ruimt ook de oude
      `/events`-redirect op).

Bewaakt door `tests/Feature/Events/` (pricing, validator, capaciteit, checkout,
webhook-idempotentie, mail/PDF, scan, publieke pagina's, SEO-assets, admin) en
`tests/Feature/Events/EventExtrasTest.php` (drempel, maximum, voorraad,
vrijgave, server-side hervalidatie, en dat een extra géén bezoeker is).

## Redirects van de oude site (bij go-live)

De huidige el-pablo.com is een WordPress met **72 geïndexeerde URL's** (bron: de
Yoast-sitemaps, gecrawld 2026-08-06). `database/seeders/RedirectSeeder.php` mapt
er 57 naar de nieuwe pagina's; de rest (oude blogposts + `/ajax`) mag bewust
404'en.

**Bij de deploy draaien:** `php artisan db:seed --class=RedirectSeeder`
(idempotent — `updateOrCreate` op `from`, dus veilig te herhalen op live).

Beheer daarna via Filament → *Redirects*. Komt er later een events-pagina, wijzig
dan het doel van de `/events/*`-regels naar `/events` in plaats van ze te
verwijderen — dan blijven de oude links hun waarde doorgeven.

`resources/views/errors/404.blade.php` vangt de rest op in de huisstijl
(`noindex`, vertaald via `__()`, links naar home/muziek/boeken).

Bewaakt door `tests/Feature/RedirectsTest.php`.

## Merk-chrome: logo naast de naam + favicon

**Logo én naam.** Header en footer toonden vroeger *óf* het logo *óf* de naam —
zodra je een logo instelde verdween "El Pablo". Nu staat het beeldmerk links met
de naam (en ondertitel) ernaast. Bevat een logo de merknaam zelf al, zet dan
*Naam naast het logo tonen* uit (Website → Header, en idem in de footer onder
*Merk & tekst*). De sleutel heet `show_name` in de `header`- en
`footer.brand`-blob; ontbreekt hij, dan geldt `true`.

**Favicon.** De `<head>` had géén `<link rel="icon">`, waardoor de browser
`/favicon.ico` opvroeg — een leeg bestand — en het gecachete WordPress-icoon van
de vorige site op dit domein bleef tonen. Nu:

- `public/favicon.ico` (32×32), `public/favicon.svg` en
  `public/apple-touch-icon.png` (180×180) staan in de repo: het beeldmerk in
  `primary-600` op een `ink-950`-tegel. `deploy.sh` bouwt `/www` elke deploy vers
  op met symlinks naar `public/*`, dus ze komen vanzelf mee.
- Een eigen favicon uploaden kan via **Website → Header → Favicon**; die wint van
  de bestanden in `public/`.
- De links dragen `?v=2`. Vervang je de bestanden in `public/`, verhoog dat
  nummer in `meta.blade.php` — anders blijven browsers het oude icoon tonen.

Bewaakt door `tests/Feature/BrandingTest.php`.

### Nog te doen
- [ ] **Echte content**: placeholder-foto's (Unsplash — nog 24 secties) en de
      demo-mp3's vervangen via de media-library / **Website → Mixtapes**.
      Bronmateriaal staat in `~/Development/elpablo-oud-archief/uploads/`
      (zie *Oude WordPress opgeruimd*).
- [x] Content migreren van de bestaande el-pablo.com — de oude WordPress is
      van de hosting verwijderd (21/09/2026).
- [x] Redirects oude WP-site (57) + eigen 404-pagina.
- [x] Lettertype gekozen (Anton + Inter).
- [x] Juridische pagina's geseed (cookiebeleid + privacybeleid).
- [x] `MAIL_FROM_ADDRESS` = info@el-pablo.com.
- [x] EN/ES-content vertaald (pagina's + chrome + cookiebanner + formulieren).
- [x] Footer-adres per taal (`__()`); tagline al eerder vertaald.
- [x] SEO-finish: locale-bewuste canonical, `hreflang`-alternates (+ x-default)
      en `og:locale` (+ alternates) in de `<head>` (`Seo::alternates()` /
      `meta.blade`); sitemap met alle NL/EN/ES-URL's + `xhtml:link`-hreflang,
      noindex-pagina's uitgesloten.

## Oude WordPress opgeruimd (21/09/2026)

Combell's CMS-scanner bleef elke week **2 WordPress-installaties** melden op deze
hosting — de bewaarde kopie van de oude live-site (7.0.2) in
`~/www-wordpress-backup` en een staging-WP (6.0.1) uit 2022 in
`~/staging-wordpress-backup`, samen 7 GB met 24 verouderde plugins. Die zijn
verwijderd, plus `~/.wp-cli`, `~/wpmgmt`, `~/rollback.sh` en `~/cutover.sh`.

**Eerst gearchiveerd, en dat archief blijft nodig.** De 2,1 GB
`wp-content/uploads` staat nu lokaal in `~/Development/elpablo-oud-archief/uploads/`
(1.609 bestanden, byte-voor-byte geverifieerd tegen de server): 1.562 foto's per
jaar 2015-2026 en **27 mp3's** met de echte DJ-sets (o.a. *Reggaeton Sessions
2020*, *Kizomba Sessions 2020*, *Salchata 2021 Vol. 1 & 2*, *Urban Sessions
2018/2019*, *El Pablo @ Chique Beach 01-07-22*). Ruim twintig daarvan staan nog
niet op de nieuwe site. Dit is het bronmateriaal voor de openstaande
content-taak hierboven — het staat **alleen nog op de Mac**, neem het dus mee in
je backup.

**Niet verwijderd:** beide WordPress-databases (`ID207573_elpablo` met prefix
`0_`, en de staging-DB `ID207573_wordpress318533`). De teksten van de oude site
zijn daar dus nog opvraagbaar. Schrappen kan later via het Combell-controlepaneel.

`rollback.sh` (de noodrem terug naar WordPress) werkt hierna per definitie niet
meer. Samen met `cutover.sh` staat die nog wel in git onder `deploy/`, maar beide
zijn na de cutover van 06/08/2026 zonder functie.

## Lokaal draaien

MySQL draait via Herd. Start indien nodig:
`~/Library/Application\ Support/Herd/bin/herd services:start <mysql-id>`.
App serveren: `php artisan serve` of via Herd (`.test`-domein).

Admin: `/admin` — user `pieter@dewebgoeroe.be`. Het wachtwoord is gewijzigd en
staat bewust niet meer in de repo (zie je wachtwoordmanager).
