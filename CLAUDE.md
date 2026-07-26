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

### Nog te doen
- [ ] **Echte content**: placeholder-foto's (Unsplash) en de demo-mp3's (2 sets
      herhaald) vervangen via de media-library / het `mixes`-blok.
- [ ] Content migreren van de bestaande el-pablo.com.
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
