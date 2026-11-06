#!/usr/bin/env bash
# =============================================================================
# SRV-INFRA (VLAN 60, 192.168.60.11) — Debian 12/13
# DNS interne (BIND9) et DHCP (Kea) pour les VLAN 10 à 50.
# Prérequis : adresse IP fixe configurée (voir interfaces.exemple) et, sur
# SW-COEUR, « ip helper-address 192.168.60.11 » sur les SVI des VLAN 10 à 50.
#
#   sudo ./install.sh
# =============================================================================
set -euo pipefail
ICI="$(cd "$(dirname "$0")" && pwd)"
log() { printf '\n\033[1;36m==> %s\033[0m\n' "$*"; }
[[ $EUID -eq 0 ]] || { echo "À lancer en root (sudo)."; exit 1; }

log "1/5 Paquets"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y -q bind9 bind9-utils bind9-dnsutils kea-dhcp4-server ufw fail2ban unattended-upgrades

log "2/5 BIND9 : zones pixelwave.lan et zones inverses"
install -d -o root -g bind -m 755 /etc/bind/zones
install -m 644 "$ICI/bind/named.conf.options" /etc/bind/named.conf.options
install -m 644 "$ICI/bind/named.conf.local"   /etc/bind/named.conf.local
for z in db.pixelwave.lan db.192.168.60 db.192.168.70 db.192.168.99; do
    install -o root -g bind -m 644 "$ICI/bind/$z" "/etc/bind/zones/$z"
done
named-checkconf
named-checkzone pixelwave.lan /etc/bind/zones/db.pixelwave.lan
named-checkzone 60.168.192.in-addr.arpa /etc/bind/zones/db.192.168.60
named-checkzone 70.168.192.in-addr.arpa /etc/bind/zones/db.192.168.70
named-checkzone 99.168.192.in-addr.arpa /etc/bind/zones/db.192.168.99
systemctl enable --now named 2>/dev/null || systemctl enable --now bind9
systemctl restart named 2>/dev/null || systemctl restart bind9

log "3/5 Kea DHCPv4"
install -d -o _kea -g _kea -m 750 /var/log/kea 2>/dev/null || install -d -m 750 /var/log/kea
install -m 640 "$ICI/kea/kea-dhcp4.conf" /etc/kea/kea-dhcp4.conf
chgrp _kea /etc/kea/kea-dhcp4.conf 2>/dev/null || true
kea-dhcp4 -t /etc/kea/kea-dhcp4.conf          # vérifie la syntaxe avant de redémarrer
systemctl enable --now kea-dhcp4-server
systemctl restart kea-dhcp4-server

log "4/5 Le serveur utilise son propre DNS"
cat > /etc/resolv.conf <<'EOF'
search pixelwave.lan
nameserver 127.0.0.1
EOF

log "5/5 Pare-feu local"
# connexion SSH directe en root interdite (on passe par un compte nominatif + sudo)
printf '%s\n' 'PermitRootLogin no' 'MaxAuthTries 4' > /etc/ssh/sshd_config.d/pixelwave.conf
sshd -t && systemctl reload ssh
bash "$ICI/ufw.sh"

log "Terminé. Vérification : sudo bash $(dirname "$ICI")/tests/validation-infra.sh"
