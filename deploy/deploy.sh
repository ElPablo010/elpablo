#!/usr/bin/env bash
#
# Recurring deploy van el-pablo.com op Combell shared hosting.
# Plaats dit bestand in de HOME-map van de server en draai:  bash deploy.sh
#
# Verschil met de kale skill-template: stap 7 reset OPcache. Combell draait met
# opcache.validate_timestamps=0, waardoor PHP-FPM na een code-wijziging de OUDE
# bytecode blijft serveren. Zonder die reset zie je je deploy simpelweg niet.
# (Les uit de arkvannoe.be-cutover — daar bleef WordPress draaien ondanks
# verplaatste bestanden.) Een CLI-reset werkt niet: het moet via een FPM-request.
set -euo pipefail

# ---- Config ----
PROJECT="elpablo-new"            # naam van de projectmap in je home
DOMAIN="https://el-pablo.com"    # gebruikt voor de OPcache-reset-request
# ----------------

export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

APP_DIR="$HOME/$PROJECT"
WWW_DIR="$HOME/www"
PUBLIC_DIR="$APP_DIR/public"

cd "$APP_DIR"

echo "==> 1/7  Git pull"
git pull --ff-only

echo "==> 2/7  Composer install (productie)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> 3/7  Frontend build (Vite via NVM-Node)"
npm ci
npm run build

echo "==> 4/7  /www docroot koppelen aan public"
# Combell's docroot staat vast op /www en mag GEEN symlink ZIJN (nginx weigert
# een gesymlinkte docroot -> 403). Daarom: /www is een ECHTE map met een mini
# index.php-loader + symlinks naar de public-assets (symlinks BINNEN de docroot
# werken wel). Elke deploy vers opgebouwd, zodat nieuwe public-bestanden meekomen.
mkdir -p "$WWW_DIR"
find "$WWW_DIR" -mindepth 1 -delete
find "$PUBLIC_DIR" -maxdepth 1 -mindepth 1 ! -name index.php ! -name storage \
  -exec ln -s {} "$WWW_DIR/" \;
ln -s "$APP_DIR/storage/app/public" "$WWW_DIR/storage"
printf "%s\n" "<?php require '$PUBLIC_DIR/index.php';" > "$WWW_DIR/index.php"

echo "==> 5/7  Migraties + caches"
php artisan optimize:clear
php artisan migrate --force
php artisan optimize
php artisan filament:optimize 2>/dev/null || true

echo "==> 6/7  Schrijfrechten"
chmod -R 775 storage bootstrap/cache

echo "==> 7/7  OPcache resetten (via FPM-request)"
# Random bestandsnaam zodat dit endpoint niet raadbaar/misbruikbaar is, en het
# wordt hoe dan ook direct opgeruimd (ook als de curl faalt).
OC_FILE="_ocreset_$(head -c 16 /dev/urandom | od -An -tx1 | tr -d ' \n').php"
printf "%s\n" "<?php echo function_exists('opcache_reset') && opcache_reset() ? 'OK' : 'SKIP';" > "$WWW_DIR/$OC_FILE"
trap 'rm -f "$WWW_DIR/$OC_FILE"' EXIT
sleep 1
OC_RESULT="$(curl -fsS --max-time 20 "$DOMAIN/$OC_FILE" || echo 'FAILED')"
rm -f "$WWW_DIR/$OC_FILE"
trap - EXIT
echo "    OPcache: $OC_RESULT"
if [ "$OC_RESULT" = "FAILED" ]; then
  echo "    LET OP: reset mislukt. Je wijzigingen zijn mogelijk nog niet zichtbaar."
  echo "    Controleer of $DOMAIN bereikbaar is en draai deploy.sh opnieuw."
fi

echo ""
echo "Deploy klaar."
