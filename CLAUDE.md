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

### Nog te doen
- [ ] **Contact- en boekingsformulier vertalen** — de veldlabels + Livewire-
      validatieberichten staan nog in NL. Vereist Livewire-locale-persistentie
      (locale bewaren + `setLocale()` bij re-render), daarom bewust apart gehouden.
- [ ] **Echte content**: placeholder-foto's (Unsplash) en de demo-mp3's (2 sets
      herhaald) vervangen via de media-library / het `mixes`-blok.
- [ ] **Footer-settings per taal** — adres/tagline komen uit `settings` (één
      waarde); tagline is via `__()` vertaald, adres ("Antwerpen, België") niet.
- [ ] Content migreren van de bestaande el-pablo.com.
- [ ] (SEO-refinement) `hreflang`-alternates + per-locale `og:locale` in de
      `<head>`; sitemap uitbreiden met de EN/ES-URL's.
- [x] Lettertype gekozen (Anton + Inter).
- [x] Juridische pagina's geseed (cookiebeleid + privacybeleid).
- [x] `MAIL_FROM_ADDRESS` = info@el-pablo.com.
- [x] EN/ES-content vertaald (pagina's + chrome + cookiebanner).

## Lokaal draaien

MySQL draait via Herd. Start indien nodig:
`~/Library/Application\ Support/Herd/bin/herd services:start <mysql-id>`.
App serveren: `php artisan serve` of via Herd (`.test`-domein).

Admin: `/admin` — user `pieter@dewebgoeroe.be`, tijdelijk wachtwoord
`elpablo-admin` (wijzig dit).
