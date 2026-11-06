#!/usr/bin/env bash
# Restauration de la base du portail à partir d'une sauvegarde.
#   sudo ./restauration.sh /var/backups/pixelwave/pixelwave-20261106-0230.sql.gz
set -euo pipefail
FICHIER="${1:?Usage : $0 <fichier .sql.gz>}"

sha256sum -c "$FICHIER.sha256"                       # refuse un fichier corrompu
echo "La base pixelwave va être remplacée par $FICHIER. Taper OUI pour continuer :"
read -r reponse
[[ "$reponse" == "OUI" ]] || { echo "Annulé."; exit 1; }

a2dissite -q pixelwave-portail && systemctl reload apache2      # maintenance : portail coupé
gunzip -c "$FICHIER" | mariadb
a2ensite -q pixelwave-portail && systemctl reload apache2
curl -sk https://127.0.0.1/api/v1/sante
echo
echo "Restauration terminée."
