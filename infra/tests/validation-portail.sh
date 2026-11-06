#!/usr/bin/env bash
# Tests de validation de SRV-PORTAIL — à lancer sur le serveur après install.sh
#   sudo bash validation-portail.sh | tee validation-portail-$(date +%F).txt
set -uo pipefail
ok=0; ko=0
verif() {   # verif "ID" "description" commande...
    local id="$1" desc="$2"; shift 2
    if "$@" >/dev/null 2>&1; then printf '  [OK] %-7s %s\n' "$id" "$desc"; ok=$((ok+1))
    else printf '  [KO] %-7s %s\n' "$id" "$desc"; ko=$((ko+1)); fi
}

echo "Validation SRV-PORTAIL — $(hostname) — $(date '+%d/%m/%Y %H:%M')"
echo "--- Services"
verif VP-01 "Apache actif"                      systemctl is-active --quiet apache2
verif VP-02 "MariaDB actif"                     systemctl is-active --quiet mariadb
verif VP-03 "fail2ban actif"                    systemctl is-active --quiet fail2ban
verif VP-04 "Configuration Apache valide"       apache2ctl configtest
echo "--- Réseau"
verif VP-05 "HTTPS en écoute (443)"             bash -c "ss -ltn | grep -q ':443 '"
verif VP-06 "MariaDB n'écoute qu'en local"      bash -c "ss -ltn | grep ':3306 ' | grep -qv '0.0.0.0\|\[::\]'"
verif VP-07 "Pare-feu ufw actif"                bash -c "ufw status | grep -q 'Status: active'"
verif VP-08 "SSH limité au VLAN 99"             bash -c "ufw status | grep '22/tcp' | grep -q '192.168.99.0/24'"
echo "--- Application"
verif VP-09 "Santé applicative OK (API)"         bash -c "curl -sk https://127.0.0.1/api/v1/sante | grep -q '\"base_de_donnees\": \"ok\"'"
verif VP-10 "HTTP redirigé vers HTTPS (301)"     bash -c "curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ | grep -q 301"
verif VP-11 "Page d'accueil servie (200)"        bash -c "curl -sk -o /dev/null -w '%{http_code}' https://127.0.0.1/ | grep -q 200"
verif VP-12 "Catalogue API : 8 produits"         bash -c "curl -sk https://127.0.0.1/api/v1/produits | grep -q '\"total\": 8'"
echo "--- Sécurité"
verif VP-13 "En-tête HSTS présent"               bash -c "curl -sk -D - -o /dev/null https://127.0.0.1/ | grep -qi strict-transport-security"
verif VP-14 "En-tête CSP présent"                bash -c "curl -sk -D - -o /dev/null https://127.0.0.1/ | grep -qi content-security-policy"
verif VP-15 "Version d'Apache masquée"          bash -c "! curl -sk -D - -o /dev/null https://127.0.0.1/ | grep -qi 'Server: Apache/'"
verif VP-16 "Version de PHP masquée"            bash -c "! curl -sk -D - -o /dev/null https://127.0.0.1/ | grep -qi 'X-Powered-By'"
verif VP-17 "Code source hors du web (src/)"     bash -c "curl -sk -o /dev/null -w '%{http_code}' https://127.0.0.1/src/bootstrap.php | grep -q 404"
verif VP-18 "Fichiers cachés refusés (/.git)"    bash -c "curl -sk -o /dev/null -w '%{http_code}' https://127.0.0.1/.git/config | grep -q 403"
verif VP-19 "config.php non lisible par tous"    bash -c "[ \$(stat -c %a /var/www/pixelwave/portail/config/config.php) = 640 ]"
verif VP-20 "Compte BDD sans droit d'administration" bash -c "! mariadb -N -e \"SHOW GRANTS FOR 'pixelwave_app'@'localhost'\" | grep -qE 'ALL PRIVILEGES|DROP|GRANT OPTION'"
echo "--- Sauvegarde"
verif VP-21 "Sauvegarde exécutable"              /usr/local/sbin/pixelwave-sauvegarde.sh
verif VP-22 "Archive du jour présente et intègre" bash -c "f=\$(ls -t /var/backups/pixelwave/*.sql.gz | head -1) && sha256sum -c \"\$f.sha256\" && gunzip -t \"\$f\""
verif VP-23 "Tâche cron planifiée"               test -f /etc/cron.d/pixelwave-sauvegarde

echo
echo "Résultat : $ok OK, $ko KO"
[[ $ko -eq 0 ]]
