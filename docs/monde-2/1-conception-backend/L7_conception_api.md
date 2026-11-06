# Livrable 7 : conception de l'API REST

## 1. Pourquoi une API

Le portail est une application PHP qui renvoie des pages HTML. En plus de ça, j'ai prévu une API REST (`/api/v1`) pour que d'autres programmes du système d'information puissent utiliser les mêmes données sans aller directement dans la base :

- le JavaScript du portail, pour afficher une clé sans recharger la page ;
- le launcher PixelWave (application PC), pour montrer au joueur ses jeux et ses clés ;
- le bot Discord de la communauté, pour afficher les actualités ou ouvrir un ticket depuis Discord ;
- les outils du support et la supervision, pour la file des tickets et pour vérifier que le service marche.

La base de données est dans la DMZ et n'accepte que les connexions locales. L'API est donc la seule façon d'accéder aux données depuis le réseau.

## 2. Les règles que j'ai suivies

- Les noms de ressources sont au pluriel et sans verbe : `/produits`, `/tickets`, `/moi/commandes`.
- La méthode HTTP donne l'action : `GET` pour lire, `POST` pour créer, `PATCH` pour modifier une partie, `DELETE` pour supprimer. Je n'utilise pas `PUT`, parce qu'aucun client n'a besoin de remplacer une ressource en entier.
- Tout est en JSON (UTF-8). Les dates sont au format `AAAA-MM-JJ HH:MM:SS` et les prix sont des nombres décimaux.
- La version est dans l'URL (`/api/v1`). Si un jour je dois faire un changement incompatible, je créerai `/api/v2` sans casser le launcher déjà installé chez les joueurs.
- Les produits ont un identifiant numérique, mais les tickets utilisent leur référence (`BUG-2026-0007`), qui parle plus au joueur.
- `/moi/...` désigne toujours l'utilisateur connecté. Du coup, on ne peut pas voir la bibliothèque de quelqu'un d'autre juste en changeant un numéro dans l'URL.

## 3. Les routes

| Méthode | Route | Ce qu'elle fait | Qui peut l'utiliser |
|---|---|---|---|
| GET | `/api/v1/sante` | indique si le service et la base répondent | tout le monde |
| POST | `/api/v1/auth/jetons` | donne un jeton contre un e-mail et un mot de passe | tout le monde |
| DELETE | `/api/v1/auth/jetons/courant` | révoque le jeton utilisé | connecté |
| GET | `/api/v1/plateformes` | liste des plateformes | tout le monde |
| GET | `/api/v1/produits` | catalogue, avec les filtres `type`, `plateforme`, `q` et `tri` | tout le monde |
| GET | `/api/v1/produits/{id}` | fiche d'un produit avec ses plateformes et ses DLC | tout le monde |
| GET | `/api/v1/actualites` | actualités publiées (paramètre `limite`) | tout le monde |
| GET | `/api/v1/actualites/{id}` | une actualité | tout le monde |
| GET | `/api/v1/moi` | profil de l'utilisateur connecté | connecté |
| GET | `/api/v1/moi/bibliotheque` | ses jeux et ses clés (masquées si pas encore affichées) | connecté |
| POST | `/api/v1/moi/cles/{id}/revelation` | affiche une clé et enregistre la date | propriétaire de la clé |
| GET | `/api/v1/moi/panier` | contenu du panier | connecté |
| POST | `/api/v1/moi/panier` | ajoute un produit pour une plateforme | connecté |
| DELETE | `/api/v1/moi/panier/{id}` | enlève une ligne du panier | propriétaire de la ligne |
| GET | `/api/v1/moi/commandes` | historique des commandes | connecté |
| POST | `/api/v1/moi/commandes` | valide le panier et attribue les clés | connecté |
| GET | `/api/v1/tickets` | ses tickets (joueur) ou toute la file (support, filtre `statut`) | connecté |
| POST | `/api/v1/tickets` | signale un bug | connecté |
| GET | `/api/v1/tickets/{reference}` | détail, messages et historique d'un ticket | auteur du ticket ou support |
| POST | `/api/v1/tickets/{reference}/messages` | ajoute un message | auteur du ticket ou support |
| PATCH | `/api/v1/tickets/{reference}` | change le statut, la priorité ou le technicien | support et admin |

Pour afficher une clé, j'ai mis un `POST` et pas un `GET`. C'est parce que l'action modifie quelque chose : la date d'affichage est enregistrée, et après ça la clé n'est plus remboursable. Un `GET` doit pouvoir être relancé ou mis en cache sans rien changer.

## 4. Exemples de données échangées

Un produit :

```json
{ "id": 3, "type": "jeu", "titre": "Abyssal Tides", "slug": "abyssal-tides", "genre": "Exploration",
  "jeu_parent": null, "date_sortie": "2025-09-18",
  "prix": { "base": 24.99, "remise_pct": 30, "final": 17.49 }, "plateformes": ["PC"] }
```

Un ticket :

```json
{ "reference": "BUG-2026-0007", "titre": "...", "description": "...",
  "statut": { "code": "nouveau", "libelle": "Nouveau" }, "priorite": null,
  "jeu": "Neon Drift : Circuit Nocturne", "plateforme": "PC", "version_jeu": "1.4.0",
  "frequence": "parfois", "auteur": "LeaPixel", "technicien": null,
  "date_creation": "2026-11-06 14:51:47", "date_maj": "2026-11-06 14:51:47" }
```

J'ai fait attention à ce qu'aucune réponse ne contienne d'information sensible : pas d'empreinte de mot de passe ou de jeton, pas de code de clé que le joueur n'a pas encore affiché, et pas de note interne du support quand c'est un joueur qui demande.

## 5. Authentification et droits

Pour savoir qui appelle l'API :

1. le client envoie son e-mail et son mot de passe à `POST /api/v1/auth/jetons` ;
2. l'API renvoie un jeton aléatoire (`pw_` suivi de 48 caractères) valable 90 jours. En base, je ne garde que son empreinte SHA-256, donc même si la base fuit, les jetons ne sont pas récupérables ;
3. ensuite le client ajoute `Authorization: Bearer pw_...` à chaque requête ;
4. depuis la page « Mon compte », le joueur voit ses jetons et peut en révoquer un, par exemple s'il a perdu son téléphone.

Le JavaScript du portail, lui, utilise la session déjà ouverte (cookie `HttpOnly` et `SameSite=Lax`). Pour toutes les requêtes qui modifient des données, il doit en plus envoyer l'en-tête `X-CSRF-Token`, ce qui bloque les requêtes envoyées depuis un autre site.

Pour savoir ce que la personne a le droit de faire :

- un visiteur peut voir le catalogue, les actualités et l'état du service ;
- un joueur peut en plus accéder à son profil, sa bibliothèque, ses clés, son panier, ses commandes et ses tickets ;
- le support voit en plus toute la file des tickets et les notes internes, et peut qualifier et affecter un ticket ;
- l'admin a les mêmes droits que le support pour l'instant (la gestion du catalogue viendra plus tard).

Si un joueur demande le ticket ou la clé d'un autre joueur, il reçoit une erreur 404 et pas 403. Comme ça on ne lui confirme même pas que la ressource existe.

Contre la force brute : après 5 échecs sur le même e-mail ou 20 depuis la même adresse IP en 15 minutes, la création de jeton est bloquée (erreur 429).

## 6. Les erreurs

Toutes les erreurs ont le même format. Il y a un code qui ne change pas (pour qu'un programme puisse le tester) et un message lisible, sans détail technique :

```json
{ "erreur": { "code": "donnees_invalides", "message": "Certaines données sont invalides.",
              "details": { "titre": "Entre 5 et 150 caractères." } } }
```

| Statut | Quand | Codes possibles |
|---|---|---|
| 200, 201, 204 | succès, création, succès sans contenu | |
| 400 | requête mal formée ou données invalides | `json_invalide`, `donnees_invalides`, `plateforme_invalide`, `panier_vide` |
| 401 | pas connecté ou mauvais identifiants | `non_authentifie`, `jeton_invalide`, `identifiants_invalides` |
| 403 | connecté mais pas autorisé | `acces_refuse`, `csrf_invalide` |
| 404 | ressource introuvable, ou qui appartient à un autre | `introuvable`, `produit_introuvable`, `cle_introuvable`, `ticket_introuvable` |
| 405 | méthode pas prévue sur cette route (l'en-tête `Allow` donne les bonnes) | `methode_non_autorisee` |
| 409 | conflit avec l'état actuel | `stock_epuise`, `deja_possede`, `deja_dans_panier`, `ticket_clos` |
| 429 | trop de tentatives | `trop_de_tentatives` |
| 500, 503 | erreur du serveur, base injoignable | `erreur_interne` (le détail est seulement dans le journal du serveur) |

La documentation complète, avec un exemple de requête et de réponse pour chaque route, est dans le [livrable 8](L8_documentation_api.md).
