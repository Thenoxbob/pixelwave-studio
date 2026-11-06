# Mission « Développement » · Portail joueurs PixelWave

> Monde 2 · Étape 1 · Technologies : HTML, CSS, JavaScript, PHP 8, MariaDB, Git
> Code source : [`portail/`](../../../portail/) · Base de données : [`database/`](../../../database/)

## 1. Ce qui a été développé

Le portail joueurs de la maquette Figma, relié à la base de données de la mission « Conception Backend » :

| Fonctionnalité du cahier des charges | Pages | API |
|---|---|---|
| Créer et gérer un compte joueur | `/inscription`, `/connexion`, `/compte` (profil, mot de passe, newsletter, jetons) | `POST /auth/jetons`, `GET /moi` |
| Consulter les jeux possédés et les clés | `/bibliotheque` (clé masquée, « Afficher », « Copier ») | `GET /moi/bibliotheque`, `POST /moi/cles/{id}/revelation` |
| Accéder à la boutique (jeux et DLC) | `/boutique` (filtres, tri, recherche), `/boutique/{slug}`, `/panier` | `/produits`, `/moi/panier`, `/moi/commandes` |
| Consulter les actualités | `/`, `/actualites`, `/actualites/{slug}` | `/actualites` |
| Signaler un bug et suivre sa correction | `/support`, `/support/nouveau`, `/support/{reference}` | `/tickets` |
| Traitement ITIL par le support | file des tickets, qualification, affectation, notes internes | `PATCH /tickets/{ref}` |
| Communiquer avec un service externe | notification Discord (webhook) à chaque nouveau signalement | — |
| Aide en ligne pour les joueurs | `/aide`, `/confidentialite` | — |

Les trois parcours du prototype Figma fonctionnent de bout en bout : **acheter le DLC Circuit Nocturne**, **récupérer et copier la clé**, **signaler un bug et suivre le ticket**.

## 2. Captures d'écran

| Accueil (maquette Figma respectée) | Boutique |
|---|---|
| ![Accueil](img/app_accueil.png) | ![Boutique](img/app_boutique.png) |

| Fiche produit | Ma bibliothèque |
|---|---|
| ![Produit](img/app_produit.png) | ![Bibliothèque](img/app_bibliotheque.png) |

| Signaler un bug | Suivi d'un ticket (joueur) |
|---|---|
| ![Signaler](img/app_signaler_bug.png) | ![Ticket joueur](img/app_ticket_joueur.png) |

| File du support | Ticket côté support (note interne, traitement) |
|---|---|
| ![File support](img/app_file_support.png) | ![Ticket support](img/app_ticket_support.png) |

| Mon compte (commandes, jetons API) | Panier | Mobile |
|---|---|---|
| ![Compte](img/app_compte.png) | ![Panier](img/app_panier.png) | ![Mobile](img/app_mobile_accueil.png) |

## 3. Architecture du code

Pas de framework : un PHP structuré à la main, pour maîtriser chaque couche. Aucune dépendance à installer.

```
portail/
├── public/                  ← seul dossier exposé par Apache (DocumentRoot)
│   ├── index.php            ← contrôleur frontal : routes, en-têtes de sécurité, gestion des erreurs
│   └── assets/css, js       ← charte graphique Figma, JavaScript progressif
├── src/
│   ├── bootstrap.php        ← chargement automatique des classes, configuration
│   ├── helpers.php          ← e() (échappement), prix(), date_fr()...
│   ├── Core/                ← Router, Request, View, Session, Csrf, Auth, Validator, Database
│   ├── Repository/          ← toutes les requêtes SQL, une classe par ressource
│   ├── Service/             ← règles métier partagées par le site et l'API
│   │   ├── BoutiqueService  ← panier, commande transactionnelle, attribution des clés
│   │   ├── CompteService    ← inscription, connexion, anti force brute
│   │   ├── TicketService    ← signalement, droits, cycle ITIL
│   │   └── DiscordNotifier  ← service externe
│   └── Controller/
│       ├── SiteController   ← pages HTML
│       └── ApiController    ← API REST JSON
├── templates/               ← vues PHP (gabarit commun + une vue par page)
├── config/config.example.php← modèle de configuration (config.php non versionné)
└── tests/                   ← tests unitaires, d'intégration et recette API
```

**Principe** : le contrôleur reçoit la requête, appelle un **service** (règles métier) qui s'appuie sur des **repositories** (SQL), puis rend une **vue** (HTML) ou du **JSON**. Le site et l'API appellent les mêmes services : une règle (ex. « pas de clé pour une plateforme non proposée ») n'est écrite qu'une fois.

### Exemple : validation d'une commande

```
POST /panier/valider  ou  POST /api/v1/moi/commandes
  → BoutiqueService::validerCommande()
      BEGIN
      SELECT ... FROM panier_ligne ... FOR UPDATE           (verrou sur le panier)
      INSERT commande, puis pour chaque article :
        SELECT id_cle ... WHERE id_ligne IS NULL LIMIT 1 FOR UPDATE   (verrou sur la clé)
        INSERT ligne_commande (prix du jour) ; UPDATE cle_activation (attribution)
      une clé manque ? → ROLLBACK + erreur 409 (rien n'est enregistré)
      DELETE panier ; COMMIT
```

## 4. Sécurité

Contrôles appliqués, en reprenant les risques du **Top 10 OWASP** :

| Risque | Protection dans le portail | Où |
|---|---|---|
| Injection SQL | requêtes préparées PDO partout, `ATTR_EMULATE_PREPARES = false`, aucun SQL construit avec une saisie ; jokers `%` `_` neutralisés dans la recherche | `Repository/*` |
| XSS | toute donnée affichée passe par `e()` (`htmlspecialchars`) ; en-tête **Content-Security-Policy** `script-src 'self'` (aucun script en ligne) | `helpers.php`, `index.php` |
| CSRF | jeton aléatoire par session, vérifié sur **tous** les POST ; en-tête `X-CSRF-Token` pour les appels API par cookie ; cookie `SameSite=Lax` | `Core/Csrf.php` |
| Mots de passe | `password_hash` / `password_verify` (bcrypt), re-hachage automatique, politique de 10 caractères | `CompteService` |
| Force brute | blocage 15 min après 5 échecs par e-mail ou 20 par IP, journal `tentative_connexion` | `CompteService` |
| Énumération des comptes | même message d'erreur que le compte existe ou non, `password_verify` exécuté dans les deux cas (temps constant) | `CompteService::authentifier` |
| Vol de session | cookie `HttpOnly`, `Secure` en HTTPS, `use_strict_mode`, **nouvel identifiant** à la connexion | `Core/Session.php` |
| Contrôle d'accès (IDOR) | chaque requête filtre par l'utilisateur connecté (`WHERE id_utilisateur = ?`) ; 404 sur la ressource d'un autre ; rôles vérifiés côté serveur | services, repositories |
| Exposition de données | réponses API construites champ par champ (jamais d'empreinte, de clé non révélée ni de note interne) | `ApiController::formater*` |
| Jetons d'API | aléatoires (`random_bytes`), stockés en SHA-256, expirables et révocables | `JetonRepository` |
| Redirection ouverte | `rediriger()` n'accepte que des chemins internes | `Core/View.php` |
| Clickjacking, MIME | `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy` | `index.php` |
| Fuite d'informations | en production, message d'erreur neutre et détail dans le journal serveur uniquement | `index.php` |
| Moindre privilège | compte MariaDB applicatif limité à `SELECT, INSERT, UPDATE, DELETE` | `infra/srv-portail/install.sh` |
| Données bancaires | jamais reçues ni stockées (paiement simulé, prestataire PCI-DSS prévu) | — |

Chaque protection est vérifiée par un test (voir la [mission Tests](../3-tests/README.md) : cas SEC-01 à SEC-11).

## 5. Accessibilité et responsive

- Mise en page responsive (grilles CSS, menu repliable sous 860 px), testée en 375 px sans défilement horizontal.
- Lien d'évitement « Aller au contenu », `aria-current` sur la page active, `aria-invalid` et message lié sur les champs en erreur, focus visible.
- Le site fonctionne **sans JavaScript** (le JS améliore seulement : filtres automatiques, révélation de clé sans rechargement, copie).

## 6. Versionnage avec Git

- Dépôt GitHub du projet, branche `main` ; un commit par mission, messages explicites.
- `.gitignore` : `portail/config/config.php` et `portail/tests/config.*.php` (mots de passe), journaux, caches.
- Les secrets ne sont jamais versionnés : `config.example.php` sert de modèle, le script d'installation génère un mot de passe BDD aléatoire.

## 7. Lancer le portail en local

Prérequis : PHP 8.2+ (extension `pdo_mysql`), MariaDB 10.11+.

```bash
mariadb -u root -p < database/01_creation.sql
mariadb -u root -p < database/02_jeu_de_donnees.sql
cp portail/config/config.example.php portail/config/config.php   # puis renseigner la connexion BDD
php -S 127.0.0.1:8080 -t portail/public portail/public/index.php
```

Ouvrir http://127.0.0.1:8080. Les comptes de démonstration sont listés dans [`database/README.md`](../../../database/README.md).

Déploiement sur le serveur : voir la [mission Infrastructure](../4-infrastructure/README.md).

## 8. Limites et évolutions prévues

| Limite actuelle | Évolution |
|---|---|
| Paiement simulé | intégration d'un prestataire (Stripe, PayPal) en mode test puis réel |
| Pas de réinitialisation de mot de passe par e-mail | lien à usage unique envoyé par e-mail (serveur SMTP du studio) |
| Pas de back-office catalogue | pages admin pour produits, promotions, import de clés |
| Synchronisation GLPI | créer automatiquement le ticket GLPI via son API REST |
| Polices Google Fonts | auto-hébergement des polices (RGPD) |
