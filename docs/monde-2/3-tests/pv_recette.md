# Procès-verbal de recette

| | |
|---|---|
| Projet | Système d'information PixelWave Studio, portail joueurs |
| Version testée | 1.0 (dépôt GitHub `pixelwave-studio`, branche `main`) |
| Date de la recette | 06/11/2026 |
| Environnement | recette en local (PHP 8.4, MariaDB 11.4) |
| Maître d'ouvrage | comité de pilotage de PixelWave Studio |
| Maître d'œuvre | |

## 1. Objet

Ce procès-verbal donne le résultat de la recette du portail joueurs (comptes, boutique, bibliothèque de clés, actualités, signalement et suivi des bugs, API REST), en le comparant au cahier des charges et au [cahier de recette](cahier_de_recette.md).

## 2. Résultats

| Indicateur | Résultat |
|---|---|
| scénarios du cahier de recette | 24 sur 24 conformes |
| tests automatisés (unitaires et intégration) | 45 sur 45 réussis |
| recette de l'API et de la sécurité | 43 sur 43 conformes |
| anomalies bloquantes encore ouvertes | 0 |
| anomalies majeures encore ouvertes | 0 |
| anomalies mineures encore ouvertes | 0 (5 trouvées, 5 corrigées) |

## 3. Réserves

1. Le paiement est simulé, on ne peut pas faire de vrai achat. Il faudra brancher un prestataire de paiement, d'abord en mode test.
2. La recette a été faite en local. Il faudra valider l'installation sur SRV-PORTAIL (dans la DMZ) avec le script `infra/tests/validation-portail.sh`, pendant la mission Mise en place de l'infrastructure.
3. Le message envoyé sur Discord n'a pas été testé avec un vrai salon. Il faudra le tester avec un webhook de recette.

## 4. Décision

- [ ] Recette acceptée sans réserve : mise en production autorisée.
- [ ] Recette acceptée avec réserves : mise en production autorisée, les réserves seront levées plus tard.
- [ ] Recette refusée : il faudra refaire une campagne de tests.

| | Maître d'ouvrage | Maître d'œuvre |
|---|---|---|
| Nom | | |
| Date | | |
| Signature | | |
