# Go-live el-pablo.com — runbook

De hosting draait nu **WordPress**. Die blijft ongestoord online tot het allerlaatste
moment: we bouwen de Laravel-site er volledig náást op, testen hem, en wisselen dan
in één handeling de docroot om. WordPress gaat daarbij niet weg maar naar
`~/www-wordpress-backup` — dat is meteen de rollback.

Zelfde aanpak als bij arkvannoe.be (juli 2026).

---

## Fase 0 — al gedaan (lokaal)

- [x] **Media-URL's relatief gemaakt.** De public-disk bouwde ze op `APP_URL`,
      waardoor `https://elpablo.test/...` in de database stond. Alle 8 media, 6
      secties en 1 pagina zijn omgezet; `config/filesystems.php` staat nu op
      `'/storage'`. Zonder dit gaf élke afbeelding een 404 na de cutover.
      Migratie: `2026_08_06_075423_relativize_media_urls`.
- [x] **Redirects van de oude site** — 57 stuks (`RedirectSeeder`).
- [x] **404-pagina** in de huisstijl.
- [x] **Admin in het Nederlands** (`APP_LOCALE=nl`).
- [x] `deploy.sh`, `cutover.sh`, `rollback.sh` en deze `env.production.example`.
- [x] Testsuite groen (62).

---

## Wat ik nog van jou nodig heb

| # | Nodig | Waarvoor |
|---|---|---|
| 1 | **SSH-gegevens** van de el-pablo.com-hosting (host, gebruiker, wachtwoord of key) | clonen, bouwen, cutover |
| 2 | **Database** aanmaken in het paneel → naam, user, host, wachtwoord | `.env` |
| 3 | Bevestiging dat **el-pablo.com al naar deze Combell-server wijst** | bepaalt of er een DNS-stap nodig is |
| 4 | Bestaat **info@el-pablo.com** als mailbox? | afzender + ontvanger van de formulieren |
| 5 | Mag ik naar **github.com/ElPablo010/elpablo** pushen als `main`? | de server kloont daaruit |
| 6 | Draait er **Node ≥20** op de server? (`node -v` via SSH) | zo nee: NVM installeren |

Punten 1–2 kan ik niet zelf: die staan achter je Combell-login.

---

## Fase 1 — opbouwen naast WordPress (geen impact op de live site)

WordPress blijft de hele fase gewoon draaien op `/www`.

1. **Repo pushen** (lokaal): remote toevoegen, werk squashen naar `main`, pushen.
2. **SSH → clonen** in de home-map:
   ```bash
   git clone https://github.com/ElPablo010/elpablo.git elpablo
   cd elpablo
   php -v && composer --version     # PHP ≥8.3
   ```
3. **`.env`**: `deploy/env.production.example` → `.env`, invullen, dan
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   ```
4. **Database**: lokaal dumpen, via phpMyAdmin importeren.
   ```bash
   mysqldump --no-tablespaces -h 127.0.0.1 -u root elpablo_new > elpablo.sql
   ```
   De dump bevat al relatieve media-URL's, dus geen zoek-vervang nodig.
5. **Media oversturen** (staat niet in git):
   ```bash
   rsync -avz storage/app/public/ <user>@<host>:~/elpablo/storage/app/public/
   ```
6. **Node + assets**: als `node -v` < 20 → NVM installeren (zie skill stap 8), dan
   ```bash
   npm ci && npm run build
   ```
7. **Redirects seeden**: `php artisan db:seed --class=RedirectSeeder --force`
8. **PHP-limieten voor de mp3-uploads**: `upload_max_filesize` en `post_max_size`
   op 256M via het paneel of een `.user.ini` in de docroot. Anders werkt uploaden
   lokaal wel en live niet.
9. **Smoke test zonder de live site te raken**:
   ```bash
   php artisan serve --port=8123 &
   curl -s localhost:8123 | head -30        # moet de El Pablo-homepage geven
   curl -so /dev/null -w '%{http_code}\n' localhost:8123/admin/login
   ```

Pas als dit groen is, gaan we naar fase 2.

---

## Fase 2 — de cutover (het enige moment met impact)

```bash
bash cutover.sh
```

Het script controleert eerst alles (`.env` aanwezig, `APP_DEBUG=false`, build
gebouwd, app start), vraagt om bevestiging, en doet dan:

1. `~/www` → `~/www-wordpress-backup` (WordPress blijft intact)
2. `~/www` opnieuw opbouwen als Laravel-docroot (echte map + symlinks + loader)
3. caches + **OPcache-reset**
4. verifieert dat `https://el-pablo.com/` 200 geeft en géén WordPress-markup bevat

> **De OPcache-stap is niet optioneel.** Combell draait met
> `opcache.validate_timestamps=0`: zonder reset blijft PHP-FPM de oude bytecode
> serveren en zie je WordPress gewoon doordraaien alsof er niets gebeurd is. Bij
> arkvannoe.be kostte dat een halve middag zoeken. Een CLI-reset helpt niet — het
> moet via een echt webrequest, en dat doet het script.

**Downtime:** enkele seconden (de `mv` + heropbouw).

### Misgaat er iets?

```bash
bash rollback.sh
```

WordPress staat binnen seconden terug. De Laravel-app blijft in `~/elpablo`
staan, dus je kunt rustig repareren en `cutover.sh` opnieuw draaien.

---

## Na de cutover

- [ ] Steekproef op de redirects: `/contact-bookings/` → `/boeken`,
      `/mixtapes/salchata-2021-vol-2` → `/muziek`, `/urban-latin-dj` → `/over`
- [ ] Contactformulier én boekingsformulier testen (komt de mail aan?)
- [ ] `/admin` bereikbaar, wachtwoord werkt
- [ ] Afbeeldingen laden (dan klopte de rsync + de relatieve URL's)
- [ ] Geen stack trace op een niet-bestaande pagina → `APP_DEBUG=false` staat goed
- [ ] **Scheduler-cron** in het paneel: elke minuut
      `cd ~/elpablo && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1`
      (nodig voor de queue: formulier-mails en de SEO-collectie)
- [ ] SSL: bestond al voor WordPress, dus normaal niets te doen. Geeft HTTPS een
      403, dan is het cert niet mee verhuisd → Let's Encrypt opnieuw uitgeven.

### Mixes — opgelost, maar let op bij nieuwe uploads

Alle 12 mix-items (NL/EN/ES, home + muziek) wijzen nu naar eigen uploads onder
`/storage/website-audio/`. Er staat niets meer op de oude WordPress, dus de
cutover breekt de spelers niet.

**Wel onthouden:** elke taal is een eigen pagina met eigen secties. Upload je in
NL een nieuwe set, dan komt die *niet* vanzelf op `/en` en `/es`. Daarvoor is:

```bash
php artisan mixes:sync-media --dry-run    # toon wat er zou wijzigen
php artisan mixes:sync-media              # audio + covers van NL naar EN/ES
```

De Nederlandse pagina is de bron; vertaalde subtitels blijven staan, en items die
in NL verdwijnen worden ook uit de vertalingen gehaald.

De cover-afbeeldingen komen uit de media-library. Op de EN/ES-pagina's stonden nog
Unsplash-placeholders; die zijn met dezelfde sync meegenomen.

### WordPress opruimen

Pas als de site dagen goed draait. `~/www-wordpress-backup` mag dan weg, en de
WordPress-database kan blijven staan als extra vangnet (kost bijna niets).
