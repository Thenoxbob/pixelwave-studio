#!/usr/bin/env bash
# =============================================================================
# SRV-PORTAIL (DMZ, VLAN 70, 192.168.70.11) — Debian 12/13
# Installe et sécurise la pile LAMP qui héberge le portail joueurs PixelWave.
#
#   sudo ./install.sh                # installation complète
#   sudo DEPOT=https://github.com/Thenoxbob/pixelwave-studio.git ./install.sh
#
# Le script est idempotent : il peut être relancé sans casser l'existant.
# =============================================================================
set -euo pipefail

DEPOT="${DEPOT:-https://github.com/Thenoxbob/pixelwave-studio.git}"
BRANCHE="${BRANCHE:-main}"
DOSSIER_APP="/var/www/pixelwave"
NOM_SITE="${NOM_SITE:-portail.pixelwave.lan}"
BASE="pixelwave"
UTIL_BDD="pixelwave_app"
ICI="$(cd "$(dirname "$0")" && pwd)"

log() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
[[ $EUID -eq 0 ]] || { echo "À lancer en root (sudo)."; exit 1; }

# -----------------------------------------------------------------------------
log "1/9 Paquets système"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y -q apache2 mariadb-server php libapache2-mod-php php-mysql php-mbstring \
    php-xml git ufw fail2ban unattended-upgrades openssl curl ca-certificates
# mises à jour de sécurité automatiques
dpkg-reconfigure -f noninteractive unattended-upgrades

# -----------------------------------------------------------------------------
log "2/9 MariaDB : écoute locale uniquement"
install -m 644 "$ICI/mariadb/50-pixelwave.cnf" /etc/mysql/mariadb.conf.d/50-pixelwave.cnf
systemctl restart mariadb
# équivalent non interactif de mysql_secure_installation
mariadb -e "DELETE FROM mysql.global_priv WHERE User='';
            DELETE FROM mysql.global_priv WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');
            DROP DATABASE IF EXISTS test;
            FLUSH PRIVILEGES;"

# -----------------------------------------------------------------------------
log "3/9 Code de l'application"
if [[ -d "$DOSSIER_APP/.git" ]]; then
    git -C "$DOSSIER_APP" fetch -q origin "$BRANCHE"
    git -C "$DOSSIER_APP" reset -q --hard "origin/$BRANCHE"
else
    git clone -q --branch "$BRANCHE" "$DEPOT" "$DOSSIER_APP"
fi

# -----------------------------------------------------------------------------
log "4/9 Base de données et compte applicatif (droits minimaux)"
if ! mariadb -e "USE $BASE" 2>/dev/null; then
    mariadb < "$DOSSIER_APP/database/01_creation.sql"
    mariadb < "$DOSSIER_APP/database/02_jeu_de_donnees.sql"   # jeu de démonstration (à retirer en production réelle)
fi
FICHIER_CONFIG="$DOSSIER_APP/portail/config/config.php"
if [[ ! -f "$FICHIER_CONFIG" ]]; then
    MDP_BDD="$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-24)"
    mariadb -e "CREATE USER IF NOT EXISTS '$UTIL_BDD'@'localhost' IDENTIFIED BY '$MDP_BDD';
                ALTER USER '$UTIL_BDD'@'localhost' IDENTIFIED BY '$MDP_BDD';
                GRANT SELECT, INSERT, UPDATE, DELETE ON $BASE.* TO '$UTIL_BDD'@'localhost';
                FLUSH PRIVILEGES;"
    sed -e "s|https://portail.pixelwave.test|https://$NOM_SITE|" \
        -e "s|'host'     => '127.0.0.1'|'host'     => 'localhost'|" \
        -e "s|A_REMPLACER|$MDP_BDD|" \
        "$DOSSIER_APP/portail/config/config.example.php" > "$FICHIER_CONFIG"
    echo "Mot de passe BDD généré et écrit dans $FICHIER_CONFIG (jamais affiché)."
fi

# -----------------------------------------------------------------------------
log "5/9 Droits sur les fichiers"
chown -R root:www-data "$DOSSIER_APP"
find "$DOSSIER_APP" -type d -exec chmod 750 {} \;
find "$DOSSIER_APP" -type f -exec chmod 640 {} \;
chmod 640 "$FICHIER_CONFIG"                      # lisible par Apache, pas par les autres comptes
install -d -o www-data -g www-data -m 750 /var/log/pixelwave

# -----------------------------------------------------------------------------
log "6/9 PHP : durcissement"
for v in /etc/php/*/apache2; do
    install -m 644 "$ICI/php/99-pixelwave.ini" "$v/conf.d/99-pixelwave.ini"
done

# -----------------------------------------------------------------------------
log "7/9 Apache : hôte virtuel HTTPS"
CERT=/etc/ssl/certs/pixelwave-portail.crt
CLE=/etc/ssl/private/pixelwave-portail.key
if [[ ! -f "$CERT" ]]; then
    # certificat autosigné pour le laboratoire ; en production : certbot --apache (Let's Encrypt)
    openssl req -x509 -nodes -newkey rsa:3072 -days 825 -keyout "$CLE" -out "$CERT" \
        -subj "/C=FR/O=PixelWave Studio/CN=$NOM_SITE" -addext "subjectAltName=DNS:$NOM_SITE,IP:192.168.70.11" 2>/dev/null
    chmod 600 "$CLE"
fi
install -m 644 "$ICI/apache/pixelwave-portail.conf" /etc/apache2/sites-available/pixelwave-portail.conf
install -m 644 "$ICI/apache/securite.conf" /etc/apache2/conf-available/pixelwave-securite.conf
a2enmod -q ssl rewrite headers >/dev/null
a2enconf -q pixelwave-securite >/dev/null
a2dissite -q 000-default >/dev/null || true
a2ensite -q pixelwave-portail >/dev/null
apache2ctl configtest
systemctl reload apache2

# -----------------------------------------------------------------------------
log "8/9 Pare-feu local (ufw) et fail2ban"
# connexion SSH directe en root interdite (on passe par un compte nominatif + sudo)
printf '%s\n' 'PermitRootLogin no' 'MaxAuthTries 4' > /etc/ssh/sshd_config.d/pixelwave.conf
sshd -t && systemctl reload ssh
bash "$ICI/ufw.sh"
install -m 644 "$ICI/fail2ban/pixelwave.local" /etc/fail2ban/jail.d/pixelwave.local
systemctl restart fail2ban

# -----------------------------------------------------------------------------
log "9/9 Sauvegarde quotidienne de la base"
install -m 750 "$ICI/sauvegarde/pixelwave-sauvegarde.sh" /usr/local/sbin/pixelwave-sauvegarde.sh
install -m 644 "$ICI/sauvegarde/pixelwave-sauvegarde.cron" /etc/cron.d/pixelwave-sauvegarde
install -d -m 700 /var/backups/pixelwave

log "Terminé. Vérification : sudo bash $(dirname "$ICI")/tests/validation-portail.sh"
