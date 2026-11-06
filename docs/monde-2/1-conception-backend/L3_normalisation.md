# Livrable 3 : normalisation

Le but est qu'une information ne soit écrite qu'à un seul endroit, et que chaque champ dépende de la clé, de toute la clé, et de rien d'autre.

## 1. Le point de départ

Avant le projet, le studio suivait ses ventes et ses bugs dans deux tableurs. Si on les recopie tels quels dans des tables, on obtient à peu près ça.

Table VENTES (une ligne par commande) :

| n° commande | pseudo | email | jeux achetés | plateformes | prix | clés | date |
|---|---|---|---|---|---|---|---|
| 1 | LeaPixel | lea.martin@exemple.test | Neon Drift, Livrées rétro | PC, PC | 19.99, 3.99 | HWGQ4-…, 9WP3S-… | 02/03/2026 |
| 4 | LeaPixel | lea.martin@exemple.test | Circuit Nocturne, Abyssal Tides | PC, PC | 9.99, 17.49 | 3V835-…, S9795-… | 21/10/2026 |
| 2 | DriftKing | tom.durand@exemple.test | Neon Drift | PS5 | 19.99 | VBZZ7-… | 11/04/2026 |

Table BUGS :

| n° ticket | pseudo | email | jeu | genre du jeu | statut | technicien | email technicien | messages |
|---|---|---|---|---|---|---|---|---|
| BUG-0001 | DriftKing | tom.durand@… | Neon Drift | Course arcade | Résolu | karim | karim@… | « Merci… » / « Super… » / « La 1.4… » |

## 2. Les problèmes de cette version

- **Redondance** : l'e-mail de LeaPixel est recopié sur chacune de ses commandes, et le genre « Course arcade » sur chaque bug de Neon Drift.
- **Mise à jour** : si LeaPixel change d'e-mail, il faut modifier toutes ses lignes. Si on en oublie une, les données ne sont plus cohérentes.
- **Insertion** : on ne peut pas enregistrer un jeu qui n'a jamais été vendu, ni un joueur qui n'a encore rien acheté.
- **Suppression** : si on supprime la seule commande de DriftKing, on perd son compte. Si on supprime un bug, on perd l'e-mail du technicien.

## 3. Application des formes normales

### 1FN : une seule valeur par champ

Les colonnes `jeux achetés`, `plateformes`, `prix` et `clés` contiennent des listes. Je les ai remplacées par une table `ligne_commande` (une ligne par article acheté, avec `id_commande`, `id_produit`, `id_plateforme` et `prix_unitaire`). Les clés vont dans une table `cle_activation` reliée à la ligne.

Pareil pour `messages`, qui contient plusieurs messages : chaque message devient une ligne de la table `message_ticket`. Et les plateformes d'un produit (« PC, PS5, Switch ») passent dans la table associative `produit_plateforme`.

### 2FN : chaque champ dépend de toute la clé

Après la 1FN, une ligne de vente aurait comme clé (n° commande, jeu, plateforme). Or :

- le pseudo, l'e-mail et la date ne dépendent que du n° de commande, donc ils restent au niveau de la commande (et le pseudo et l'e-mail partent ensuite dans `utilisateur`, voir la 3FN) ;
- le titre, le type et le prix du catalogue ne dépendent que du jeu, donc ils vont dans la table `produit` ;
- seul le prix réellement payé dépend de la clé complète, donc il reste dans `ligne_commande`.

J'ai ensuite remplacé cette clé à trois colonnes par un simple `id_ligne`, parce qu'une clé d'activation doit pouvoir pointer vers une ligne précise.

### 3FN : pas de dépendance entre champs qui ne sont pas des clés

| Dépendance trouvée | Ce que j'ai fait |
|---|---|
| l'e-mail dépend du pseudo, pas du n° de commande | création de la table `utilisateur` ; la commande ne garde que `id_utilisateur` |
| le genre dépend du jeu, pas du ticket | le genre est porté par `produit.id_genre`, avec une table `genre` |
| l'e-mail du technicien dépend du technicien | le technicien est un `utilisateur` avec le profil support (`ticket.id_technicien`) |
| le libellé d'un statut (« En attente du joueur ») et son ordre dépendent du code du statut | table `statut_ticket`, et pareil pour `priorite` |
| le libellé d'un profil dépend de son code | table `role` |

## 4. Les redondances que j'ai gardées exprès

Certains choix peuvent ressembler à des doublons, mais ils sont voulus :

- `ligne_commande.prix_unitaire` n'est pas une copie de `produit.prix`. C'est le prix payé ce jour-là, promo comprise. Si le prix du catalogue change, l'historique doit rester juste (c'est vérifié par la requête R23 du livrable 6).
- À l'inverse, je n'ai pas mis de colonne `montant_total` dans `commande` : le total se calcule avec `SUM(prix_unitaire)`, et le stocker risquerait de créer des incohérences.
- Je n'ai pas mis de colonne « statut » dans `cle_activation`. On le déduit : si `id_ligne` est vide, la clé est disponible ; s'il est rempli, elle est attribuée ; si `date_revelation` est remplie, le joueur l'a affichée.
- Il n'y a pas de table « bibliothèque » : les jeux d'un joueur se déduisent de ses commandes payées et de leurs clés.
- Le prix remisé est calculé à un seul endroit, dans la vue `v_produit_prix`, que la boutique, le panier et la commande utilisent tous.
- La clé étrangère sur deux colonnes de `cle_activation` vers `produit_plateforme` empêche d'avoir une clé pour une plateforme où le jeu n'existe pas.

## 5. Résultat

Le modèle final a 20 tables et il est en 3FN. Il est représenté dans le [livrable 2](L2_representation_bdd.md) et créé en SQL dans le [livrable 4](L4_script_creation.md). Au final, le tableur des ventes est devenu les tables `utilisateur`, `commande`, `ligne_commande`, `cle_activation`, `produit`, `plateforme` et `produit_plateforme`, et celui des bugs est devenu `ticket`, `message_ticket`, `historique_ticket`, `statut_ticket`, `priorite` et `genre`.
