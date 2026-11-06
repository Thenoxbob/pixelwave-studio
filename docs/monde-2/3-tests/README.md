# Mission « Mise en place des tests » · Portail joueurs PixelWave

> Monde 2 · Étape 2 · Outils : tests unitaires PHP (format PHPUnit), Postman, cahier de recette, issues GitHub

## Documents de la mission

| Document | Contenu |
|---|---|
| [Stratégie de test](#1-stratégie-les-types-de-tests) (cette page) | types de tests, outils, comment les lancer |
| [Cahier de recette](cahier_de_recette.md) | 24 scénarios fonctionnels et de sécurité, avec résultat attendu et obtenu |
| [Rapport d'exécution](rapport_execution.md) | résultats chiffrés de la campagne du 06/11/2026 |
| [Suivi des anomalies](anomalies.md) | anomalies trouvées, gravité, correction |
| [Procès-verbal de recette](pv_recette.md) | décision de mise en production, réserves, signatures |
| [Résultats bruts : tests automatisés](execution_tests_automatises.txt) · [recette API](execution_recette_api.txt) · [tableau](resultats_recette_api.md) | sorties réelles des outils |

## 1. Stratégie : les types de tests

| Type | Question posée | Ce qui est testé dans le projet | Outil | Volume |
|---|---|---|---|---|
| **Unitaire** | Une fonction isolée fait-elle ce qu'elle doit ? | validation des saisies, politique de mot de passe, routage, échappement HTML, formats prix/date, empreinte des jetons | `portail/tests/unit` (format PHPUnit) | 17 tests |
| **Intégration** | Le code et la vraie base MariaDB fonctionnent-ils ensemble ? | commande transactionnelle et attribution des clés, rupture de stock, droits sur les clés et les tickets, connexion et blocage, cycle ITIL | `portail/tests/integration` (base de test recréée à chaque test) | 28 tests |
| **Fonctionnel / API** (boîte noire) | L'application répond-elle correctement à de vraies requêtes HTTP ? | les 21 routes de l'API, cas nominaux et cas d'erreur | `portail/tests/api/recette_api.php`, collection Postman | 32 cas + 30 requêtes Postman |
| **Sécurité** | Les protections résistent-elles à une attaque simple ? | CSRF, XSS, injection SQL, force brute, cookie de session, en-têtes, jeton révoqué | `recette_api.php` (cas SEC) | 11 cas |
| **Recette** (acceptation) | Le portail répond-il au cahier des charges, du point de vue du joueur et du support ? | les parcours de bout en bout dans le navigateur | [cahier de recette](cahier_de_recette.md) | 24 scénarios |
| **Non-régression** | Une correction a-t-elle cassé autre chose ? | relance complète des tests automatisés après chaque correction | `php tests/run.php` + `recette_api.php` | ≈ 1 min |
| **Validation d'infrastructure** | Le serveur est-il installé et sécurisé comme prévu ? | services, ports, pare-feu, HTTPS, sauvegarde, DNS, DHCP | `infra/tests/*.sh` | 39 contrôles (voir [mission Infrastructure](../4-infrastructure/README.md)) |

Non couverts dans cette campagne, prévus ensuite : tests de charge (montée en charge de la communauté, outil k6 ou JMeter) et test utilisateur du portail réel (le prototype Figma a déjà été testé, livrable 8 du Monde 1).

### Répartition

```
   recette manuelle ........ 24 scénarios  (lent, proche de l'utilisateur)
   API et sécurité (HTTP) .. 43 cas
   intégration (MariaDB) ... 28 tests
   unitaires ............... 17 tests      (rapide, isolé)
```

Les tests d'intégration sont plus nombreux que les tests unitaires : l'essentiel des règles métier du portail (stock de clés, droits d'accès, cycle des tickets) repose sur la base de données et ses contraintes. Les tester contre une vraie base MariaDB, recréée avant chaque test, est plus fiable que de simuler la base.

## 2. Lancer les tests

Prérequis : PHP 8.2+ avec `pdo_mysql`, un serveur MariaDB.

```bash
cd portail
cp tests/config.test.example.php tests/config.test.php     # base pixelwave_test + compte admin MariaDB
php tests/run.php                  # unitaires + intégration (sans Composer)
php tests/run.php unit             # unitaires seuls
```

Avec Composer, les mêmes fichiers tournent sous PHPUnit :

```bash
composer require --dev phpunit/phpunit
vendor/bin/phpunit
```

Recette de l'API (le portail doit être démarré) :

```bash
php -S 127.0.0.1:8080 -t public public/index.php        # dans un autre terminal
PIXELWAVE_CONFIG=tests/config.recette.php php tests/api/recette_api.php http://127.0.0.1:8080 --reinitialiser
```

`--reinitialiser` recrée la base pointée par la configuration avant la campagne, pour partir toujours du même jeu de données. Avec Postman : importer `api/pixelwave_api.postman_collection.json` puis **Run collection**.

## 3. Résultats de la campagne du 06/11/2026

| Campagne | Résultat |
|---|---|
| Tests unitaires | **17 / 17** réussis |
| Tests d'intégration (MariaDB 11.4) | **28 / 28** réussis |
| Recette API et sécurité (HTTP) | **43 / 43** cas conformes |
| Collection Postman | **30 / 30** statuts conformes |
| Cahier de recette | **24 / 24** scénarios conformes |
| Anomalies | 5 trouvées (1 bloquante, 4 mineures), 5 corrigées, 0 ouverte |

Détail : [rapport d'exécution](rapport_execution.md).

## 4. Suivi des anomalies avec GitHub

Chaque anomalie devient une **issue GitHub** créée avec le modèle [`.github/ISSUE_TEMPLATE/anomalie.md`](../../../.github/ISSUE_TEMPLATE/anomalie.md) : référence `ANO-xx`, gravité (bloquant, majeur, mineur), étapes pour reproduire, résultat attendu/obtenu, environnement. Le commit de correction mentionne l'issue (`Corrige #12`) pour la fermer automatiquement. Le tableau de suivi de cette campagne est dans [anomalies.md](anomalies.md).
