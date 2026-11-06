#!/usr/bin/env bash
# Tests de validation de SRV-INFRA (DNS + DHCP) — à lancer sur le serveur après install.sh
#   sudo bash validation-infra.sh | tee validation-infra-$(date +%F).txt
set -uo pipefail
ok=0; ko=0
verif() {
    local id="$1" desc="$2"; shift 2
    if "$@" >/dev/null 2>&1; then printf '  [OK] %-7s %s\n' "$id" "$desc"; ok=$((ok+1))
    else printf '  [KO] %-7s %s\n' "$id" "$desc"; ko=$((ko+1)); fi
}
resout() { [ "$(dig +short @127.0.0.1 "$@" | tail -1)" != "" ]; }

echo "Validation SRV-INFRA — $(hostname) — $(date '+%d/%m/%Y %H:%M')"
echo "--- DNS (BIND9)"
verif VI-01 "Service BIND9 actif"                    bash -c "systemctl is-active --quiet named || systemctl is-active --quiet bind9"
verif VI-02 "Configuration BIND valide"              named-checkconf
verif VI-03 "Zone pixelwave.lan valide"              named-checkzone pixelwave.lan /etc/bind/zones/db.pixelwave.lan
verif VI-04 "portail.pixelwave.lan -> 192.168.70.11" bash -c "dig +short @127.0.0.1 portail.pixelwave.lan | grep -qx 192.168.70.11"
verif VI-05 "srv-infra.pixelwave.lan -> 192.168.60.11" bash -c "dig +short @127.0.0.1 srv-infra.pixelwave.lan | grep -qx 192.168.60.11"
verif VI-06 "Résolution inverse 192.168.70.11"       bash -c "dig +short @127.0.0.1 -x 192.168.70.11 | grep -qx srv-portail.pixelwave.lan."
verif VI-07 "Résolution d'un nom Internet (forwarders)" resout debian.org
verif VI-08 "Nom inexistant -> NXDOMAIN"             bash -c "dig @127.0.0.1 inconnu.pixelwave.lan | grep -q NXDOMAIN"
verif VI-09 "Transfert de zone refusé"               bash -c "dig @127.0.0.1 pixelwave.lan AXFR | grep -qi 'transfer failed'"
verif VI-10 "Version de BIND masquée"                bash -c "dig +short @127.0.0.1 version.bind chaos txt | grep -q 'non communiq'"
echo "--- DHCP (Kea)"
verif VI-11 "Service Kea DHCPv4 actif"               systemctl is-active --quiet kea-dhcp4-server
verif VI-12 "Configuration Kea valide"               kea-dhcp4 -t /etc/kea/kea-dhcp4.conf
verif VI-13 "Kea en écoute sur UDP 67"               bash -c "ss -lun | grep -q ':67 '"
verif VI-14 "5 sous-réseaux déclarés (VLAN 10 à 50)" bash -c "[ \$(grep -c '\"subnet\"' /etc/kea/kea-dhcp4.conf) -eq 5 ]"
echo "--- Sécurité"
verif VI-15 "Pare-feu ufw actif"                     bash -c "ufw status | grep -q 'Status: active'"
verif VI-16 "DNS en écoute sur 192.168.60.11:53"     bash -c "ss -lun | grep -q '192.168.60.11:53 '"

echo
echo "Test manuel complémentaire (depuis un poste du VLAN 40) :"
echo "  ipconfig /release && ipconfig /renew  -> adresse 192.168.40.100-200, passerelle .1, DNS 192.168.60.11"
echo "  nslookup portail.pixelwave.lan         -> 192.168.70.11"
echo
echo "Résultat : $ok OK, $ko KO"
[[ $ko -eq 0 ]]
