# Livrable 8 · Documentation de l'API REST du portail joueurs

> Version 1 · URL de base : `https://portail.pixelwave.lan/api/v1` (en local : `http://127.0.0.1:8080/api/v1`)
> Collection Postman prête à l'emploi : [`api/pixelwave_api.postman_collection.json`](../../../api/pixelwave_api.postman_collection.json) + environnement [`pixelwave_local`](../../../api/pixelwave_local.postman_environment.json).
> Les exemples de réponses ci-dessous ont été obtenus en appelant l'API sur le jeu de données de test.

## Sommaire

- [Démarrage rapide](#démarrage-rapide)
- [Conventions](#conventions)
- [Authentification](#authentification) · [Santé](#santé) · [Catalogue](#catalogue) · [Actualités](#actualités)
- [Espace joueur](#espace-joueur) : profil, bibliothèque, clés, panier, commandes
- [Tickets](#tickets)
- [Codes d'erreur](#codes-derreur)

## Démarrage rapide

```bash
# 1. obtenir un jeton
curl -X POST http://127.0.0.1:8080/api/v1/auth/jetons \
     -H "Content-Type: application/json" \
     -d '{"email":"hugo.petit@exemple.test","mot_de_passe":"<mot de passe>","libelle":"Launcher PC"}'

# 2. l'utiliser
curl http://127.0.0.1:8080/api/v1/moi/bibliotheque -H "Authorization: Bearer pw_..."
```

## Conventions

| Élément | Règle |
|---|---|
| Format | JSON UTF-8 (`Content-Type: application/json` pour les corps envoyés) |
| Authentification | `Authorization: Bearer <jeton>` sur les routes marquées 🔒 |
| Listes | `{ "donnees": [...], "total": n }` |
| Dates | `AAAA-MM-JJ` ou `AAAA-MM-JJ HH:MM:SS` (heure de Paris) |
| Prix | nombre décimal en euros TTC |
| Plateformes | codes `PC`, `PS5`, `SWITCH`, `XBOX` |
| Erreurs | `{ "erreur": { "code", "message", "details"? } }` |

Appel depuis le navigateur avec le cookie de session du portail : ajouter `X-CSRF-Token` (valeur de la balise `<meta name="csrf-token">`) sur les `POST`, `PATCH` et `DELETE`.

---

## Authentification

### `POST /auth/jetons` — obtenir un jeton

Public. Échange les identifiants d'un compte actif contre un jeton valable 90 jours.

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| `email` | texte | oui | adresse du compte |
| `mot_de_passe` | texte | oui | mot de passe |
| `libelle` | texte (≤ 50) | non | nom du jeton, visible dans « Mon compte » (défaut : `API`) |

**201 Created**

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

> Le jeton n'est renvoyé qu'**une seule fois**. Seule son empreinte est conservée.

**Erreurs** : `400 donnees_invalides` (champ manquant), `400 json_invalide`, `401 identifiants_invalides` (même message que le compte existe ou non), `429 trop_de_tentatives` (5 échecs sur 15 min).

### `DELETE /auth/jetons/courant` 🔒 — révoquer le jeton utilisé

**204 No Content**. Le jeton devient inutilisable immédiatement (`401 jeton_invalide` ensuite).

---

## Santé

### `GET /sante` — état du service

Public. Utilisé par l'outil de supervision.

**200 OK**

```json
{ "statut": "ok", "base_de_donnees": "ok", "date": "2026-11-06T14:51:47+01:00" }
```

**503** si la base ne répond pas : `{ "statut": "degrade", "base_de_donnees": "injoignable", ... }`

---

## Catalogue

### `GET /plateformes`

Public. **200** : `{ "donnees": [ { "id_plateforme": 1, "code": "PC", "nom": "PC" }, ... ] }`

### `GET /produits` — lister le catalogue

Public. Produits en vente uniquement.

| Paramètre | Valeurs | Description |
|---|---|---|
| `type` | `jeu`, `dlc`, `promo` | filtre ; `promo` = remisés aujourd'hui |
| `plateforme` | `PC`, `PS5`, `SWITCH`, `XBOX` | disponible sur cette plateforme |
| `q` | texte (1 à 100) | recherche dans le titre |
| `tri` | `nouveautes` (défaut), `prix_croissant`, `prix_decroissant`, `titre` | ordre |

`GET /produits?type=promo` → **200 OK**

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

**400** `GET /produits?type=xyz`

```json
{ "erreur": { "code": "donnees_invalides", "message": "Certaines données sont invalides.",
              "details": { "type": "Valeur non autorisée." } } }
```

### `GET /produits/{id}` — fiche produit

Public. Ajoute `description`, `offres` (disponibilité par plateforme, sans révéler le stock exact) et `dlc` (pour un jeu).

`GET /produits/6` → **200 OK**

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

**Erreur** : `404 produit_introuvable` (inexistant ou retiré de la vente).

---

## Actualités

### `GET /actualites?limite=20`

Public. Actualités **publiées** uniquement, de la plus récente à la plus ancienne. `limite` entre 1 et 50 (sinon **400**).

`GET /actualites?limite=1` → **200 OK**

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

Public. Même objet + `contenu`. `404 actualite_introuvable` pour un brouillon ou un identifiant inconnu.

---

## Espace joueur

Toutes ces routes exigent un jeton (🔒). Elles agissent **toujours** sur le compte du jeton.

### `GET /moi` 🔒 — profil

**200 OK**

```json
{ "id": 4, "pseudo": "LeaPixel", "email": "lea.martin@exemple.test", "role": "joueur",
  "newsletter": true, "date_inscription": "2026-03-01 20:14:00" }
```

**401** sans jeton : `{ "erreur": { "code": "non_authentifie", "message": "Connexion requise." } }`
**401** jeton faux, expiré ou révoqué : `{ "erreur": { "code": "jeton_invalide", "message": "Jeton invalide, expiré ou révoqué." } }`

### `GET /moi/bibliotheque` 🔒 — jeux possédés

Produits des commandes **payées** (une commande remboursée n'y figure plus). Une clé non révélée est masquée.

**200 OK** (extrait)

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

### `POST /moi/cles/{id}/revelation` 🔒 — révéler une clé

Aucun corps. Enregistre la date de révélation (une clé révélée n'est plus remboursable) et renvoie le code complet. Rappeler la route renvoie le même code sans changer la date.

**200 OK**

```json
{ "id_cle": 5, "produit": "Neon Drift : Circuit Nocturne", "plateforme": "PC",
  "cle": "3V835-NUTAC-K79ZN", "date_revelation": "2026-11-06 14:51:47" }
```

**Erreur** : `404 cle_introuvable` si la clé n'existe pas **ou appartient à un autre joueur**.

### `GET /moi/panier` 🔒

**200 OK**

```json
{ "lignes": [ { "id_ligne": 1, "produit": { "id": 4, "titre": "Starlit Courier", "type": "jeu" },
                "plateforme": "PC", "prix": 29.99 } ],
  "total": 29.99 }
```

### `POST /moi/panier` 🔒 — ajouter au panier

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| `id_produit` | entier > 0 | oui | produit en vente |
| `plateforme` | code | oui | plateforme proposée pour ce produit |

```json
{ "id_produit": 5, "plateforme": "PC" }
```

**201 Created** : `{ "id_ligne": 4 }`

| Erreur | Cas |
|---|---|
| `400 plateforme_invalide` | le produit n'existe pas sur cette plateforme |
| `404 produit_introuvable` | produit inconnu ou retiré |
| `409 stock_epuise` | plus aucune clé pour cette plateforme |
| `409 deja_possede` | le joueur possède déjà ce produit sur cette plateforme |
| `409 deja_dans_panier` | déjà dans le panier |

```json
{ "erreur": { "code": "stock_epuise", "message": "Plus aucune clé disponible pour cette plateforme. Reviens bientôt !" } }
```

### `DELETE /moi/panier/{id_ligne}` 🔒

**204 No Content** · `404 ligne_introuvable` si la ligne n'est pas dans **son** panier.

### `POST /moi/commandes` 🔒 — valider le panier

Aucun corps. Transforme le panier en commande payée (paiement simulé dans cette version), au **prix du jour**, et attribue une clé par article. Tout ou rien : si une seule clé manque, aucune commande n'est créée.

**201 Created**

```json
{
    "reference": "CMD-2026-000010", "date": "2026-11-06 14:51:47", "statut": "payee", "montant_total": 29.99,
    "lignes": [ { "produit": "Starlit Courier", "plateforme": "PC", "prix": 29.99, "id_cle": 26 } ]
}
```

**Erreurs** : `400 panier_vide`, `409 stock_epuise` (avec `details.articles` : liste des produits indisponibles).

### `GET /moi/commandes` 🔒

**200 OK**

```json
{ "donnees": [ { "reference": "CMD-2026-000004", "date": "2026-10-21 19:02:00", "statut": "payee",
                 "nb_articles": 2, "montant_total": 27.48 } ],
  "total": 1 }
```

---

## Tickets

Cycle de vie (ITIL) : `nouveau` → `en_cours` ⇄ `attente_joueur` → `resolu` → `clos`.
Priorités : `basse`, `moyenne`, `haute`, `critique` (fixées par le support uniquement).

### `GET /tickets` 🔒

- **Joueur** : ses propres tickets, du plus récemment mis à jour au plus ancien.
- **Support / admin** : toute la file, tickets non qualifiés en premier puis par priorité. Filtre `?statut=nouveau` (code de statut, sinon **400**).

**200 OK** : `{ "donnees": [ <ticket>, ... ], "total": n }` (voir l'objet ticket ci-dessous).

### `POST /tickets` 🔒 — signaler un bug

| Champ | Type | Obligatoire | Règle |
|---|---|---|---|
| `titre` | texte | oui | 5 à 150 caractères |
| `description` | texte | oui | 20 à 5 000 caractères |
| `id_produit` | entier | non | produit en vente |
| `plateforme` | code | non | `PC`, `PS5`, `SWITCH`, `XBOX` |
| `version_jeu` | texte | non | lettres, chiffres, points, tirets (ex. `1.4.0`) |
| `frequence` | liste | non | `toujours`, `parfois`, `une_fois` |

```json
{ "id_produit": 6, "plateforme": "PC", "version_jeu": "1.4.0", "frequence": "parfois",
  "titre": "Freeze dans le tunnel du Quai 9",
  "description": "L'image se fige environ trois secondes à l'entrée du tunnel, en mode nuit." }
```

**201 Created**

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

L'équipe QA est prévenue dans son salon Discord (webhook) ; une panne de Discord ne bloque pas la création.

**400** (détail par champ)

```json
{ "erreur": { "code": "donnees_invalides", "message": "Certaines données sont invalides.",
              "details": { "titre": "Entre 5 et 150 caractères.", "description": "Ce champ est obligatoire." } } }
```

### `GET /tickets/{reference}` 🔒

Auteur du ticket ou support. Ajoute `messages` et `historique`. Les notes internes ne sont renvoyées qu'au support (avec le champ `interne`).

`GET /tickets/BUG-2026-0002` (joueur) → **200 OK** (extrait)

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

**Erreur** : `404 ticket_introuvable` (inexistant ou ticket d'un autre joueur).

### `POST /tickets/{reference}/messages` 🔒

| Champ | Type | Obligatoire | Description |
|---|---|---|---|
| `contenu` | texte (2 à 5 000) | oui | message |
| `interne` | booléen | non | support uniquement : note invisible pour le joueur |

**201 Created** : `{ "id": 10 }`. Si le ticket était `attente_joueur` et que le joueur répond, il repasse automatiquement `en_cours`.
**Erreurs** : `400 donnees_invalides`, `404 ticket_introuvable`, `409 ticket_clos`.

### `PATCH /tickets/{reference}` 🔒 support

Qualification, affectation, changement de statut. Au moins un champ parmi `statut`, `priorite`, `id_technicien`.

| Champ | Type | Description |
|---|---|---|
| `statut` | code de statut | chaque changement est ajouté à l'historique |
| `priorite` | code de priorité | |
| `id_technicien` | entier ou `null` | membre du support ou admin (`null` = désaffecter) |
| `commentaire` | texte (≤ 255) | enregistré dans l'historique |

```json
{ "statut": "en_cours", "priorite": "haute", "id_technicien": 2, "commentaire": "Reproduit sur PC" }
```

**200 OK** : le ticket mis à jour.
**Erreurs** : `403 acces_refuse` (joueur), `400 donnees_invalides` (statut inconnu, technicien qui n'est pas du support...).

```json
{ "erreur": { "code": "acces_refuse", "message": "Tu n'as pas les droits nécessaires pour cette action." } }
```

---

## Codes d'erreur

| HTTP | Code | Signification | Que faire |
|---|---|---|---|
| 400 | `json_invalide` | corps JSON illisible | vérifier la syntaxe |
| 400 | `donnees_invalides` | un ou plusieurs champs refusés | lire `details` (champ → message) |
| 400 | `plateforme_invalide` | produit absent de cette plateforme | voir `offres` du produit |
| 400 | `panier_vide` | validation d'un panier vide | ajouter un produit |
| 401 | `non_authentifie` | pas de jeton | envoyer `Authorization: Bearer` |
| 401 | `jeton_invalide` | jeton faux, expiré ou révoqué | en demander un nouveau |
| 401 | `identifiants_invalides` | e-mail ou mot de passe faux | — |
| 403 | `acces_refuse` | profil insuffisant | — |
| 403 | `csrf_invalide` | appel par cookie sans `X-CSRF-Token` | ajouter l'en-tête |
| 404 | `introuvable` et `*_introuvable` | ressource absente ou appartenant à un autre | — |
| 405 | `methode_non_autorisee` | méthode non prévue (voir l'en-tête `Allow`) | — |
| 409 | `stock_epuise`, `deja_possede`, `deja_dans_panier`, `ticket_clos` | conflit avec l'état actuel | lire `message` |
| 429 | `trop_de_tentatives` | trop d'échecs de connexion | attendre 15 minutes |
| 500 | `erreur_interne` | erreur serveur | contacter le support (détail dans le journal serveur) |

## Tester l'API

1. Importer la collection et l'environnement Postman du dossier [`api/`](../../../api/).
2. Réinitialiser la base (`01_creation.sql` puis `02_jeu_de_donnees.sql`).
3. Lancer la collection avec le **Collection Runner** : 30 requêtes, chacune vérifie son code de statut et une partie du contenu, cas d'erreur compris.

Une recette automatisée équivalente (43 cas, dont la sécurité) se lance sans Postman : `php portail/tests/api/recette_api.php` (voir la [mission Tests](../3-tests/README.md)).
