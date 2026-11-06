#!/usr/bin/env bash
# Pare-feu local de SRV-PORTAIL (défense en profondeur, en plus de l'ASA)
set -euo pipefail
VLAN_MGMT="192.168.99.0/24"     # VLAN 99 : administration

ufw --force reset >/dev/null
ufw default deny incoming
ufw default allow outgoing                       # mises à jour, webhook Discord
ufw allow 80/tcp  comment 'HTTP (redirection vers HTTPS)'
ufw allow 443/tcp comment 'HTTPS portail joueurs'
# SSH uniquement depuis le VLAN d'administration, avec limitation de débit (anti force brute)
ufw limit proto tcp from "$VLAN_MGMT" to any port 22 comment 'SSH depuis le VLAN management'
ufw logging low
ufw --force enable
ufw status verbose
