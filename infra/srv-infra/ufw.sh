#!/usr/bin/env bash
# Pare-feu local de SRV-INFRA : DNS et DHCP pour les VLAN internes, SSH depuis le management
set -euo pipefail
INTERNES=(192.168.10.0/24 192.168.20.0/24 192.168.30.0/24 192.168.40.0/24 192.168.50.0/24 192.168.60.0/24 192.168.99.0/24)

ufw --force reset >/dev/null
ufw default deny incoming
ufw default allow outgoing
for reseau in "${INTERNES[@]}"; do
    ufw allow from "$reseau" to any port 53 comment 'DNS interne'
    ufw allow proto udp from "$reseau" to any port 67 comment 'DHCP (relais SW-COEUR)'
done
ufw limit proto tcp from 192.168.99.0/24 to any port 22 comment 'SSH depuis le VLAN management'
ufw logging low
ufw --force enable
ufw status verbose
