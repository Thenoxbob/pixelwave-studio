# Résultats de la recette automatisée de l'API

Exécution du 06/11/2026 à 15:12 sur `http://127.0.0.1:8080` — **43/43 cas conformes**.

| Cas | Description | Statut attendu | Résultat | Détail |
|---|---|---|---|---|
| API-01 | Santé du service | 200 | ✅ Conforme | HTTP 200 |
| API-02 | Catalogue complet (produits en vente) | 200 | ✅ Conforme | HTTP 200 |
| API-03 | Filtre promotions | 200 | ✅ Conforme | HTTP 200 |
| API-04 | Filtre invalide refusé | 400 | ✅ Conforme | HTTP 400 |
| API-05 | Détail produit avec prix remisé et offres | 200 | ✅ Conforme | HTTP 200 |
| API-06 | Produit inexistant | 404 | ✅ Conforme | HTTP 404 |
| API-07 | Produit retiré de la vente invisible | 404 | ✅ Conforme | HTTP 404 |
| API-08 | Actualités publiées uniquement (brouillon exclu) | 200 | ✅ Conforme | HTTP 200 |
| API-09 | Route protégée sans jeton | 401 | ✅ Conforme | HTTP 401 |
| API-10 | Jeton inventé | 401 | ✅ Conforme | HTTP 401 |
| API-11 | Jeton expiré | 401 | ✅ Conforme | HTTP 401 |
| API-12 | Création de jeton, mauvais mot de passe | 401 | ✅ Conforme | HTTP 401 |
| API-13 | Création de jeton valide | 201 | ✅ Conforme | HTTP 201 |
| API-14 | Profil avec le nouveau jeton | 200 | ✅ Conforme | HTTP 200 |
| API-15 | JSON mal formé | 400 | ✅ Conforme | HTTP 400 |
| API-16 | Bibliothèque : clé non révélée masquée | 200 | ✅ Conforme | HTTP 200 |
| API-17 | Révéler la clé d'un autre joueur | 404 | ✅ Conforme | HTTP 404 |
| API-18 | Révéler sa propre clé | 200 | ✅ Conforme | HTTP 200 |
| API-19 | Ajout panier : rupture de stock | 409 | ✅ Conforme | HTTP 409 |
| API-20 | Ajout panier : plateforme non proposée | 400 | ✅ Conforme | HTTP 400 |
| API-21 | Ajout panier : produit déjà possédé | 409 | ✅ Conforme | HTTP 409 |
| API-22 | Ajout panier : précommande Pocket Galaxy | 201 | ✅ Conforme | HTTP 201 |
| API-23 | Validation de commande | 201 | ✅ Conforme | HTTP 201 |
| API-24 | Nouvelle validation avec panier vide | 400 | ✅ Conforme | HTTP 400 |
| API-25 | Signalement incomplet | 400 | ✅ Conforme | HTTP 400 |
| API-26 | Signalement valide | 201 | ✅ Conforme | HTTP 201 |
| API-27 | Lire le ticket d'un autre joueur | 404 | ✅ Conforme | HTTP 404 |
| API-28 | Un joueur ne peut pas qualifier un ticket | 403 | ✅ Conforme | HTTP 403 |
| API-29 | Le support qualifie et affecte le ticket | 200 | ✅ Conforme | HTTP 200 |
| API-30 | Le support voit la file complète | 200 | ✅ Conforme | HTTP 200 |
| API-31 | Le joueur répond sur son ticket | 201 | ✅ Conforme | HTTP 201 |
| API-32 | Méthode non autorisée | 405 | ✅ Conforme | HTTP 405 |
| SEC-01 | Connexion par formulaire (redirection) | 303 | ✅ Conforme | HTTP 303 |
| SEC-02 | Formulaire sans jeton CSRF refusé | 403 | ✅ Conforme | HTTP 403 |
| SEC-03 | Appel API par cookie sans en-tête CSRF refusé | 403 | ✅ Conforme | HTTP 403 |
| SEC-04 | Contenu saisi échappé à l'affichage (XSS) | 200 | ✅ Conforme | HTTP 200 |
| SEC-05 | En-têtes de sécurité présents | 200 | ✅ Conforme | HTTP 200 |
| SEC-06 | Cookie de session HttpOnly et SameSite | 200 | ✅ Conforme | HTTP 200 |
| SEC-07 | Page protégée sans connexion : redirection | 303 | ✅ Conforme | HTTP 303 |
| SEC-08 | Injection SQL dans la recherche sans effet | 200 | ✅ Conforme | HTTP 200 |
| SEC-09 | Blocage après 5 échecs de connexion | 429 | ✅ Conforme | HTTP 429 |
| SEC-10 | Révocation du jeton courant | 204 | ✅ Conforme | HTTP 204 |
| SEC-11 | Jeton révoqué inutilisable | 401 | ✅ Conforme | HTTP 401 |
