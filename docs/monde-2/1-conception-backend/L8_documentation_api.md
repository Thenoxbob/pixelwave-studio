# Livrable 8 : documentation de l'API REST

Version 1 de l'API du portail joueurs. Adresse de base sur le serveur : `https://portail.pixelwave.lan/api/v1`, et en local : `http://127.0.0.1:8080/api/v1`.

Une collection Postman est prête dans [`api/pixelwave_api.postman_collection.json`](../../../api/pixelwave_api.postman_collection.json), avec son environnement [`pixelwave_local`](../../../api/pixelwave_local.postman_environment.json). Les exemples de réponses de cette page viennent de vrais appels à l'API sur le jeu de données de test.

## Sommaire

- [Pour commencer](#pour-commencer)
- [Règles générales](#règles-générales)
- [Authentification](#authentification), [Santé](#santé), [Catalogue](#catalogue), [Actualités](#actualités)
- [Espace joueur](#espace-joueur) : profil, bibliothèque, clés, panier, commandes
- [Tickets](#tickets)
- [Liste des erreurs](#liste-des-erreurs)

## Pour commencer

```bash
# 1. récupérer un jeton
curl -X POST http://127.0.0.1:8080/api/v1/auth/jetons \
     -H "Content-Type: application/json" \
     -d '{"email":"hugo.petit@exemple.test","mot_de_passe":"<mot de passe>","libelle":"Launcher PC"}'

# 2. l'utiliser
curl http://127.0.0.1:8080/api/v1/moi/bibliotheque -H "Authorization: Bearer pw_..."
```

## Règles générales

- Tout est en JSON UTF-8. Quand on envoie un corps, il faut l'en-tête `Content-Type: application/json`.
- Les routes marquées « connexion requise » demandent l'en-tête `Authorization: Bearer <jeton>`.
- Les listes ont toujours la forme `{ "donnees": [...], "total": n }`.
- Les dates sont au format `AAAA-MM-JJ` ou `AAAA-MM-JJ HH:MM:SS`, à l'heure de Paris.
- Les prix sont en euros TTC.
- Les codes de plateforme sont `PC`, `PS5`, `SWITCH` et `XBOX`.
- Les erreurs ont la forme `{ "erreur": { "code", "message", "details" } }` (`details` seulement quand c'est utile).

Si on appelle l'API depuis le navigateur avec le cookie de session du portail, il faut ajouter l'en-tête `X-CSRF-Token` sur les `POST`, `PATCH` et `DELETE`. Sa valeur est dans la balise `<meta name="csrf-token">` de la page.

---

## Authentification

### `POST /auth/jetons` : obtenir un jeton

Accessible sans connexion. On envoie les identifiants d'un compte actif et on reçoit un jeton valable 90 jours.

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| `email` | texte | oui | e-mail du compte |
| `mot_de_passe` | texte | oui | mot de passe |
| `libelle` | texte (50 max) | non | nom du jeton, visible dans « Mon compte » (par défaut `API`) |

Réponse 201 :

```json
{
    "jeton": "pw_daed6ebc32468e9f8efb7c66aa355fd1d6d92d698e921455",
    "type": "Bearer",
    "expiration": "2027-02-04 14:51:47",
    "utilisateur": {
        "id": 7, "pseudo": "AbyssDiver", "email": "hugo.petit@exemple.test",
        "role": "joueur", "newsletter": false, "date_inscription": "2026-10-04 22:10:00"
    }
}
```

Attention, le jeton n'est renvoyé qu'une seule fois : seule son empreinte est gardée en base.

Erreurs possibles : `400 donnees_invalides` (champ manquant), `400 json_invalide`, `401 identifiants_invalides` (même message que le compte existe ou pas), `429 trop_de_tentatives` (5 échecs en 15 minutes).

### `DELETE /auth/jetons/courant` : révoquer le jeton utilisé (connexion requise)

Réponse 204 sans contenu. Le jeton ne marche plus tout de suite après (`401 jeton_invalide`).

---

## Santé

### `GET /sante` : état du service

Accessible sans connexion. C'est cette route que l'outil de supervision appelle.

Réponse 200 :

```json
{ "statut": "ok", "base_de_donnees": "ok", "date": "2026-11-06T14:51:47+01:00" }
```

Si la base ne répond pas, on reçoit un 503 avec `"statut": "degrade"` et `"base_de_donnees": "injoignable"`.

---

## Catalogue

### `GET /plateformes`

Accessible sans connexion. Réponse 200 : `{ "donnees": [ { "id_plateforme": 1, "code": "PC", "nom": "PC" }, ... ] }`

### `GET /produits` : le catalogue

Accessible sans connexion. Renvoie seulement les produits en vente.

| Paramètre | Valeurs | Rôle |
|---|---|---|
| `type` | `jeu`, `dlc`, `promo` | filtre ; `promo` donne les produits en réduction aujourd'hui |
| `plateforme` | `PC`, `PS5`, `SWITCH`, `XBOX` | produits disponibles sur cette plateforme |
| `q` | texte (1 à 100 caractères) | recherche dans le titre |
| `tri` | `nouveautes` (par défaut), `prix_croissant`, `prix_decroissant`, `titre` | ordre du résultat |

Exemple avec `GET /produits?type=promo`, réponse 200 :

```json
{
    "donnees": [
        {
            "id": 7, "type": "dlc", "titre": "Tiny Forge : Atelier d'hiver", "slug": "tiny-forge-atelier-hiver",
            "accroche": "La saison froide arrive dans ta forge.", "genre": null, "jeu_parent": "Tiny Forge",
            "date_sortie": "2025-12-01",
            "prix": { "base": 7.99, "remise_pct": 30, "final": 5.59 },
            "plateformes": ["PC", "SWITCH"]
        },
        {
            "id": 3, "type": "jeu", "titre": "Abyssal Tides", "slug": "abyssal-tides",
            "accroche": "Explore un océan qui change à chaque plongée.", "genre": "Exploration", "jeu_parent": null,
            "date_sortie": "2025-09-18",
            "prix": { "base": 24.99, "remise_pct": 30, "final": 17.49 },
            "plateformes": ["PC"]
        }
    ],
    "total": 2
}
```

Avec une valeur interdite, par exemple `GET /produits?type=xyz`, réponse 400 :

```json
{ "erreur": { "code": "donnees_invalides", "message": "Certaines données sont invalides.",
              "details": { "type": "Valeur non autorisée." } } }
```

### `GET /produits/{id}` : fiche d'un produit

Accessible sans connexion. En plus des champs de la liste, on a la `description`, les `offres` (disponible ou non sur chaque plateforme, sans donner le stock exact) et les `dlc` si c'est un jeu.

Exemple avec `GET /produits/6`, réponse 200 :

```json
{
    "id": 6, "type": "dlc", "titre": "Neon Drift : Circuit Nocturne", "slug": "neon-drift-circuit-nocturne",
    "accroche": "Douze circuits de nuit et une météo qui change pendant la course.",
    "genre": null, "jeu_parent": "Neon Drift", "date_sortie": "2026-10-20",
    "prix": { "base": 9.99, "remise_pct": 0, "final": 9.99 },
    "plateformes": ["PC", "PS5"],
    "description": "Extension pour Neon Drift : douze nouveaux circuits nocturnes, pluie dynamique et deux nouvelles voitures.",
    "offres": [
        { "plateforme": "PC", "nom": "PC", "disponible": true },
        { "plateforme": "PS5", "nom": "PlayStation 5", "disponible": true }
    ],
    "dlc": []
}
```

Erreur possible : `404 produit_introuvable`, si le produit n'existe pas ou n'est plus en vente.

---

## Actualités

### `GET /actualites?limite=20`

Accessible sans connexion. Seulement les actualités publiées, de la plus récente à la plus ancienne. `limite` doit être entre 1 et 50, sinon on reçoit une erreur 400.

Exemple avec `GET /actualites?limite=1`, réponse 200 :

```json
{
    "donnees": [
        { "id": 4, "titre": "Starlit Courier est disponible", "categorie": "Studio",
          "chapo": "Notre nouveau jeu narratif sort aujourd'hui sur PC, PS5 et Switch.",
          "jeu": "Starlit Courier", "date_publication": "2026-10-23 08:00:00" }
    ],
    "total": 1
}
```

### `GET /actualites/{id}`

Accessible sans connexion. Même chose qu'au-dessus, avec le `contenu` complet. On reçoit `404 actualite_introuvable` pour un brouillon ou un identifiant qui n'existe pas.

---

## Espace joueur

Toutes ces routes demandent une connexion. Elles travaillent toujours sur le compte à qui appartient le jeton.

### `GET /moi` : profil

Réponse 200 :

```json
{ "id": 4, "pseudo": "LeaPixel", "email": "lea.martin@exemple.test", "role": "joueur",
  "newsletter": true, "date_inscription": "2026-03-01 20:14:00" }
```

Sans jeton, réponse 401 : `{ "erreur": { "code": "non_authentifie", "message": "Connexion requise." } }`

Avec un jeton faux, expiré ou révoqué, réponse 401 : `{ "erreur": { "code": "jeton_invalide", "message": "Jeton invalide, expiré ou révoqué." } }`

### `GET /moi/bibliotheque` : jeux possédés

Renvoie les produits des commandes payées (une commande remboursée n'apparaît plus). Si le joueur n'a pas encore affiché une clé, elle est masquée.

Réponse 200 (extrait) :

```json
{
    "donnees": [
        { "id_cle": 6, "produit": { "id": 3, "titre": "Abyssal Tides", "type": "jeu" }, "plateforme": "PC",
          "commande": "CMD-2026-000004", "date_achat": "2026-10-21 19:02:00",
          "revelee": true, "cle": "S9795-CUW7G-6S6M5" },
        { "id_cle": 5, "produit": { "id": 6, "titre": "Neon Drift : Circuit Nocturne", "type": "dlc" }, "plateforme": "PC",
          "commande": "CMD-2026-000004", "date_achat": "2026-10-21 19:02:00",
          "revelee": false, "cle": "•••••-•••••-K79ZN" }
    ],
    "total": 4
}
```

### `POST /moi/cles/{id}/revelation` : afficher une clé

Pas de corps à envoyer. La route enregistre la date d'affichage (après ça, la clé n'est plus remboursable) et renvoie le code complet. Si on la rappelle, on récupère le même code et la date ne change pas.

Réponse 200 :

```json
{ "id_cle": 5, "produit": "Neon Drift : Circuit Nocturne", "plateforme": "PC",
  "cle": "3V835-NUTAC-K79ZN", "date_revelation": "2026-11-06 14:51:47" }
```

Erreur possible : `404 cle_introuvable` si la clé n'existe pas ou si elle appartient à un autre joueur.

### `GET /moi/panier`

Réponse 200 :

```json
{ "lignes": [ { "id_ligne": 1, "produit": { "id": 4, "titre": "Starlit Courier", "type": "jeu" },
                "plateforme": "PC", "prix": 29.99 } ],
  "total": 29.99 }
```

### `POST /moi/panier` : ajouter au panier

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| `id_produit` | entier positif | oui | produit en vente |
| `plateforme` | code | oui | une plateforme où ce produit est vendu |

```json
{ "id_produit": 5, "plateforme": "PC" }
```

Réponse 201 : `{ "id_ligne": 4 }`

Erreurs possibles :

- `400 plateforme_invalide` : le produit n'existe pas sur cette plateforme ;
- `404 produit_introuvable` : produit inconnu ou retiré de la vente ;
- `409 stock_epuise` : il n'y a plus de clé pour cette plateforme ;
- `409 deja_possede` : le joueur a déjà ce produit sur cette plateforme ;
- `409 deja_dans_panier` : le produit est déjà dans le panier.

```json
{ "erreur": { "code": "stock_epuise", "message": "Plus aucune clé disponible pour cette plateforme. Reviens bientôt !" } }
```

### `DELETE /moi/panier/{id_ligne}`

Réponse 204 sans contenu, ou `404 ligne_introuvable` si la ligne n'est pas dans le panier du joueur.

### `POST /moi/commandes` : valider le panier

Pas de corps à envoyer. Le panier devient une commande payée (pour l'instant le paiement est simulé), au prix du jour, et chaque article reçoit une clé. C'est tout ou rien : s'il manque une seule clé, aucune commande n'est créée.

Réponse 201 :

```json
{
    "reference": "CMD-2026-000010", "date": "2026-11-06 14:51:47", "statut": "payee", "montant_total": 29.99,
    "lignes": [ { "produit": "Starlit Courier", "plateforme": "PC", "prix": 29.99, "id_cle": 26 } ]
}
```

Erreurs possibles : `400 panier_vide`, `409 stock_epuise` (la liste des produits qui manquent est dans `details.articles`).

### `GET /moi/commandes`

Réponse 200 :

```json
{ "donnees": [ { "reference": "CMD-2026-000004", "date": "2026-10-21 19:02:00", "statut": "payee",
                 "nb_articles": 2, "montant_total": 27.48 } ],
  "total": 1 }
```

---

## Tickets

Un ticket passe par les statuts `nouveau`, `en_cours`, `attente_joueur` (il peut revenir à `en_cours`), `resolu` puis `clos`. Les priorités sont `basse`, `moyenne`, `haute` et `critique`, et seul le support peut les fixer.

### `GET /tickets` (connexion requise)

Un joueur reçoit ses propres tickets, du plus récemment modifié au plus ancien. Le support reçoit toute la file : d'abord les tickets pas encore qualifiés, puis par priorité. Le support peut filtrer avec `?statut=nouveau` (un code de statut inconnu donne une erreur 400).

Réponse 200 : `{ "donnees": [ <ticket>, ... ], "total": n }`. Le format d'un ticket est donné juste en dessous.

### `POST /tickets` : signaler un bug (connexion requise)

| Champ | Type | Obligatoire | Règle |
|---|---|---|---|
| `titre` | texte | oui | entre 5 et 150 caractères |
| `description` | texte | oui | entre 20 et 5 000 caractères |
| `id_produit` | entier | non | un produit en vente |
| `plateforme` | code | non | `PC`, `PS5`, `SWITCH` ou `XBOX` |
| `version_jeu` | texte | non | lettres, chiffres, points et tirets, par exemple `1.4.0` |
| `frequence` | liste | non | `toujours`, `parfois` ou `une_fois` |

```json
{ "id_produit": 6, "plateforme": "PC", "version_jeu": "1.4.0", "frequence": "parfois",
  "titre": "Freeze dans le tunnel du Quai 9",
  "description": "L'image se fige environ trois secondes à l'entrée du tunnel, en mode nuit." }
```

Réponse 201 :

```json
{
    "reference": "BUG-2026-0007", "titre": "Freeze dans le tunnel du Quai 9",
    "description": "L'image se fige environ trois secondes à l'entrée du tunnel, en mode nuit.",
    "statut": { "code": "nouveau", "libelle": "Nouveau" }, "priorite": null,
    "jeu": "Neon Drift : Circuit Nocturne", "plateforme": "PC", "version_jeu": "1.4.0", "frequence": "parfois",
    "auteur": "LeaPixel", "technicien": null,
    "date_creation": "2026-11-06 14:51:47", "date_maj": "2026-11-06 14:51:47"
}
```

L'équipe QA reçoit un message dans son salon Discord (webhook). Si Discord ne répond pas, le ticket est quand même créé.

Si des champs sont incorrects, réponse 400 avec le détail champ par champ :

```json
{ "erreur": { "code": "donnees_invalides", "message": "Certaines données sont invalides.",
              "details": { "titre": "Entre 5 et 150 caractères.", "description": "Ce champ est obligatoire." } } }
```

### `GET /tickets/{reference}` (connexion requise)

Réservé à l'auteur du ticket et au support. En plus du ticket, on reçoit les `messages` et l'`historique`. Les notes internes ne sont envoyées qu'au support, avec un champ `interne`.

Exemple avec `GET /tickets/BUG-2026-0002` en tant que joueur, réponse 200 (extrait) :

```json
{
    "reference": "BUG-2026-0002", "titre": "La pluie ne s'affiche pas sur le circuit Quai 9",
    "statut": { "code": "en_cours", "libelle": "En cours" },
    "priorite": { "code": "moyenne", "libelle": "Moyenne" }, "technicien": "julie.qa",
    "messages": [
        { "id": 5, "auteur": "julie.qa", "support": true,
          "contenu": "Bien reçu. Peux-tu préciser si tu joues en qualité graphique « Haute » ou « Ultra » ?",
          "date": "2026-10-23 09:15:00" },
        { "id": 6, "auteur": "LeaPixel", "support": false,
          "contenu": "Je joue en Ultra, avec la synchronisation verticale activée.", "date": "2026-10-23 20:40:00" }
    ],
    "historique": [
        { "date_changement": "2026-10-22 21:30:00", "statut": "Nouveau", "auteur": "LeaPixel", "commentaire": "Création du ticket" },
        { "date_changement": "2026-10-23 09:15:00", "statut": "En cours", "auteur": "julie.qa", "commentaire": "Pris en charge" }
    ]
}
```

Erreur possible : `404 ticket_introuvable`, si le ticket n'existe pas ou appartient à un autre joueur.

### `POST /tickets/{reference}/messages` (connexion requise)

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| `contenu` | texte (2 à 5 000 caractères) | oui | le message |
| `interne` | booléen | non | seulement pour le support : note que le joueur ne voit pas |

Réponse 201 : `{ "id": 10 }`. Si le ticket attendait une réponse du joueur (`attente_joueur`) et que le joueur répond, il repasse tout seul en `en_cours`.

Erreurs possibles : `400 donnees_invalides`, `404 ticket_introuvable`, `409 ticket_clos`.

### `PATCH /tickets/{reference}` (support uniquement)

Sert à qualifier un ticket, l'affecter ou changer son statut. Il faut envoyer au moins un des champs `statut`, `priorite` ou `id_technicien`.

| Champ | Type | Description |
|---|---|---|
| `statut` | code de statut | chaque changement est ajouté à l'historique |
| `priorite` | code de priorité | |
| `id_technicien` | entier ou `null` | un membre du support ou un admin (`null` pour retirer l'affectation) |
| `commentaire` | texte (255 max) | enregistré dans l'historique |

```json
{ "statut": "en_cours", "priorite": "haute", "id_technicien": 2, "commentaire": "Reproduit sur PC" }
```

Réponse 200 : le ticket mis à jour.

Erreurs possibles : `403 acces_refuse` si c'est un joueur, `400 donnees_invalides` si le statut n'existe pas ou si le technicien ne fait pas partie du support.

```json
{ "erreur": { "code": "acces_refuse", "message": "Tu n'as pas les droits nécessaires pour cette action." } }
```

---

## Liste des erreurs

| HTTP | Code | Signification | Que faire |
|---|---|---|---|
| 400 | `json_invalide` | le JSON envoyé est illisible | vérifier la syntaxe |
| 400 | `donnees_invalides` | un ou plusieurs champs sont refusés | regarder `details` |
| 400 | `plateforme_invalide` | le produit n'est pas vendu sur cette plateforme | regarder les `offres` du produit |
| 400 | `panier_vide` | on valide un panier vide | ajouter un produit |
| 401 | `non_authentifie` | pas de jeton | ajouter `Authorization: Bearer` |
| 401 | `jeton_invalide` | jeton faux, expiré ou révoqué | demander un nouveau jeton |
| 401 | `identifiants_invalides` | mauvais e-mail ou mot de passe | |
| 403 | `acces_refuse` | profil pas autorisé | |
| 403 | `csrf_invalide` | appel avec le cookie mais sans `X-CSRF-Token` | ajouter l'en-tête |
| 404 | `introuvable` et les `*_introuvable` | ressource absente ou qui appartient à un autre | |
| 405 | `methode_non_autorisee` | méthode pas prévue (voir l'en-tête `Allow`) | |
| 409 | `stock_epuise`, `deja_possede`, `deja_dans_panier`, `ticket_clos` | conflit avec l'état actuel | lire le `message` |
| 429 | `trop_de_tentatives` | trop de connexions ratées | attendre 15 minutes |
| 500 | `erreur_interne` | erreur du serveur | contacter le support (le détail est dans le journal du serveur) |

## Tester l'API

1. Importer dans Postman la collection et l'environnement du dossier [`api/`](../../../api/).
2. Remettre la base à zéro avec `01_creation.sql` puis `02_jeu_de_donnees.sql`.
3. Lancer la collection avec le Collection Runner. Il y a 30 requêtes, et chacune vérifie son code de retour et une partie de la réponse, y compris pour les cas d'erreur.

Il existe aussi un script qui fait une recette équivalente sans Postman (43 cas, sécurité comprise) : `php portail/tests/api/recette_api.php`. Il est expliqué dans la [mission Tests](../3-tests/README.md).
