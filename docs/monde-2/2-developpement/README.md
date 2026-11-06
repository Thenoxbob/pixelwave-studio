# Mission Développement : le portail joueurs

Monde 2, étape 1. Technologies : HTML, CSS, JavaScript, PHP 8, MariaDB, Git.

Le code est dans [`portail/`](../../../portail/) et la base de données dans [`database/`](../../../database/).

## Les livrables

| Livrable | Document |
|---|---|
| 1. WordPress ou code sur mesure | [L1_wordpress_ou_sur_mesure.md](L1_wordpress_ou_sur_mesure.md) |
| 2. Création de l'API à partir de la conception | [L2_creation_api.md](L2_creation_api.md) |
| 3. Conception du backend (architecture) | [L3_architecture_backend.md](L3_architecture_backend.md) |
| 4. Intégration HTML et CSS | [L4_integration_html_css.md](L4_integration_html_css.md) |
| 5. JavaScript | [L5_javascript.md](L5_javascript.md) |
| 6. Communication avec l'API | [L6_communication_api.md](L6_communication_api.md) |
| 7. Authentification | [L7_authentification.md](L7_authentification.md) |
| 8. Sécurité du code | [L8_securite_code.md](L8_securite_code.md) |
| 9. Historique Git | [L9_historique_git.md](L9_historique_git.md) |
| 10. Preuve d'accomplissement | [L10_preuve_accomplissement.md](L10_preuve_accomplissement.md) |

## 1. Ce que j'ai développé

J'ai codé le portail joueurs de la maquette Figma, branché sur la base de données de la mission Conception Backend. Voici ce qui a été fait pour chaque besoin du cahier des charges :

| Besoin | Pages | Routes de l'API |
|---|---|---|
| créer et gérer un compte joueur | `/inscription`, `/connexion`, `/compte` (profil, mot de passe, newsletter, jetons) | `POST /auth/jetons`, `GET /moi` |
| voir ses jeux et ses clés | `/bibliotheque` (clé masquée, boutons « Afficher » et « Copier ») | `GET /moi/bibliotheque`, `POST /moi/cles/{id}/revelation` |
| utiliser la boutique | `/boutique` (filtres, tri, recherche), `/boutique/{slug}`, `/panier` | `/produits`, `/moi/panier`, `/moi/commandes` |
| lire les actualités | `/`, `/actualites`, `/actualites/{slug}` | `/actualites` |
| signaler un bug et suivre sa correction | `/support`, `/support/nouveau`, `/support/{reference}` | `/tickets` |
| traitement des tickets par le support (ITIL) | file des tickets, qualification, affectation, notes internes | `PATCH /tickets/{ref}` |
| communiquer avec un service externe | message envoyé sur Discord à chaque nouveau bug | |
| aide en ligne pour les joueurs | `/aide`, `/confidentialite` | |

Les trois parcours du prototype Figma marchent du début à la fin : acheter le DLC Circuit Nocturne, récupérer et copier sa clé, signaler un bug et suivre son ticket.

## 2. Captures d'écran

| Accueil (comme sur la maquette Figma) | Boutique |
|---|---|
| ![Accueil](img/app_accueil.png) | ![Boutique](img/app_boutique.png) |

| Fiche produit | Ma bibliothèque |
|---|---|
| ![Produit](img/app_produit.png) | ![Bibliothèque](img/app_bibliotheque.png) |

| Signaler un bug | Suivi d'un ticket côté joueur |
|---|---|
| ![Signaler](img/app_signaler_bug.png) | ![Ticket joueur](img/app_ticket_joueur.png) |

| File du support | Ticket côté support (note interne, traitement) |
|---|---|
| ![File support](img/app_file_support.png) | ![Ticket support](img/app_ticket_support.png) |

| Mon compte (commandes, jetons API) | Panier | Sur mobile |
|---|---|---|
| ![Compte](img/app_compte.png) | ![Panier](img/app_panier.png) | ![Mobile](img/app_mobile_accueil.png) |

| Validation des formulaires en JavaScript |
|---|
| ![Validation JS](img/app_validation_js.png) |

## 3. Lancer le portail sur son PC

Il faut PHP 8.2 ou plus (avec l'extension `pdo_mysql`) et MariaDB 10.11 ou plus.

```bash
mariadb -u root -p < database/01_creation.sql
mariadb -u root -p < database/02_jeu_de_donnees.sql
cp portail/config/config.example.php portail/config/config.php   # puis remplir la connexion à la base
php -S 127.0.0.1:8080 -t portail/public portail/public/index.php
```

Ensuite on ouvre http://127.0.0.1:8080. Les comptes de démonstration sont dans [`database/README.md`](../../../database/README.md).

Pour l'installation sur le serveur, voir la [mission Infrastructure](../4-infrastructure/README.md).

## 4. Limites et améliorations prévues

- Le paiement est simulé. Il faudra brancher un vrai prestataire (Stripe ou PayPal), d'abord en mode test.
- On ne peut pas encore réinitialiser son mot de passe par e-mail. Il faudra envoyer un lien à usage unique, avec le serveur mail du studio.
- Il n'y a pas d'interface d'administration du catalogue : pour l'instant les produits, les promos et les clés s'ajoutent en SQL.
- Les tickets du portail ne sont pas encore synchronisés avec GLPI. On pourra le faire avec l'API REST de GLPI.
- Les polices sont chargées depuis Google Fonts. Pour le RGPD, ce serait mieux de les héberger sur notre serveur.
