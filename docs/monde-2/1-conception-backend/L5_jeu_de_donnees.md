# Livrable 5 · Jeu de données

**Fichier : [`database/02_jeu_de_donnees.sql`](../../../database/02_jeu_de_donnees.sql)** — à exécuter après `01_creation.sql`.

Toutes les données sont **fictives** : adresses en `.test` (domaine réservé, jamais attribué), clés générées au hasard, aucun nom ni coordonnée réels. Les mots de passe sont stockés sous forme d'empreintes bcrypt produites par `password_hash()`.

## Ordre d'insertion

Le même que celui de la création : tables de référence → utilisateurs → produits → offres (`produit_plateforme`) → promotions → actualités → commandes → lignes → clés → paniers → tickets → messages → historique → jetons → journal de connexion. Les identifiants sont fixés explicitement pour que les clés étrangères soient lisibles et que le script reconstruise la base **à l'identique**.

## Contenu

| Table | Lignes | Ce qui est représenté |
|---|---|---|
| `utilisateur` | 12 | 1 admin, 2 membres du support, 9 joueurs |
| `produit` | 9 | 5 jeux, 4 DLC (catalogue de la maquette Figma) |
| `produit_plateforme` | 17 | offres produit × plateforme |
| `promotion` | 4 | en cours, expirée, à venir |
| `commande` / `ligne_commande` | 9 / 13 | dont une commande remboursée et une multi-plateformes |
| `cle_activation` | 44 | 13 vendues (révélées ou non) + 31 en stock |
| `ticket` / `message_ticket` / `historique_ticket` | 6 / 9 / 14 | un ticket dans chaque statut |
| `actualite` | 5 | dont 1 brouillon |
| `jeton_api` | 3 | valide, expiré, révoqué |
| autres | — | listes de référence, paniers, journal de connexion |

## Cas particuliers couverts

| Cas | Donnée de test | Ce qu'il permet de vérifier |
|---|---|---|
| Joueur sans achat | `NoobSaibot` | bibliothèque vide, requête R11 |
| Compte désactivé | `OldTimer` (`actif = FALSE`) | connexion refusée |
| Plateforme sans produit | Xbox Series | filtre boutique vide |
| Genre sans jeu | Plateforme | jointure externe |
| Produit retiré de la vente | Tiny Forge : Pack de démarrage | invisible dans la boutique |
| Jeu pas encore sorti | Pocket Galaxy (10/12/2026) | badge « Précommande » |
| Rupture de stock | Starlit Courier sur Switch (0 clé) | achat impossible, erreur 409 |
| Dernière clé en stock | Livrées rétro sur PS5 (1 clé) | alerte stock (R15) |
| Promotion en cours / expirée / à venir | Abyssal Tides -30 %, Neon Drift -50 % (juillet), Starlit Courier -20 % (décembre) | seul le prix du jour est remisé |
| Prix payé ≠ prix actuel | Abyssal Tides acheté 17,49 € | historique conservé |
| Commande remboursée | CMD-2026-000008 | le jeu n'apparaît plus dans la bibliothèque |
| Même jeu sur deux plateformes | StarRunner : Starlit Courier PS5 + Switch | deux clés distinctes |
| Ticket non qualifié | BUG-2026-0004 (sans priorité ni technicien) | jointures externes, file du support |
| Note interne du support | BUG-2026-0001 | invisible pour le joueur |
| Actualité brouillon | « Pocket Galaxy : la date de sortie » | non publiée |
| Tentatives de force brute | 3 échecs de `192.0.2.200` | requête R16 |
| Champs facultatifs vides | ticket sans version ni fréquence, produit sans description | gestion des `NULL` |
| IPv6 | `2001:db8::42` | taille du champ `adresse_ip` |

## Exécution et vérification

![Jeu de données chargé](img/L5_jeu_de_donnees.png)

Les contraintes refusent bien les données incohérentes (clé pour une plateforme non proposée, remise de 95 %, e-mail en double, DLC sans jeu parent) :

![Contraintes d'intégrité](img/L5_integrite.png)
