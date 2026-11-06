# Rapport d'exécution des tests (06/11/2026)

## Environnement

- Application : portail joueurs 1.0, branche `main`
- PHP 8.4, avec le serveur intégré `php -S`
- MariaDB 11.4.4
- Navigateurs : Microsoft Edge et Brave
- Données : `database/02_jeu_de_donnees.sql`, avec la base recréée avant chaque campagne

## Résultats

| Campagne | Lancés | Réussis | Échecs | Taux |
|---|---|---|---|---|
| tests unitaires | 17 | 17 | 0 | 100 % |
| tests d'intégration (MariaDB) | 28 | 28 | 0 | 100 % |
| recette de l'API (HTTP) | 32 | 32 | 0 | 100 % |
| tests de sécurité (HTTP) | 11 | 11 | 0 | 100 % |
| collection Postman | 30 | 30 | 0 | 100 % |
| scénarios du cahier de recette | 24 | 24 | 0 | 100 % |

Ces chiffres viennent de la dernière campagne, que j'ai relancée en entier après avoir corrigé l'anomalie ANO-05. Au premier passage, 2 tests automatiques avaient échoué, mais c'était une erreur dans l'écriture des tests (c'est expliqué dans [anomalies.md](anomalies.md)).

## Comment ça s'est déroulé

1. Pendant le développement, j'ai lancé les tests unitaires et d'intégration après chaque fonctionnalité (`php tests/run.php`).
2. J'ai fait la recette à la main des trois parcours de la maquette dans le navigateur. C'est là que j'ai trouvé les 5 anomalies (ANO-01 à ANO-05), que j'ai corrigées au fur et à mesure.
3. J'ai lancé la recette automatique de l'API et de la sécurité sur le portail démarré (`recette_api.php --reinitialiser`).
4. J'ai rejoué la collection Postman sur une base remise à zéro.
5. Après la dernière correction, j'ai tout relancé pour vérifier que rien n'avait été cassé.

## Sorties brutes

- tests unitaires et d'intégration : [execution_tests_automatises.txt](execution_tests_automatises.txt)
- recette de l'API et de la sécurité : [execution_recette_api.txt](execution_recette_api.txt) et le [tableau des 43 cas](resultats_recette_api.md)

Un extrait :

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

## Ce qui est couvert

| Besoin du cahier des charges | Tests qui le vérifient |
|---|---|
| créer et gérer un compte joueur | REC-01, REC-02, les 9 tests de `CompteTest`, API-13, API-14 |
| se connecter de façon sécurisée | REC-03 à REC-07, SEC-01, SEC-06, SEC-09 à SEC-11 |
| voir ses jeux et ses clés | REC-14 à REC-17, API-16 à API-18 |
| utiliser la boutique | REC-08 à REC-13, les 11 tests de `BoutiqueTest`, API-02 à API-07, API-19 à API-24 |
| lire les actualités | API-08, et la page d'accueil (REC-08) |
| signaler un bug et le suivre (ITIL) | REC-18 à REC-23, les 8 tests de `TicketTest`, API-25 à API-31 |
| sécuriser ce qui est accessible depuis Internet | REC-24, SEC-02 à SEC-08, et sur le serveur VP-13 à VP-20 |

## Ce qui n'a pas été testé

- L'envoi réel du message Discord : je n'avais pas de serveur Discord de test. Si Discord ne répond pas, l'erreur est notée dans le journal et le ticket est quand même créé. Il faudra le tester avec un salon de recette.
- La tenue en charge : ce n'était pas demandé pour cette mission, mais il faudra le faire avant la mise en production (avec k6 ou JMeter).
- L'installation sur les serveurs Debian : j'ai vérifié les scripts (syntaxe et cohérence), mais ils doivent encore être lancés sur les VM du projet, avec les scripts `infra/tests/validation-*.sh`.
