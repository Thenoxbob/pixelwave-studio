#!/usr/bin/env bash
# Sauvegarde quotidienne de la base du portail (lancée par cron à 02h30).
# Le serveur de sauvegarde (VLAN 60) vient ensuite récupérer les fichiers par rsync/SSH :
# la DMZ ne peut pas ouvrir de connexion vers le LAN, c'est le LAN qui « tire » la sauvegarde.
set -euo pipefail
DOSSIER=/var/backups/pixelwave
RETENTION_JOURS=14
HORODATAGE=$(date +%Y%m%d-%H%M)
FICHIER="$DOSSIER/pixelwave-$HORODATAGE.sql.gz"

umask 077
mariadb-dump --single-transaction --routines --triggers --databases pixelwave | gzip -9 > "$FICHIER"
sha256sum "$FICHIER" > "$FICHIER.sha256"
# configuration applicative (contient le mot de passe BDD : fichier protégé en 600)
cp /var/www/pixelwave/portail/config/config.php "$DOSSIER/config-$HORODATAGE.php"

find "$DOSSIER" -type f -mtime +"$RETENTION_JOURS" -delete
logger -t pixelwave-sauvegarde "Sauvegarde OK : $FICHIER ($(du -h "$FICHIER" | cut -f1))"
