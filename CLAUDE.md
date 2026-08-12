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

Bewaakt door `tests/Feature/SeoActionsTest.php`.

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
| Filament-veld | `MixesFields::make()` → `->maxSize(102400)` | 100 MB |
| Livewire tijdelijke upload | `config/livewire.php` → `temporary_file_upload.rules` | 128 MB |
| PHP | `upload_max_filesize` + `post_max_size` | 100 MB (lokaal, Herd) |

Livewire's **default is 12MB** — die was hier de blokkade. PHP's default is 2M.

- **Lokaal (Herd)**: `~/Library/Application Support/Herd/config/php/83/php.ini`.
  Na wijzigen Herd herstarten.
- **Productie (Combell)**: zet `upload_max_filesize` en `post_max_size` daar via
  het klantenpaneel of een `.user.ini` in de docroot. Vergeet dit niet — anders
  werkt uploaden lokaal wel en live niet.

`post_max_size` moet altijd ≥ `upload_max_filesize` zijn (de POST bevat naast het
bestand ook de andere formuliervelden). Wil je sets >95MB kunnen uploaden, zet
beide dan op 256M.

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
- [ ] **Echte content**: placeholder-foto's (Unsplash) en de demo-mp3's (2 sets
      herhaald) vervangen via de media-library / het `mixes`-blok.
- [ ] Content migreren van de bestaande el-pablo.com.
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

## Lokaal draaien

MySQL draait via Herd. Start indien nodig:
`~/Library/Application\ Support/Herd/bin/herd services:start <mysql-id>`.
App serveren: `php artisan serve` of via Herd (`.test`-domein).

Admin: `/admin` — user `pieter@dewebgoeroe.be`. Het wachtwoord is gewijzigd en
staat bewust niet meer in de repo (zie je wachtwoordmanager).
