#!/usr/bin/env bash
#
# EENMALIGE CUTOVER: el-pablo.com van WordPress naar de Laravel-site.
#
# Draai dit pas als fase 1 helemaal klaar is (zie deploy/DEPLOY.md): repo geclonet,
# .env gevuld, composer + npm gebouwd, DB geimporteerd, media gersynct, en de
# smoke test via `php artisan serve` groen. Tot dat moment blijft WordPress
# ongestoord draaien.
#
# Wat dit script doet:
#   1. controleert dat alles klaarstaat (weigert anders),
#   2. verplaatst de WordPress-docroot naar ~/www-wordpress-backup (niets wordt
#      verwijderd — dat is meteen je rollback),
#   3. bouwt /www op als Laravel-docroot,
#   4. reset OPcache (anders blijft WordPress draaien ondanks de verplaatsing),
#   5. verifieert dat het domein de Laravel-site teruggeeft.
#
# Terug naar WordPress? bash rollback.sh
set -euo pipefail

PROJECT="elpablo"
PHP="/usr/local/bin/php8.3"   # kale `php` is hier 7.4 (oude WordPress)
DOMAIN="https://www.el-pablo.com"   # www is canoniek; non-www 301t hierheen

APP_DIR="$HOME/$PROJECT"
WWW_DIR="$HOME/www"
BACKUP_DIR="$HOME/www-wordpress-backup"
PUBLIC_DIR="$APP_DIR/public"

fail() { echo "AFGEBROKEN: $1" >&2; exit 1; }

echo "=== Controles vooraf ==="

[ -d "$APP_DIR" ] || fail "$APP_DIR bestaat niet. Clone eerst de repo (fase 1)."
[ -f "$APP_DIR/.env" ] || fail "$APP_DIR/.env ontbreekt."
[ -d "$APP_DIR/vendor" ] || fail "vendor/ ontbreekt. Draai composer install."
[ -f "$APP_DIR/public/build/manifest.json" ] || fail "public/build ontbreekt. Draai npm ci && npm run build."
[ -d "$WWW_DIR" ] || fail "$WWW_DIR bestaat niet — is dit wel de juiste server?"
[ -e "$BACKUP_DIR" ] && fail "$BACKUP_DIR bestaat al. Cutover blijkbaar al gedaan; verwijder of hernoem die map eerst."

# APP_DEBUG moet uit: anders lekt een foutpagina code en paden naar bezoekers.
if grep -qE '^APP_DEBUG=true' "$APP_DIR/.env"; then
  fail "APP_DEBUG=true staat aan in .env. Zet op false voor je live gaat."
fi

# Draait de app überhaupt? Zo niet, dan zetten we een kapotte site live.
cd "$APP_DIR"
"$PHP" artisan about --only=environment >/dev/null 2>&1 || fail "php artisan werkt niet in $APP_DIR."

# Is /www inderdaad nog WordPress? Zo niet, dan is er iets anders aan de hand.
if [ ! -e "$WWW_DIR/wp-config.php" ] && [ ! -d "$WWW_DIR/wp-content" ]; then
  echo "LET OP: $WWW_DIR ziet er niet uit als een WordPress-installatie."
  read -r -p "Toch doorgaan? (typ JA) " confirm
  [ "$confirm" = "JA" ] || fail "Afgebroken door gebruiker."
fi

echo "Alle controles ok."
echo ""
echo "Op het punt om:"
echo "  $WWW_DIR  ->  $BACKUP_DIR   (WordPress, blijft intact)"
echo "  en $WWW_DIR opnieuw op te bouwen voor $PROJECT"
echo ""
read -r -p "Doorgaan? (typ JA) " confirm
[ "$confirm" = "JA" ] || fail "Afgebroken door gebruiker."

echo ""
echo "=== 1/4  WordPress opzijzetten ==="
mv "$WWW_DIR" "$BACKUP_DIR"
echo "    $BACKUP_DIR  ($(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1))"

echo "=== 2/4  Laravel-docroot opbouwen ==="
mkdir -p "$WWW_DIR"
find "$PUBLIC_DIR" -maxdepth 1 -mindepth 1 ! -name index.php ! -name storage \
  -exec ln -s {} "$WWW_DIR/" \;
ln -s "$APP_DIR/storage/app/public" "$WWW_DIR/storage"
printf "%s\n" "<?php require '$PUBLIC_DIR/index.php';" > "$WWW_DIR/index.php"

echo "=== 3/4  APP_URL, caches + OPcache ==="
# Tijdens de testfase stond APP_URL op staging. Canonical, hreflang, og:image en
# de sitemap worden daaruit opgebouwd, dus dit moet mee — anders wijst de live
# site zoekmachines naar staging (dat noindex is).
if grep -q '^APP_URL=https://staging\.' "$APP_DIR/.env"; then
  sed -i "s|^APP_URL=.*|APP_URL=$DOMAIN|" "$APP_DIR/.env"
  echo "    APP_URL -> $DOMAIN"
else
  echo "    APP_URL: $(grep '^APP_URL=' "$APP_DIR/.env")"
fi

"$PHP" artisan optimize:clear
"$PHP" artisan optimize
"$PHP" artisan filament:optimize 2>/dev/null || true

OC_FILE="_ocreset_$(date +%s)$RANDOM$$.php"
printf "%s\n" "<?php echo function_exists('opcache_reset') && opcache_reset() ? 'OK' : 'SKIP';" > "$WWW_DIR/$OC_FILE"
trap 'rm -f "$WWW_DIR/$OC_FILE"' EXIT
sleep 1
OC_RESULT="$(curl -fsSL --max-time 20 "$DOMAIN/$OC_FILE" || echo 'FAILED')"
rm -f "$WWW_DIR/$OC_FILE"
trap - EXIT
echo "    OPcache: $OC_RESULT"

echo "=== 4/4  Verifiëren ==="
sleep 2
STATUS="$(curl -fsSL -o /dev/null -w "%{http_code}" --max-time 25 "$DOMAIN/" || echo '000')"
echo "    $DOMAIN/  ->  HTTP $STATUS"

if curl -fsSL --max-time 25 "$DOMAIN/" 2>/dev/null | grep -qi 'wp-content\|wp-includes'; then
  echo ""
  echo "    WAARSCHUWING: er zit nog WordPress-markup in de respons."
  echo "    Meestal OPcache. Draai deploy.sh nog eens, of rollback.sh om terug te gaan."
elif [ "$STATUS" = "200" ]; then
  echo ""
  echo "Cutover geslaagd. el-pablo.com draait nu de Laravel-site."
else
  echo ""
  echo "    Nog geen 200. Controleer de site; rollback.sh brengt WordPress terug."
fi

echo ""
echo "WordPress staat veilig in $BACKUP_DIR — pas opruimen als alles dagen goed draait."
