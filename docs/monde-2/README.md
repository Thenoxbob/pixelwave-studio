# Monde 2 : construire, tester, héberger

Dans ce monde, il fallait modéliser les données, développer le projet, le tester et installer l'infrastructure qui l'héberge. Voici ce que j'ai fait pour chaque mission.

| Étape | Mission | Documentation | Ce que j'ai réalisé |
|---|---|---|---|
| 1. Concevoir et développer | Conception Backend | [les 8 livrables](1-conception-backend/README.md) | MCD et MLD, normalisation en 3FN, base MariaDB de 20 tables, jeu de données, 26 requêtes, conception et documentation de l'API REST, collection Postman |
| 1. Concevoir et développer | Développement | [documentation](2-developpement/README.md) | portail joueurs en PHP 8 et MariaDB qui reprend la maquette Figma, API REST de 21 routes, sécurité selon l'OWASP, notification Discord |
| 2. Tester et héberger | Mise en place des tests | [stratégie, cahier de recette, PV](3-tests/README.md) | 45 tests automatiques, 43 cas de recette sur l'API et la sécurité, 24 scénarios de recette, 5 anomalies corrigées |
| 2. Tester et héberger | Mise en place de l'infrastructure | [documentation technique](4-infrastructure/README.md) | scripts d'installation Debian (Apache, MariaDB, BIND9, Kea, ufw, fail2ban), sauvegarde et restauration, 39 contrôles de validation |

## Où trouver le code

```
database/   les scripts SQL (création, jeu de données, requêtes) et les schémas
portail/    l'application web en PHP (pages et API REST) et ses tests
api/        la collection et l'environnement Postman
infra/      l'installation et la configuration des serveurs, les scripts de validation
docs/       la documentation des missions
```
