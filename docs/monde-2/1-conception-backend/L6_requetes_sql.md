# Livrable 6 : requêtes SQL

Les requêtes sont dans [`database/03_requetes.sql`](../../../database/03_requetes.sql). Il y en a 26, chacune avec un commentaire qui explique à quoi elle sert. Je les ai toutes testées sur le jeu de données du livrable 5.

Pour la partie qui modifie les données (INSERT, UPDATE, DELETE), j'ai tout mis entre `START TRANSACTION` et `ROLLBACK`. Comme ça on peut relancer le script sans abîmer les données de test.

## La liste

| N° | À quoi elle sert | Ce qu'elle utilise |
|---|---|---|
| R1 | boutique : produits en vente, les plus récents d'abord, avec le prix du jour | `SELECT`, jointure sur la vue, `WHERE`, `ORDER BY` |
| R2 | filtre « Promotions » | `JOIN`, `BETWEEN`, tri sur deux colonnes |
| R3 | recherche par nom | `LIKE`, `CONCAT` |
| R4 | accueil : les 3 dernières actualités | `JOIN`, `ORDER BY`, `LIMIT` |
| R5 | connexion : retrouver un compte actif | `JOIN`, `WHERE` (le mot de passe est vérifié ensuite en PHP) |
| R6 | fiche produit : stock de clés par plateforme | `LEFT JOIN`, `COUNT`, `GROUP BY` |
| R7 | « Ma bibliothèque » : jeux et clés d'un joueur | 5 jointures, `CASE` pour masquer les clés pas encore affichées |
| R8 | les DLC avec leur jeu | jointure de la table produit avec elle-même |
| R9 | file du support triée par priorité | `LEFT JOIN`, `COALESCE`, tri sur une expression |
| R10 | messages d'un ticket vus par le joueur | `JOIN`, filtre sur les notes internes |
| R11 | joueurs qui n'ont jamais rien acheté | `LEFT JOIN ... IS NULL` |
| R12 | montant de chaque commande | `COUNT`, `SUM`, `GROUP BY` |
| R13 | chiffre d'affaires par produit pour la direction | `SUM`, `AVG`, `ROUND`, `GROUP BY` |
| R14 | nombre de tickets par jeu et par statut pour la QA | `COUNT`, `GROUP BY` sur deux colonnes |
| R15 | alerte quand il reste moins de 2 clés | `LEFT JOIN`, `GROUP BY`, `HAVING` |
| R16 | adresses IP avec au moins 3 connexions ratées | `INTERVAL`, `GROUP BY`, `HAVING` |
| R17 | temps moyen pour résoudre un ticket | `TIMESTAMPDIFF`, `AVG` |
| R18 | inscription d'un joueur | `INSERT` |
| R19 | ajout au panier | `INSERT`, `LAST_INSERT_ID()` |
| R20 | validation d'une commande et attribution d'une clé | `INSERT ... SELECT`, `SELECT ... FOR UPDATE`, `UPDATE` |
| R21 | afficher sa clé (en vérifiant que c'est bien la sienne) | `UPDATE` avec jointure |
| R22 | le support prend un ticket et on garde la trace | `UPDATE`, `INSERT ... SELECT` |
| R23 | changer un prix sans modifier les anciennes commandes | `UPDATE` puis vérification |
| R24 | enlever un article du panier | `DELETE` |
| R25 | RGPD : supprimer les connexions de plus de 30 jours | `DELETE` avec `INTERVAL` |
| R26 | vérifier qu'on ne peut pas créer une clé pour une mauvaise plateforme | `INSERT` refusé (erreur 1452) |

## Quelques résultats

R7, la bibliothèque de LeaPixel. Elle n'a pas encore affiché la clé du DLC Circuit Nocturne, donc elle reste masquée :

![R7](img/L6_R7_bibliotheque.png)

R9, la file du support. Les tickets que personne n'a encore traités passent en premier, puis les autres par priorité :

![R9](img/L6_R9_file_support.png)

R13, le chiffre d'affaires par produit. La commande remboursée n'est pas comptée, et on voit qu'Abyssal Tides a été vendu au prix promo :

![R13](img/L6_R13_chiffre_affaires.png)

R15, l'alerte de stock. Deux offres n'ont plus de clé et une n'en a plus qu'une :

![R15](img/L6_R15_alerte_stock.png)

## Pourquoi j'utilise `SELECT ... FOR UPDATE` dans R20

Si deux joueurs valident leur panier exactement en même temps, ils pourraient tomber sur la même clé disponible. Avec `FOR UPDATE`, la ligne est verrouillée jusqu'à la fin de la transaction, donc le deuxième joueur récupère la clé suivante, ou une erreur s'il n'y en a plus. Il ne peut jamais avoir la même clé que le premier. Le portail fait exactement la même chose dans `BoutiqueService::validerCommande()`.
