# Livrable 3 · Normalisation

> Objectif : une information n'est écrite qu'à un seul endroit, et chaque champ dépend « de la clé, de toute la clé et de rien d'autre que la clé ».

## 1. Point de départ : la première version « tableur »

Avant la modélisation, le studio suivait ses ventes et ses bugs dans deux tableurs. Transposés tels quels en tables, ils donnent ceci :

**VENTES** (une ligne par commande)

| n° commande | pseudo | email | jeux achetés | plateformes | prix | clés | date |
|---|---|---|---|---|---|---|---|
| 1 | LeaPixel | lea.martin@exemple.test | Neon Drift, Livrées rétro | PC, PC | 19.99, 3.99 | HWGQ4-…, 9WP3S-… | 02/03/2026 |
| 4 | LeaPixel | lea.martin@exemple.test | Circuit Nocturne, Abyssal Tides | PC, PC | 9.99, 17.49 | 3V835-…, S9795-… | 21/10/2026 |
| 2 | DriftKing | tom.durand@exemple.test | Neon Drift | PS5 | 19.99 | VBZZ7-… | 11/04/2026 |

**BUGS**

| n° ticket | pseudo | email | jeu | genre du jeu | statut | technicien | email technicien | messages |
|---|---|---|---|---|---|---|---|---|
| BUG-0001 | DriftKing | tom.durand@… | Neon Drift | Course arcade | Résolu | karim | karim@… | « Merci… » / « Super… » / « La 1.4… » |

## 2. Anomalies constatées

| Anomalie | Exemple dans la version tableur |
|---|---|
| **Redondance** | L'e-mail de LeaPixel est recopié sur chacune de ses commandes ; le genre « Course arcade » sur chaque bug de Neon Drift. |
| **Mise à jour** | Si LeaPixel change d'e-mail, il faut modifier toutes ses lignes ; une ligne oubliée crée une incohérence. |
| **Insertion** | Impossible d'enregistrer un jeu qui n'a encore jamais été vendu, ni un joueur sans commande. |
| **Suppression** | Supprimer la seule commande de DriftKing fait disparaître son compte. Supprimer un bug fait perdre l'e-mail du technicien. |

## 3. Application des formes normales

### 1FN : un champ = une seule valeur

- `jeux achetés`, `plateformes`, `prix`, `clés` contiennent des **listes** → une ligne par article : table **`ligne_commande`** (`id_commande`, `id_produit`, `id_plateforme`, `prix_unitaire`), et les clés vont dans **`cle_activation`** reliée à la ligne.
- `messages` contient plusieurs messages → table **`message_ticket`**, un message par ligne.
- Les plateformes d'un produit (« PC, PS5, Switch ») → table associative **`produit_plateforme`**.

### 2FN : tout le champ dépend de toute la clé

Après la 1FN, une ligne de vente aurait pour clé (`n° commande`, `jeu`, `plateforme`). Or :

- `pseudo`, `email`, `date` dépendent du **n° de commande seul** → ils restent dans **`commande`** (et `pseudo`/`email` partent dans `utilisateur`, voir 3FN) ;
- le titre, le type, le prix catalogue dépendent du **jeu seul** → table **`produit`** ;
- seul `prix_unitaire` (prix réellement payé) dépend de la clé complète → il reste dans `ligne_commande`.

J'ai ensuite remplacé la clé composée par un identifiant simple `id_ligne`, car la clé d'activation doit pouvoir référencer **une** ligne précise.

### 3FN : aucun champ non clé ne dépend d'un autre champ non clé

| Dépendance transitive trouvée | Correction |
|---|---|
| `commande.email` dépend de `commande.pseudo` (et non du n° de commande) | Table **`utilisateur`** ; `commande` ne garde que `id_utilisateur`. |
| `ticket.genre du jeu` dépend de `ticket.jeu` | Le genre est porté par **`produit.id_genre`** → table **`genre`**. |
| `ticket.email technicien` dépend de `ticket.technicien` | Le technicien est un **`utilisateur`** (rôle support) : `ticket.id_technicien`. |
| Le libellé du statut (« En attente du joueur ») et son ordre dépendent du code du statut | Table **`statut_ticket`** (code, libellé, ordre, est_final), idem **`priorite`**. |
| Le libellé du rôle dépend de son code | Table **`role`**. |

## 4. Choix justifiés (redondances apparentes mais volontaires)

| Champ | Pourquoi ce n'est pas une redondance |
|---|---|
| `ligne_commande.prix_unitaire` | Ce n'est **pas** une copie de `produit.prix` : c'est le prix payé ce jour-là (promotion comprise). Quand le prix catalogue change, l'historique doit rester exact (requête R23 du livrable 6). |
| Pas de colonne `commande.montant_total` | Le total se **calcule** à partir des lignes (`SUM(prix_unitaire)`) ; le stocker créerait un risque d'incohérence. |
| Pas de colonne `cle_activation.statut` | Le statut se **déduit** : `id_ligne` vide = disponible, renseigné = attribuée, `date_revelation` renseignée = révélée. |
| Pas de table « bibliothèque » | Les jeux possédés se déduisent des commandes payées et de leurs clés : une table en plus serait une copie à maintenir. |
| Vue `v_produit_prix` | Le prix remisé du jour est calculé à un seul endroit (vue), utilisé par la boutique, le panier et la commande. |
| `cle_activation` → `produit_plateforme` (clé composée) | Garantit qu'une clé correspond à une offre qui existe vraiment (pas de clé PS5 pour un jeu PC). |

## 5. Résultat

Le modèle obtenu (20 tables) est en **3FN**. Il est représenté dans le [livrable 2](L2_representation_bdd.md) et implémenté dans le [livrable 4](L4_script_creation.md).

| Avant (2 tableurs) | Après (modèle normalisé) |
|---|---|
| VENTES | `utilisateur`, `commande`, `ligne_commande`, `cle_activation`, `produit`, `plateforme`, `produit_plateforme` |
| BUGS | `ticket`, `message_ticket`, `historique_ticket`, `statut_ticket`, `priorite`, `genre` |
