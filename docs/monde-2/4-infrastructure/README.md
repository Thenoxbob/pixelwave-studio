# Mission « Mise en place de l'infrastructure » · Documentation technique

> Monde 2 · Étape 2 · Debian, Apache, MariaDB, BIND9, DHCP (Kea), ufw
> Scripts et fichiers de configuration : [`infra/`](../../../infra/)

## 1. Objectif et périmètre

Déployer l'infrastructure qui héberge le portail joueurs et fournit les services réseau de base, dans le respect de l'architecture du SI (livrable 1) et du schéma réseau (livrable 2) du Monde 1 :

| Serveur | Rôle | Emplacement |
|---|---|---|
| **SRV-PORTAIL** | serveur web (Apache + PHP), base de données (MariaDB), portail joueurs | DMZ, VLAN 70, `192.168.70.11` |
| **SRV-INFRA** | DNS interne (BIND9), DHCP (Kea) pour les VLAN des services | VLAN 60, `192.168.60.11` |

Les deux serveurs sont des machines virtuelles Debian sur l'hyperviseur du studio.

## 2. Architecture d'hébergement

![Architecture d'hébergement](img/architecture_hebergement.png)

Points clés :

- Le portail est **dans la DMZ** : depuis Internet, seuls les ports 80 et 443 de SRV-PORTAIL sont joignables (NAT statique et ACL sur l'ASA).
- La DMZ **ne peut pas ouvrir de connexion vers le LAN** (règle `no forward` de l'ASA, livrable 2). Conséquences assumées :
  - la base MariaDB est **sur le même serveur** que le portail, à l'écoute de `127.0.0.1` uniquement ;
  - SRV-PORTAIL utilise des **résolveurs DNS publics** (le DNS interne n'est pas joignable depuis la DMZ) ;
  - la sauvegarde est **tirée** depuis le LAN (le serveur de sauvegarde vient chercher les fichiers), jamais poussée depuis la DMZ.
- L'administration (SSH) se fait **uniquement depuis le VLAN 99** (management).
- Les postes des VLAN 10 à 50 obtiennent leur adresse par DHCP **relayé** par SW-COEUR vers SRV-INFRA, et résolvent `portail.pixelwave.lan` en interne.

## 3. Choix techniques

| Besoin | Choix | Justification | Alternative écartée |
|---|---|---|---|
| Système | **Debian 12/13** (stable) | gratuit, stable, support de sécurité long, paquets à jour pour PHP 8 et MariaDB | Ubuntu Server (plus de changements entre versions) |
| Virtualisation | **Proxmox VE** | gratuit, interface web, sauvegarde et instantanés des VM intégrés | VMware ESXi (licence payante depuis 2024) |
| Serveur web | **Apache 2.4 + mod_php** | demandé par le référentiel, configuration par hôte virtuel bien documentée | Nginx + PHP-FPM (plus performant mais plus de composants) |
| Base de données | **MariaDB**, locale | compatible MySQL (cours, phpMyAdmin) ; locale car la DMZ est isolée du LAN | serveur BDD séparé dans la DMZ (une VM de plus pour 50 000 comptes : surdimensionné aujourd'hui) |
| DNS | **BIND9** | référence du marché, zones directes et inverses, ACL, délégation future vers l'AD | dnsmasq (plus simple, mais moins adapté à plusieurs zones et à la délégation) |
| DHCP | **Kea DHCPv4** | successeur officiel d'ISC DHCP (en fin de vie), configuration JSON vérifiable (`kea-dhcp4 -t`) | isc-dhcp-server (plus maintenu par l'ISC depuis 2022) |
| Pare-feu local | **ufw** | règles lisibles, défense en profondeur en plus de l'ASA | iptables brut (plus difficile à maintenir) |
| Anti force brute SSH | **fail2ban** | bannit automatiquement les IP après 5 échecs | — |
| Certificat | autosigné (laboratoire) → **Let's Encrypt** en production | HTTPS obligatoire (mots de passe, clés de jeux) | HTTP seul (refusé) |

## 4. Dimensionnement des machines virtuelles

| VM | vCPU | RAM | Disque | Justification |
|---|---|---|---|---|
| SRV-PORTAIL | 2 | 4 Go | 40 Go | Apache + PHP + MariaDB ; base estimée < 1 Go pour 50 000 comptes ; 14 jours de sauvegardes compressées |
| SRV-INFRA | 1 | 1 Go | 16 Go | BIND9 et Kea sont très légers (≈ 80 postes, marge à 80 utilisateurs internes) |

Évolution : si la charge du portail augmente (lancement d'un jeu), ajout de vCPU/RAM à chaud dans Proxmox, puis séparation de la base sur une VM dédiée de la DMZ.

## 5. Plan d'adressage des serveurs et services

| Nom DNS | Adresse | VLAN | Rôle |
|---|---|---|---|
| `srv-portail.pixelwave.lan` (alias `portail`) | 192.168.70.11 | 70 DMZ | portail joueurs |
| `srv-web.pixelwave.lan` (alias `www`) | 192.168.70.10 | 70 DMZ | site vitrine |
| `asa-dmz.pixelwave.lan` | 192.168.70.1 | 70 DMZ | passerelle de la DMZ |
| `srv-infra.pixelwave.lan` (alias `ns1`) | 192.168.60.11 | 60 | DNS + DHCP |
| `srv-ad`, `srv-glpi`, `srv-fichiers`, `srv-supervision`, `srv-sauvegarde` | 192.168.60.10, .12 à .15 | 60 | réservés pour les missions suivantes |
| `sw-coeur.pixelwave.lan` | 192.168.99.2 | 99 | commutateur cœur |
| `pve.pixelwave.lan` | 192.168.99.10 | 99 | hyperviseur |
| adresse publique du portail | 200.100.100.3 | outside | NAT statique vers 192.168.70.11 |

**Plages DHCP** (Kea, bail de 8 h) :

| VLAN | Réseau | Passerelle | Plage distribuée | DNS |
|---|---|---|---|---|
| 10 Direction | 192.168.10.0/24 | .1 | .100 à .200 | 192.168.60.11 |
| 20 Développement | 192.168.20.0/24 | .1 | .100 à .200 | 192.168.60.11 |
| 30 Marketing | 192.168.30.0/24 | .1 | .100 à .200 | 192.168.60.11 |
| 40 Support / QA | 192.168.40.0/24 | .1 | .100 à .200 (réservation `imp-support` .20) | 192.168.60.11 |
| 50 Administration | 192.168.50.0/24 | .1 | .100 à .200 | 192.168.60.11 |

Les serveurs (VLAN 60, 70) et équipements (VLAN 99) ont des adresses **fixes**. Le VLAN 80 (Wi-Fi invités) est servi par son point d'accès avec un DNS public : il n'a accès ni au DNS ni au DHCP internes.

## 6. Configuration réseau à appliquer

**SW-COEUR** : relayer les demandes DHCP vers SRV-INFRA sur chaque interface VLAN des services.

```
interface Vlan10
 ip helper-address 192.168.60.11
! idem pour Vlan20, Vlan30, Vlan40, Vlan50
```

**ASA** : publier le portail (NAT statique) et n'autoriser que HTTP/HTTPS depuis Internet.

```
object network SRV-PORTAIL
 host 192.168.70.11
 nat (dmz,outside) static 200.100.100.3
access-list OUTSIDE-IN extended permit tcp any object SRV-PORTAIL eq 443
access-list OUTSIDE-IN extended permit tcp any object SRV-PORTAIL eq 80
access-group OUTSIDE-IN in interface outside
```

## 7. Procédure d'installation

### SRV-PORTAIL

1. Créer la VM (Debian, 2 vCPU, 4 Go, 40 Go), carte réseau sur le VLAN 70.
2. Installer Debian sans interface graphique (« serveur SSH » + « utilitaires usuels »), créer un compte administrateur, désactiver la connexion SSH de root (`PermitRootLogin no`).
3. Configurer l'adresse fixe : modèle [`infra/srv-portail/interfaces.exemple`](../../../infra/srv-portail/interfaces.exemple).
4. Récupérer le dépôt et lancer l'installation :
   ```bash
   sudo apt install -y git
   git clone https://github.com/Thenoxbob/pixelwave-studio.git
   sudo bash pixelwave-studio/infra/srv-portail/install.sh
   ```
   Le script ([`install.sh`](../../../infra/srv-portail/install.sh)) : installe Apache, PHP, MariaDB ; limite MariaDB à `127.0.0.1` et supprime les comptes anonymes ; déploie le code dans `/var/www/pixelwave` ; crée la base, le jeu de démonstration et un **compte BDD aux droits minimaux avec un mot de passe aléatoire** ; durcit PHP ; crée l'hôte virtuel HTTPS ; active ufw, fail2ban, les mises à jour de sécurité automatiques et la sauvegarde nocturne.
5. Valider : `sudo bash pixelwave-studio/infra/tests/validation-portail.sh` (section 10).

### SRV-INFRA

1. Créer la VM (Debian, 1 vCPU, 1 Go, 16 Go), carte réseau sur le VLAN 60, adresse fixe ([`interfaces.exemple`](../../../infra/srv-infra/interfaces.exemple)).
2. `sudo bash pixelwave-studio/infra/srv-infra/install.sh` : installe BIND9 et Kea, vérifie la configuration (`named-checkconf`, `named-checkzone`, `kea-dhcp4 -t`) **avant** de redémarrer les services, active ufw.
3. Configurer le relais DHCP sur SW-COEUR (section 6).
4. Valider : `sudo bash pixelwave-studio/infra/tests/validation-infra.sh`, puis depuis un poste du VLAN 40 : `ipconfig /renew` et `nslookup portail.pixelwave.lan`.

### Mettre à jour le portail

```bash
sudo bash /var/www/pixelwave/infra/srv-portail/install.sh   # récupère la dernière version de main, conserve config.php et la base
```

## 8. Sécurisation

| Mesure | SRV-PORTAIL | SRV-INFRA |
|---|---|---|
| Pare-feu local ufw, refus par défaut | 80, 443 ouverts ; 22 depuis le VLAN 99 seulement | 53 et 67 depuis les VLAN internes ; 22 depuis le VLAN 99 |
| SSH | limité (`ufw limit`), fail2ban (5 essais → 1 h), root interdit | idem |
| Mises à jour de sécurité | `unattended-upgrades` | `unattended-upgrades` |
| Services exposés | Apache seul ; MariaDB sur `127.0.0.1` | BIND sur `192.168.60.11`, récursion réservée aux VLAN internes, transfert de zone interdit, version masquée |
| Chiffrement | HTTPS (TLS 1.2/1.3), redirection HTTP → HTTPS, HSTS | — |
| Divulgation d'informations | `ServerTokens Prod`, `expose_php Off`, `display_errors Off` | `version "non communiquée"` |
| Fichiers | seul `public/` est servi ; fichiers cachés refusés ; `config.php` en 640 root:www-data | zones en 644 root:bind |
| Base de données | compte applicatif `SELECT/INSERT/UPDATE/DELETE` uniquement, mot de passe aléatoire | — |
| Application | protections OWASP (voir [mission Développement](../2-developpement/README.md#4-sécurité)) | — |

## 9. Sauvegarde et restauration

| Élément | Fréquence | Outil | Conservation |
|---|---|---|---|
| Base `pixelwave` (dump SQL compressé + empreinte SHA-256) | chaque nuit à 02h30 | [`pixelwave-sauvegarde.sh`](../../../infra/srv-portail/sauvegarde/pixelwave-sauvegarde.sh) via cron | 14 jours sur le serveur |
| Fichier de configuration du portail | chaque nuit | même script | 14 jours |
| VM complètes | chaque semaine | sauvegarde Proxmox (vzdump) | 4 semaines |
| Copie hors DMZ | chaque nuit | SRV-SAUVEGARDE tire `/var/backups/pixelwave` par rsync/SSH | selon la politique du serveur de sauvegarde |

**Restauration** : `sudo infra/srv-portail/sauvegarde/restauration.sh /var/backups/pixelwave/pixelwave-AAAAMMJJ-HHMM.sql.gz`. Le script vérifie l'empreinte (refuse un fichier corrompu), demande confirmation, met le portail en maintenance, restaure la base, remet le portail en ligne et appelle `/api/v1/sante`.

**Test de restauration à réaliser** (et à consigner) : supprimer un ticket de test → restaurer la sauvegarde de la nuit → vérifier que le ticket est revenu et que `/api/v1/sante` répond `ok`.

## 10. Tests de validation

Deux scripts vérifient automatiquement l'installation et affichent `OK`/`KO` pour chaque contrôle :

```bash
sudo bash infra/tests/validation-portail.sh | tee validation-portail-$(date +%F).txt
sudo bash infra/tests/validation-infra.sh   | tee validation-infra-$(date +%F).txt
```

### SRV-PORTAIL ([`validation-portail.sh`](../../../infra/tests/validation-portail.sh))

| ID | Contrôle | Résultat attendu |
|---|---|---|
| VP-01 à VP-03 | Apache, MariaDB, fail2ban actifs | services `active` |
| VP-04 | `apache2ctl configtest` | `Syntax OK` |
| VP-05 | HTTPS en écoute | port 443 ouvert |
| VP-06 | MariaDB isolée | port 3306 sur `127.0.0.1` uniquement |
| VP-07, VP-08 | ufw actif, SSH réservé au VLAN 99 | règles présentes |
| VP-09 | `GET /api/v1/sante` | `"base_de_donnees": "ok"` |
| VP-10 | `http://` | redirection 301 vers HTTPS |
| VP-11, VP-12 | accueil, catalogue API | 200, 8 produits |
| VP-13 à VP-16 | en-têtes HSTS et CSP présents, versions Apache/PHP masquées | conformes |
| VP-17, VP-18 | code source et fichiers cachés inaccessibles | 404, 403 |
| VP-19, VP-20 | `config.php` en 640 ; compte BDD sans droit d'administration | conformes |
| VP-21 à VP-23 | sauvegarde exécutable, archive intègre, cron en place | conformes |

### SRV-INFRA ([`validation-infra.sh`](../../../infra/tests/validation-infra.sh))

| ID | Contrôle | Résultat attendu |
|---|---|---|
| VI-01 à VI-03 | BIND9 actif, configuration et zone valides | OK |
| VI-04, VI-05 | `portail.pixelwave.lan`, `srv-infra.pixelwave.lan` | 192.168.70.11, 192.168.60.11 |
| VI-06 | résolution inverse de 192.168.70.11 | `srv-portail.pixelwave.lan.` |
| VI-07 | nom Internet (`debian.org`) | résolu via les forwarders |
| VI-08 | nom inexistant | `NXDOMAIN` |
| VI-09, VI-10 | transfert de zone, version de BIND | refusé, masquée |
| VI-11 à VI-14 | Kea actif, configuration valide, UDP 67, 5 sous-réseaux | OK |
| VI-15, VI-16 | ufw actif, DNS en écoute sur 192.168.60.11 | OK |

> **État** : les scripts d'installation et de validation sont prêts et vérifiés (syntaxe Bash, JSON Kea valide, zones cohérentes avec le plan d'adressage). Ils doivent être **exécutés sur les VM du projet** ; joindre ensuite les fichiers `validation-*.txt` produits à ce dossier comme preuve de validation.

## 11. Exploitation

| Besoin | Commande / emplacement |
|---|---|
| Journaux Apache | `/var/log/apache2/pixelwave-acces.log`, `pixelwave-erreurs.log` |
| Erreurs PHP | `/var/log/pixelwave/php-erreurs.log` |
| Requêtes lentes | `/var/log/mysql/mariadb-lent.log` |
| Journal DHCP | `/var/log/kea/kea-dhcp4.log` |
| Ajouter un nom DNS | éditer `db.pixelwave.lan`, **incrémenter le numéro de série**, `named-checkzone` puis `rndc reload` |
| Baux DHCP actifs | `/var/lib/kea/kea-leases4.csv` |
| IP bannies | `sudo fail2ban-client status sshd` |
| Santé du portail (supervision) | `GET https://portail.pixelwave.lan/api/v1/sante` (200 = OK, 503 = base injoignable) |
