#!/usr/bin/env bash
#
# NOODREM: zet el-pablo.com terug op de oude WordPress-site.
#
# Werkt zolang ~/www-wordpress-backup bestaat (cutover.sh verplaatst WordPress
# daarheen en verwijdert nooit iets). De Laravel-app blijft gewoon staan in
# ~/elpablo-new, dus je kunt daarna rustig repareren en cutover.sh opnieuw draaien.
set -euo pipefail

PROJECT="elpablo-new"
DOMAIN="https://el-pablo.com"

WWW_DIR="$HOME/www"
BACKUP_DIR="$HOME/www-wordpress-backup"

fail() { echo "AFGEBROKEN: $1" >&2; exit 1; }

[ -d "$BACKUP_DIR" ] || fail "$BACKUP_DIR bestaat niet — er is geen WordPress om naar terug te keren."

echo "Dit zet de WordPress-site terug op $DOMAIN."
echo "  $WWW_DIR          -> weg (dit is enkel de Laravel-docroot: symlinks + loader)"
echo "  $BACKUP_DIR       -> $WWW_DIR"
echo ""
echo "De Laravel-app in ~/$PROJECT blijft ongemoeid."
read -r -p "Doorgaan? (typ JA) " confirm
[ "$confirm" = "JA" ] || fail "Afgebroken door gebruiker."

echo ""
echo "=== 1/3  Laravel-docroot verwijderen ==="
# Veiligheidscheck: /www hoort de dunne Laravel-docroot te zijn (symlinks + een
# mini index.php). Zit er iets anders in, dan stoppen we liever.
if [ -e "$WWW_DIR/wp-config.php" ] || [ -d "$WWW_DIR/wp-content" ]; then
  fail "$WWW_DIR bevat WordPress-bestanden. Rollback lijkt al gebeurd — niets gedaan."
fi
rm -rf "$WWW_DIR"

echo "=== 2/3  WordPress terugzetten ==="
mv "$BACKUP_DIR" "$WWW_DIR"

echo "=== 3/3  OPcache resetten ==="
# Zonder reset blijft FPM de Laravel-bytecode serveren (validate_timestamps=0).
OC_FILE="_ocreset_$(date +%s)$RANDOM$$.php"
printf "%s\n" "<?php echo function_exists('opcache_reset') && opcache_reset() ? 'OK' : 'SKIP';" > "$WWW_DIR/$OC_FILE"
trap 'rm -f "$WWW_DIR/$OC_FILE"' EXIT
sleep 1
OC_RESULT="$(curl -fsS --max-time 20 "$DOMAIN/$OC_FILE" || echo 'FAILED')"
rm -f "$WWW_DIR/$OC_FILE"
trap - EXIT
echo "    OPcache: $OC_RESULT"

sleep 2
STATUS="$(curl -fsS -o /dev/null -w '%{http_code}' --max-time 25 "$DOMAIN/" || echo '000')"
echo ""
echo "$DOMAIN/  ->  HTTP $STATUS"
echo "WordPress staat terug. De Laravel-app wacht in ~/$PROJECT."
