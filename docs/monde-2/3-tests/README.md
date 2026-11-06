# Mission Mise en place des tests

Monde 2, étape 2. Outils : tests unitaires PHP (au format PHPUnit), Postman, cahier de recette, issues GitHub.

## Les documents de la mission

- cette page : les différents types de tests, les outils et comment les lancer ;
- [cahier de recette](cahier_de_recette.md) : 24 scénarios avec le résultat attendu et le résultat obtenu ;
- [rapport d'exécution](rapport_execution.md) : les résultats de la campagne du 06/11/2026 ;
- [suivi des anomalies](anomalies.md) : les problèmes trouvés, leur gravité et leur correction ;
- [procès-verbal de recette](pv_recette.md) : la décision de mise en production, les réserves et les signatures ;
- les sorties brutes des outils : [tests automatisés](execution_tests_automatises.txt), [recette de l'API](execution_recette_api.txt) et le [tableau des résultats](resultats_recette_api.md).

## 1. Les types de tests

| Type | La question qu'on se pose | Ce que je teste dans le projet | Outil | Nombre |
|---|---|---|---|---|
| unitaire | est-ce qu'une fonction seule fait ce qu'elle doit ? | validation des saisies, règles du mot de passe, routage, échappement du HTML, format des prix et des dates, empreinte des jetons | `portail/tests/unit` | 17 tests |
| intégration | est-ce que le code marche avec la vraie base MariaDB ? | commande et attribution des clés, rupture de stock, droits sur les clés et les tickets, connexion et blocage, étapes d'un ticket | `portail/tests/integration` (la base de test est recréée avant chaque test) | 28 tests |
| fonctionnel (API) | est-ce que l'appli répond bien à de vraies requêtes HTTP ? | les 21 routes de l'API, les cas normaux et les cas d'erreur | `portail/tests/api/recette_api.php` et la collection Postman | 32 cas, plus 30 requêtes Postman |
| sécurité | est-ce que les protections tiennent face à une attaque simple ? | CSRF, XSS, injection SQL, force brute, cookie de session, en-têtes, jeton révoqué | `recette_api.php` (cas SEC) | 11 cas |
| recette | est-ce que le portail répond au cahier des charges, du point de vue du joueur et du support ? | les parcours complets dans le navigateur | [cahier de recette](cahier_de_recette.md) | 24 scénarios |
| non-régression | est-ce qu'une correction a cassé autre chose ? | on relance tous les tests automatiques après chaque correction | `php tests/run.php` et `recette_api.php` | environ 1 minute |
| validation de l'infrastructure | est-ce que le serveur est installé et sécurisé comme prévu ? | services, ports, pare-feu, HTTPS, sauvegarde, DNS, DHCP | `infra/tests/*.sh` | 39 contrôles (voir la [mission Infrastructure](../4-infrastructure/README.md)) |

Je n'ai pas fait de test de charge (pour voir comment le portail tient quand beaucoup de joueurs se connectent en même temps). Ce sera à faire avant la mise en production, avec un outil comme k6 ou JMeter. Le test utilisateur, lui, a déjà été fait sur le prototype Figma au Monde 1 (livrable 8).

### Répartition

```
   recette manuelle ........ 24 scénarios  (lent, proche de l'utilisateur)
   API et sécurité (HTTP) .. 43 cas
   intégration (MariaDB) ... 28 tests
   unitaires ............... 17 tests      (rapide, isolé)
```

J'ai plus de tests d'intégration que de tests unitaires. C'est parce que la plupart des règles du portail (le stock de clés, les droits d'accès, les étapes d'un ticket) passent par la base de données et ses contraintes. Je trouve plus fiable de les tester sur une vraie base MariaDB, recréée avant chaque test, plutôt que de faire semblant d'avoir une base.

## 2. Lancer les tests

Il faut PHP 8.2 ou plus avec `pdo_mysql`, et un serveur MariaDB.

```bash
cd portail
cp tests/config.test.example.php tests/config.test.php     # base pixelwave_test et compte admin MariaDB
php tests/run.php                  # tests unitaires et d'intégration, sans Composer
php tests/run.php unit             # seulement les tests unitaires
```

Si Composer est installé, les mêmes fichiers marchent avec PHPUnit :

```bash
composer require --dev phpunit/phpunit
vendor/bin/phpunit
```

Pour la recette de l'API, il faut que le portail soit lancé :

```bash
php -S 127.0.0.1:8080 -t public public/index.php        # dans un autre terminal
PIXELWAVE_CONFIG=tests/config.recette.php php tests/api/recette_api.php http://127.0.0.1:8080 --reinitialiser
```

L'option `--reinitialiser` recrée la base avant de commencer, pour toujours partir des mêmes données. Avec Postman, il suffit d'importer `api/pixelwave_api.postman_collection.json` et de cliquer sur Run collection.

## 3. Résultats de la campagne du 06/11/2026

| Campagne | Résultat |
|---|---|
| tests unitaires | 17 sur 17 réussis |
| tests d'intégration (MariaDB 11.4) | 28 sur 28 réussis |
| recette de l'API et de la sécurité | 43 cas sur 43 conformes |
| collection Postman | 30 statuts sur 30 conformes |
| cahier de recette | 24 scénarios sur 24 conformes |
| anomalies | 5 trouvées (1 bloquante, 4 mineures), 5 corrigées, aucune encore ouverte |

Le détail est dans le [rapport d'exécution](rapport_execution.md).

## 4. Suivi des anomalies sur GitHub

Chaque anomalie doit devenir une issue GitHub, créée avec le modèle [`.github/ISSUE_TEMPLATE/anomalie.md`](../../../.github/ISSUE_TEMPLATE/anomalie.md). On y met la référence `ANO-xx`, la gravité (bloquante, majeure ou mineure), les étapes pour reproduire, le résultat attendu et obtenu, et l'environnement. Le commit qui corrige le problème cite l'issue (par exemple `Corrige #12`), ce qui la ferme automatiquement. Le tableau de suivi de cette campagne est dans [anomalies.md](anomalies.md).
