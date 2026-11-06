# Livrable 2 · Représentation de la base de données

> Démarche Merise : MCD (conceptuel) → MLD (logique, ci-dessous en diagramme) → MPD (script SQL du [livrable 4](L4_script_creation.md)).

## 1. Modèle conceptuel (MCD)

Entités et associations, avec leurs cardinalités (lecture : « un utilisateur passe 0 ou plusieurs commandes ; une commande est passée par 1 et 1 seul utilisateur »).

| Association | Entité A | Card. A | Entité B | Card. B |
|---|---|---|---|---|
| AVOIR (profil) | UTILISATEUR | 1,1 | ROLE | 0,n |
| PASSER | UTILISATEUR | 0,n | COMMANDE | 1,1 |
| CONTENIR | COMMANDE | 1,n | LIGNE_COMMANDE | 1,1 |
| ÊTRE DISPONIBLE SUR | PRODUIT | 1,n | PLATEFORME | 0,n |
| CONCERNER (ligne) | LIGNE_COMMANDE | 1,1 | PRODUIT × PLATEFORME | 0,n |
| ATTRIBUER | LIGNE_COMMANDE | 1,1 | CLE_ACTIVATION | 0,1 |
| CLASSER | PRODUIT | 0,1 | GENRE | 0,n |
| ÉTENDRE (DLC) | PRODUIT (DLC) | 0,1 | PRODUIT (jeu) | 0,n |
| BÉNÉFICIER | PRODUIT | 0,n | PROMOTION | 1,1 |
| METTRE AU PANIER | UTILISATEUR | 0,n | PRODUIT × PLATEFORME | 0,n |
| SIGNALER | UTILISATEUR | 0,n | TICKET | 1,1 |
| TRAITER | UTILISATEUR (support) | 0,n | TICKET | 0,1 |
| ÊTRE DANS L'ÉTAT | TICKET | 1,1 | STATUT_TICKET | 0,n |
| AVOIR LA PRIORITÉ | TICKET | 0,1 | PRIORITE | 0,n |
| ÉCRIRE | UTILISATEUR | 0,n | MESSAGE_TICKET | 1,1 |
| TRACER | TICKET | 1,n | HISTORIQUE_TICKET | 1,1 |
| RÉDIGER | UTILISATEUR | 0,n | ACTUALITE | 1,1 |
| POSSÉDER (jeton) | UTILISATEUR | 0,n | JETON_API | 1,1 |

## 2. Passage au MLD

Règles appliquées (cours « Concevoir le backend », §2.5) :

- chaque entité devient une **table**, son identifiant devient la **clé primaire** (entier auto-incrémenté) ;
- association **1 à plusieurs** → **clé étrangère** du côté de la cardinalité maximale 1 (ex. `commande.id_utilisateur`) ;
- association **plusieurs à plusieurs** → **table associative** dont la clé primaire réunit les deux clés : `produit_plateforme (id_produit, id_plateforme)` ;
- l'association réflexive ÉTENDRE devient la clé étrangère `produit.id_jeu_parent` qui pointe vers `produit`.

Point de conception important : une clé, une ligne de commande et une ligne de panier ne pointent pas vers le produit et la plateforme **séparément**, mais vers le couple `produit_plateforme` (clé étrangère composée). La base refuse donc d'elle-même une clé « Abyssal Tides sur PS5 » si le jeu n'existe que sur PC.

## 3. Schéma de la base (MLD)

20 tables et 1 vue, réparties en deux schémas pour rester lisibles. **PK** = clé primaire, **FK** = clé étrangère, **UK** = valeur unique. Les traits relient chaque clé étrangère à la clé primaire référencée, avec la cardinalité à chaque extrémité (patte d'oie = plusieurs, barre = un, rond = zéro).

### Comptes, catalogue, commandes et clés

![MLD boutique](img/mld_boutique.png)

### Support (tickets ITIL), actualités et sécurité

![MLD support](img/mld_support.png)

> Les mêmes diagrammes existent au format texte dans [`database/schema.dbml`](../../../database/schema.dbml) (à coller sur [dbdiagram.io](https://dbdiagram.io)) et en Mermaid dans [`database/schema.md`](../../../database/schema.md) : GitHub l'affiche directement.

## 4. Rôle de chaque table

| Table | Contenu | Fonctionnalité servie |
|---|---|---|
| `role` | joueur, support, admin | gestion des droits |
| `utilisateur` | comptes joueurs et membres du studio | compte joueur, authentification |
| `jeton_api` | jetons d'accès à l'API | API REST |
| `tentative_connexion` | journal des connexions | anti force brute |
| `genre`, `plateforme` | listes de référence | boutique |
| `produit` | jeux et DLC | boutique, bibliothèque |
| `produit_plateforme` | offre : un produit sur une plateforme | choix de la plateforme |
| `promotion` | remises datées | badge « -30 % » |
| `panier_ligne` | panier en cours | boutique |
| `commande`, `ligne_commande` | achats et prix payés | historique, bibliothèque |
| `cle_activation` | stock de clés et attribution | « Ma bibliothèque » |
| `categorie_actualite`, `actualite` | actualités du studio | page d'accueil, actualités |
| `statut_ticket`, `priorite` | cycle ITIL | support |
| `ticket`, `message_ticket`, `historique_ticket` | signalements, échanges, traçabilité | signaler et suivre un bug |
| vue `v_produit_prix` | prix du jour, promotion incluse | calcul unique du prix affiché |
