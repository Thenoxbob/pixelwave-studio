# Livrable 7 · Conception de l'API REST

## 1. Pourquoi une API dans le portail

Le portail est une application PHP qui affiche des pages HTML. En plus, il expose une **API REST** (`/api/v1`) pour que d'autres composants du système d'information puissent utiliser les mêmes données sans accéder à la base :

| Client de l'API | Usage |
|---|---|
| Le portail lui-même (JavaScript) | révéler une clé sans recharger la page |
| Launcher PixelWave (application PC) | afficher la bibliothèque du joueur, ses clés |
| Bot Discord de la communauté | lister les actualités, ouvrir un ticket depuis Discord |
| Outils du support / supervision | file des tickets, contrôle de santé du service |

La base de données reste dans la DMZ, joignable en local uniquement : **l'API est le seul point d'entrée réseau vers les données**.

## 2. Principes retenus

- **Ressources au pluriel, sans verbe** : `/produits`, `/tickets`, `/moi/commandes`.
- **Méthode HTTP = action** : `GET` lire, `POST` créer, `PATCH` modifier partiellement, `DELETE` supprimer. `PUT` n'est pas utilisé : aucune ressource n'est remplacée entièrement par un client.
- **Format** : JSON en UTF-8, dates `AAAA-MM-JJ HH:MM:SS`, montants en nombre décimal.
- **Version dans l'URL** (`/api/v1`) : une évolution incompatible deviendra `/api/v2` sans casser le launcher déjà installé.
- **Identifiants** : numériques pour le catalogue, **référence lisible** pour les tickets (`BUG-2026-0007`), plus parlante pour le joueur.
- **Ressources « moi »** : `/moi/...` désigne toujours l'utilisateur authentifié ; impossible de demander la bibliothèque d'un autre joueur en changeant un identifiant dans l'URL.

## 3. Ressources et routes

| Méthode | Route | Rôle | Accès |
|---|---|---|---|
| GET | `/api/v1/sante` | état du service et de la base (supervision) | public |
| POST | `/api/v1/auth/jetons` | échanger e-mail + mot de passe contre un jeton | public |
| DELETE | `/api/v1/auth/jetons/courant` | révoquer le jeton utilisé | authentifié |
| GET | `/api/v1/plateformes` | liste des plateformes | public |
| GET | `/api/v1/produits` | catalogue (filtres `type`, `plateforme`, `q`, `tri`) | public |
| GET | `/api/v1/produits/{id}` | fiche produit, offres, DLC | public |
| GET | `/api/v1/actualites` | actualités publiées (`limite`) | public |
| GET | `/api/v1/actualites/{id}` | une actualité | public |
| GET | `/api/v1/moi` | profil de l'utilisateur connecté | authentifié |
| GET | `/api/v1/moi/bibliotheque` | jeux possédés et clés (masquées si non révélées) | authentifié |
| POST | `/api/v1/moi/cles/{id}/revelation` | révéler une clé (action tracée) | propriétaire de la clé |
| GET | `/api/v1/moi/panier` | contenu du panier | authentifié |
| POST | `/api/v1/moi/panier` | ajouter un produit pour une plateforme | authentifié |
| DELETE | `/api/v1/moi/panier/{id}` | retirer une ligne | propriétaire de la ligne |
| GET | `/api/v1/moi/commandes` | historique des commandes | authentifié |
| POST | `/api/v1/moi/commandes` | valider le panier, attribuer les clés | authentifié |
| GET | `/api/v1/tickets` | ses tickets (joueur) ou toute la file (support, filtre `statut`) | authentifié |
| POST | `/api/v1/tickets` | signaler un bug | authentifié |
| GET | `/api/v1/tickets/{reference}` | détail, messages, historique | auteur ou support |
| POST | `/api/v1/tickets/{reference}/messages` | ajouter un message | auteur ou support |
| PATCH | `/api/v1/tickets/{reference}` | statut, priorité, technicien | support, admin |

**Note sur la révélation de clé** : c'est un `POST` et non un `GET`, car l'action **modifie** l'état (date de révélation enregistrée, remboursement impossible ensuite). Un `GET` doit pouvoir être répété ou mis en cache sans effet.

## 4. Données échangées (exemples de ressources)

**Produit**

```json
{ "id": 3, "type": "jeu", "titre": "Abyssal Tides", "slug": "abyssal-tides", "genre": "Exploration",
  "jeu_parent": null, "date_sortie": "2025-09-18",
  "prix": { "base": 24.99, "remise_pct": 30, "final": 17.49 }, "plateformes": ["PC"] }
```

**Ticket**

```json
{ "reference": "BUG-2026-0007", "titre": "...", "description": "...",
  "statut": { "code": "nouveau", "libelle": "Nouveau" }, "priorite": null,
  "jeu": "Neon Drift : Circuit Nocturne", "plateforme": "PC", "version_jeu": "1.4.0",
  "frequence": "parfois", "auteur": "LeaPixel", "technicien": null,
  "date_creation": "2026-11-06 14:51:47", "date_maj": "2026-11-06 14:51:47" }
```

Aucune réponse ne contient de champ sensible : ni empreinte de mot de passe, ni empreinte de jeton, ni code de clé non révélé, ni note interne du support (pour un joueur).

## 5. Authentification et autorisations

**Authentification (qui appelle ?)**

1. Le client envoie `POST /api/v1/auth/jetons` avec e-mail et mot de passe.
2. L'API répond un **jeton aléatoire** de 48 caractères hexadécimaux (`pw_...`), valable 90 jours. La base ne garde que son empreinte SHA-256 : une fuite de la base ne donne pas les jetons.
3. Le client envoie ensuite `Authorization: Bearer pw_...` à chaque requête.
4. Le joueur peut voir et révoquer ses jetons depuis « Mon compte » (ex. téléphone perdu).

Le JavaScript du portail utilise la **session** déjà ouverte (cookie `HttpOnly`, `SameSite=Lax`) ; pour toute requête qui modifie des données, il doit alors envoyer l'en-tête `X-CSRF-Token` (protection contre les requêtes forgées depuis un autre site).

**Autorisations (qu'a-t-il le droit de faire ?)**

| Profil | Droits |
|---|---|
| Visiteur | catalogue, actualités, santé |
| Joueur | + son profil, sa bibliothèque, ses clés, son panier, ses commandes, ses tickets |
| Support | + toute la file des tickets, notes internes, qualification et affectation |
| Admin | comme le support (gestion du catalogue prévue dans une version suivante) |

Un joueur qui demande le ticket ou la clé d'un autre reçoit **404** (et non 403) : on ne confirme même pas que la ressource existe.

**Protection contre la force brute** : 5 échecs par e-mail ou 20 par adresse IP en 15 minutes bloquent la création de jeton (**429**).

## 6. Gestion des erreurs

Toutes les erreurs ont le même format, avec un **code stable** (exploitable par un programme) et un **message** lisible, sans détail technique :

```json
{ "erreur": { "code": "donnees_invalides", "message": "Certaines données sont invalides.",
              "details": { "titre": "Entre 5 et 150 caractères." } } }
```

| Statut | Quand | Codes d'erreur |
|---|---|---|
| 200 / 201 / 204 | succès / création / succès sans contenu | — |
| 400 | requête mal formée ou données invalides | `json_invalide`, `donnees_invalides`, `plateforme_invalide`, `panier_vide` |
| 401 | non authentifié ou identifiants faux | `non_authentifie`, `jeton_invalide`, `identifiants_invalides` |
| 403 | authentifié mais pas le droit | `acces_refuse`, `csrf_invalide` |
| 404 | ressource introuvable (ou appartenant à un autre) | `introuvable`, `produit_introuvable`, `cle_introuvable`, `ticket_introuvable` |
| 405 | méthode non prévue sur la route (en-tête `Allow`) | `methode_non_autorisee` |
| 409 | conflit avec l'état actuel | `stock_epuise`, `deja_possede`, `deja_dans_panier`, `ticket_clos` |
| 429 | trop de tentatives | `trop_de_tentatives` |
| 500 / 503 | erreur interne / base injoignable | `erreur_interne` (détail uniquement dans le journal du serveur) |

La documentation complète, avec un exemple de requête et de réponse pour chaque route, est le [livrable 8](L8_documentation_api.md).
