# Monde 2 · Construire, tester, héberger

> Modéliser les données, développer le projet, le recetter et monter l'infrastructure qui l'accueille.

| Étape | Mission | Documentation | Réalisations |
|---|---|---|---|
| 1 · Concevoir et développer | **Conception Backend** | [8 livrables](1-conception-backend/README.md) | MCD/MLD, normalisation 3FN, 20 tables MariaDB, jeu de données, 26 requêtes, API REST conçue et documentée, collection Postman |
| 1 · Concevoir et développer | **Développement** | [documentation](2-developpement/README.md) | portail joueurs PHP 8 / MariaDB fidèle à la maquette Figma, API REST (21 routes), sécurité OWASP, notification Discord |
| 2 · Tester et héberger | **Mise en place des tests** | [stratégie, cahier de recette, PV](3-tests/README.md) | 45 tests automatisés, 43 cas de recette API et sécurité, 24 scénarios de recette, 5 anomalies corrigées |
| 2 · Tester et héberger | **Mise en place de l'infrastructure** | [documentation technique](4-infrastructure/README.md) | scripts d'installation Debian (Apache, MariaDB, BIND9, Kea, ufw, fail2ban), sauvegarde/restauration, 39 contrôles de validation |

## Où est le code ?

```
database/   scripts SQL (création, jeu de données, requêtes) et schémas
portail/    application web PHP (pages + API REST) et ses tests
api/        collection et environnement Postman
infra/      installation et configuration des serveurs, scripts de validation
docs/       documentation des missions
```
