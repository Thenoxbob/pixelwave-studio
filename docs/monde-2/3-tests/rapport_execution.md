# Rapport d'exécution des tests · 06/11/2026

## Environnement

| Élément | Version |
|---|---|
| Application | portail joueurs 1.0, branche `main` |
| PHP | 8.4 (serveur intégré `php -S`) |
| Base de données | MariaDB 11.4.4 |
| Navigateurs | Microsoft Edge (Chromium), navigateur intégré de l'outil de développement, Brave |
| Jeu de données | `database/02_jeu_de_donnees.sql`, base recréée avant chaque campagne |

## Synthèse

| Campagne | Exécutés | Réussis | Échecs | Taux |
|---|---|---|---|---|
| Tests unitaires | 17 | 17 | 0 | 100 % |
| Tests d'intégration (MariaDB) | 28 | 28 | 0 | 100 % |
| Recette API (HTTP) | 32 | 32 | 0 | 100 % |
| Tests de sécurité (HTTP) | 11 | 11 | 0 | 100 % |
| Collection Postman (statuts attendus) | 30 | 30 | 0 | 100 % |
| Scénarios du cahier de recette | 24 | 24 | 0 | 100 % |

Ces chiffres sont ceux de la **dernière campagne de non-régression**, relancée après la correction de l'anomalie ANO-05. Au premier passage, 2 tests automatisés avaient échoué à cause d'erreurs dans les tests eux-mêmes (détail dans [anomalies.md](anomalies.md)).

## Déroulement

1. **Tests unitaires et d'intégration** pendant le développement, après chaque fonctionnalité (`php tests/run.php`).
2. **Recette manuelle** des trois parcours de la maquette dans le navigateur : 5 anomalies trouvées (ANO-01 à ANO-05), corrigées au fil de l'eau.
3. **Recette API et sécurité** automatisée sur le serveur démarré (`recette_api.php --reinitialiser`).
4. **Collection Postman** rejouée sur une base réinitialisée.
5. **Non-régression complète** après la dernière correction : toutes les campagnes ci-dessus relancées.

## Sorties brutes

- Tests unitaires et d'intégration : [execution_tests_automatises.txt](execution_tests_automatises.txt)
- Recette API et sécurité : [execution_recette_api.txt](execution_recette_api.txt) et [tableau des 43 cas](resultats_recette_api.md)

Extrait :

```
== Suite « integration » ==
BoutiqueTest
  [OK]     testCatalogueNAfficheQueLesProduitsEnVente
  [OK]     testFiltrePromotionEtPrixRemise
  [OK]     testRechercheNeTraitePasLesJokersSql
  [OK]     testAjoutAuPanierPuisCommandeAttribueUneCle
  [OK]     testPlateformeNonProposeeEstRefusee
  [OK]     testRuptureDeStockEstSignalee
  [OK]     testProduitDejaPossedeEstRefuse
  [OK]     testProduitRetireDeLaVenteEstIntrouvable
  [OK]     testCommandeAnnuleeEntierementSiUneCleManque
  [OK]     testCommandeRembourseeNeDonnePlusAccesAuJeu
  [OK]     testPanierVideNePeutPasEtreValide
...
45 tests, 45 réussis, 0 échecs, 0 erreurs
```

```
  SEC-02  OK     Formulaire sans jeton CSRF refusé — HTTP 403
  SEC-03  OK     Appel API par cookie sans en-tête CSRF refusé — HTTP 403
  SEC-04  OK     Contenu saisi échappé à l'affichage (XSS) — HTTP 200
  SEC-08  OK     Injection SQL dans la recherche sans effet — HTTP 200
  SEC-09  OK     Blocage après 5 échecs de connexion — HTTP 429
43/43 cas conformes
```

## Couverture des exigences

| Exigence du cahier des charges | Couverte par |
|---|---|
| Créer et gérer un compte joueur | REC-01, REC-02, `CompteTest` (9 tests), API-13, API-14 |
| Authentifier de manière sécurisée | REC-03 à REC-07, SEC-01, SEC-06, SEC-09 à SEC-11 |
| Consulter les jeux possédés et les clés | REC-14 à REC-17, API-16 à API-18 |
| Accéder à la boutique | REC-08 à REC-13, `BoutiqueTest` (11 tests), API-02 à API-07, API-19 à API-24 |
| Consulter les actualités | API-08, page d'accueil (REC-08) |
| Signaler un bug, suivi ITIL | REC-18 à REC-23, `TicketTest` (8 tests), API-25 à API-31 |
| Sécuriser les services exposés | REC-24, SEC-02 à SEC-08, validation serveur VP-13 à VP-20 |

## Points non testés dans cette campagne

| Point | Raison | Prévu |
|---|---|---|
| Envoi réel au webhook Discord | pas de serveur Discord de test ; l'échec réseau est géré sans bloquer (journalisé) | test avec un salon Discord de recette |
| Montée en charge | hors périmètre de la mission | k6 / JMeter avant la mise en production |
| Installation sur les serveurs Debian | les scripts ont été vérifiés (syntaxe, cohérence) mais doivent être exécutés sur les VM du projet | scripts `infra/tests/validation-*.sh` |
