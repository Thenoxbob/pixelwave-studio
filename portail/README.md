# Portail joueurs PixelWave

Application web PHP 8 / MariaDB : comptes joueurs, boutique (jeux et DLC), bibliothèque de clés d'activation, actualités, signalement et suivi des bugs, API REST.

Documentation complète : [`docs/monde-2/2-developpement`](../docs/monde-2/2-developpement/README.md) · API : [`L8_documentation_api.md`](../docs/monde-2/1-conception-backend/L8_documentation_api.md)

## Démarrage rapide (poste de développement)

```bash
# depuis la racine du dépôt
mariadb -u root -p < database/01_creation.sql
mariadb -u root -p < database/02_jeu_de_donnees.sql
cp portail/config/config.example.php portail/config/config.php    # renseigner la connexion BDD, 'env' => 'dev'
php -S 127.0.0.1:8080 -t portail/public portail/public/index.php
```

Puis http://127.0.0.1:8080 (comptes de test : [`database/README.md`](../database/README.md)).

## Tests

```bash
cd portail
cp tests/config.test.example.php tests/config.test.php
php tests/run.php                                   # 45 tests unitaires et d'intégration
php tests/api/recette_api.php http://127.0.0.1:8080 # 43 cas de recette HTTP (portail démarré)
```

Détails : [`docs/monde-2/3-tests`](../docs/monde-2/3-tests/README.md).

## Configuration

| Clé | Rôle |
|---|---|
| `env` | `dev` (erreurs affichées) ou `prod` (erreurs journalisées seulement) |
| `url` | URL publique, utilisée dans les liens de la notification Discord |
| `db` | connexion MariaDB du compte applicatif |
| `discord_webhook` | URL du webhook Discord de l'équipe QA (vide = désactivé) |
| `jeton_duree_jours` | validité des jetons d'API |
| `connexion_max_echecs_email` / `_ip` | seuils anti force brute (fenêtre de 15 min) |
