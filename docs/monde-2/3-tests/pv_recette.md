# Procès-verbal de recette

| | |
|---|---|
| **Projet** | Système d'information PixelWave Studio · Portail joueurs |
| **Version recettée** | 1.0 (dépôt GitHub `pixelwave-studio`, branche `main`) |
| **Période de recette** | 06/11/2026 |
| **Environnement** | recette locale (PHP 8.4, MariaDB 11.4) |
| **Maître d'ouvrage** | Comité de pilotage PixelWave Studio |
| **Maître d'œuvre** | _(nom de l'étudiant)_ |

## 1. Objet

Le présent procès-verbal constate le résultat de la recette du portail joueurs (comptes, boutique, bibliothèque de clés, actualités, signalement et suivi des bugs, API REST) au regard du cahier des charges et du [cahier de recette](cahier_de_recette.md).

## 2. Résultats

| Indicateur | Résultat |
|---|---|
| Scénarios de recette | 24 / 24 conformes |
| Tests automatisés (unitaires + intégration) | 45 / 45 réussis |
| Recette API et sécurité | 43 / 43 conformes |
| Anomalies bloquantes ouvertes | 0 |
| Anomalies majeures ouvertes | 0 |
| Anomalies mineures ouvertes | 0 (5 détectées, 5 corrigées) |

## 3. Réserves

| N° | Réserve | Levée prévue |
|---|---|---|
| R1 | Le paiement est simulé : aucune transaction réelle n'est possible. | intégration d'un prestataire de paiement en mode test |
| R2 | La recette a été menée en environnement local ; le déploiement sur SRV-PORTAIL (DMZ) doit être validé par le script `infra/tests/validation-portail.sh`. | mission « Mise en place de l'infrastructure » |
| R3 | La notification Discord n'a pas été testée avec un vrai salon. | test avec un webhook de recette |

## 4. Décision

☐ Recette prononcée **sans réserve** : mise en production autorisée.
☐ Recette prononcée **avec réserves** : mise en production autorisée, réserves à lever dans les délais indiqués.
☐ Recette **refusée** : nouvelle campagne nécessaire.

| | Maître d'ouvrage | Maître d'œuvre |
|---|---|---|
| Nom | | |
| Date | | |
| Signature | | |
