# Livrable 2 : création de l'API à partir de la conception

## Ce qui est codé

Toutes les routes prévues dans la [conception de l'API](../1-conception-backend/L7_conception_api.md) sont développées dans [`ApiController.php`](../../../portail/src/Controller/ApiController.php), soit 21 routes. Elles sont déclarées dans [`public/index.php`](../../../portail/public/index.php) :

- public : santé, plateformes, catalogue, fiche produit, actualités ;
- authentification : créer un jeton, révoquer le jeton courant ;
- espace joueur : profil, bibliothèque, affichage d'une clé, panier (lire, ajouter, retirer), commandes (lister, valider) ;
- tickets : lister, créer, consulter, ajouter un message, qualifier (support).

Les traitements passent par les services et les repositories (voir le [livrable 3](L3_architecture_backend.md)) : les données sont lues et écrites avec des requêtes SQL préparées (PDO), sans ORM.

## Respect de la documentation

- Chaque route utilise la méthode prévue (`GET`, `POST`, `PATCH`, `DELETE`) et renvoie le code de statut documenté : 200, 201 à la création, 204 quand il n'y a pas de contenu.
- Les réponses sont en JSON, avec le format décrit dans la documentation (listes dans `donnees` avec un `total`).
- Les erreurs ont toutes la forme `{ "erreur": { "code", "message", "details" } }`, avec les codes prévus (400, 401, 403, 404, 405, 409, 429, 500).

## Contrôles intégrés

- **Validation** : la classe `Validator` vérifie chaque champ reçu (obligatoire, longueur, format, valeur autorisée) et renvoie le détail des erreurs champ par champ.
- **Gestion des erreurs** : toutes les erreurs passent par `HttpException`, et `index.php` les transforme en réponse JSON. Une erreur imprévue donne un 500 avec un message neutre, et le détail part dans le journal du serveur.
- **Authentification** : jeton `Bearer` ou cookie de session avec en-tête CSRF (voir le [livrable 7](L7_authentification.md)).
- **Autorisations** : `Auth::exiger()` vérifie le rôle, et chaque requête SQL filtre sur l'utilisateur connecté.

## Tests

J'ai testé chaque route avec deux outils :

- la **collection Postman** [`api/pixelwave_api.postman_collection.json`](../../../api/pixelwave_api.postman_collection.json) : 30 requêtes, cas d'erreur compris, toutes avec le bon code de statut ;
- un **script de recette** qui envoie de vraies requêtes HTTP ([`recette_api.php`](../../../portail/tests/api/recette_api.php)) : 32 cas sur l'API et 11 cas de sécurité, tous conformes.

Ces tests vérifient aussi la base de données : par exemple qu'une commande consomme bien une clé du stock, ou qu'un ticket créé apparaît avec son historique.

Exemple avec curl :

```bash
curl -s http://127.0.0.1:8080/api/v1/produits?type=promo
curl -s http://127.0.0.1:8080/api/v1/moi/bibliotheque -H "Authorization: Bearer pw_demo_LeaPixel_2026_9f8e7d6c5b4a"
```

## Écarts avec la conception

L'implémentation suit la conception. J'ai seulement précisé deux points dans la documentation après avoir codé :

- la fiche produit indique si chaque plateforme est disponible, sans donner le nombre exact de clés en stock ;
- quand on affiche une clé déjà affichée, la date d'affichage ne change pas.

La documentation à jour est le [livrable 8 de la mission précédente](../1-conception-backend/L8_documentation_api.md).
