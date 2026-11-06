# Livrable 1 : quelles données stocker pour le portail joueurs ?

Mission Conception Backend, Monde 2 (compétence C12).

## Ma démarche

Je suis reparti du cahier des charges (partie « Portail joueurs » et partie « Gestion des tickets ») et de la maquette Figma qu'on a validée. Pour chaque action qu'un joueur ou qu'un membre du support peut faire sur le portail, je me suis demandé quelles informations sont créées, lues, modifiées ou supprimées. Je n'ai gardé une donnée que si elle sert vraiment à une fonctionnalité, pour respecter le principe de minimisation du RGPD.

Voici ce que ça donne par fonctionnalité :

- créer et gérer un compte joueur : pseudo, e-mail, mot de passe, profil, accord pour la newsletter ;
- se connecter de façon sécurisée : le mot de passe haché, un journal des tentatives de connexion et des jetons pour l'API ;
- voir ses jeux et ses clés : les commandes, le détail de chaque commande, les clés d'activation et les plateformes ;
- utiliser la boutique : les produits (jeux et DLC), les genres, les plateformes où chaque produit est disponible, les promotions et le panier ;
- lire les actualités : les articles et leur catégorie ;
- signaler un bug et suivre son traitement : les tickets, leur statut, leur priorité, les messages échangés et l'historique.

## Les données retenues

Dans les tableaux, « O » veut dire obligatoire et « F » facultatif. Pour la sensibilité, j'ai mis « forte » quand la donnée doit être protégée en priorité, « perso » pour une donnée personnelle classique, et « non » sinon.

### Comptes et sécurité

| Donnée | À quoi elle sert | Type | O/F | Sensibilité |
|---|---|---|---|---|
| Pseudo | nom affiché sur le portail et dans les tickets | texte (30), unique | O | perso |
| E-mail | identifiant de connexion, contact pour un ticket | texte (255), unique | O | perso |
| Mot de passe | connexion | texte (255), stocké haché avec bcrypt | O | forte |
| Profil (joueur, support, admin) | savoir à quelles pages et quelles routes de l'API la personne a accès | texte court | O | non |
| Newsletter | n'envoyer les actualités qu'aux joueurs qui l'ont accepté | booléen, faux par défaut | O | perso |
| Compte actif | pouvoir désactiver un compte sans perdre son historique | booléen | O | non |
| Date d'inscription, dernière connexion | suivi des comptes, purge des comptes inactifs | date et heure | O / F | perso |
| Tentatives de connexion (e-mail, IP, date, réussie ou non) | bloquer les attaques par force brute | texte, date, booléen | O | perso (IP gardée 30 jours) |
| Jeton d'API (empreinte, nom, date d'expiration, révoqué) | permettre à une appli comme le launcher d'utiliser l'API | texte (64), date, booléen | O | forte |

### Boutique

| Donnée | À quoi elle sert | Type | O/F | Sensibilité |
|---|---|---|---|---|
| Type (jeu ou DLC) | filtre « Jeux / DLC » de la boutique | liste | O | non |
| Titre, slug, accroche, description | fiche produit, recherche, adresse de la page | texte | O (description F) | non |
| Prix de base | calcul du prix affiché | décimal (6,2) | O | non |
| Date de sortie | tri par nouveautés, badge « Précommande » | date | F (si pas encore annoncée) | non |
| Genre | sous-titre des cartes (« Course arcade »...) | texte | F (vide pour un DLC) | non |
| Jeu parent | relier un DLC à son jeu | lien vers un produit | F | non |
| Plateformes | choisir PC, PS5 ou Switch au moment de l'achat | texte | O, au moins une | non |
| Promotion (pourcentage, début, fin) | badge « -30 % » et prix remisé | entier, dates | F | non |
| En vente ou retiré | retirer un produit sans casser les anciennes commandes | booléen | O | non |
| Panier (produit, plateforme, date) | préparer la commande | liens, date | F | non |

### Achats et clés

| Donnée | À quoi elle sert | Type | O/F | Sensibilité |
|---|---|---|---|---|
| Commande (référence, date, statut) | historique d'achat, remboursements | texte, date, liste | O | perso |
| Ligne de commande (produit, plateforme, prix payé) | garder le prix réellement payé même si le prix change après | liens, décimal | O | perso |
| Code de la clé d'activation | activer le jeu sur sa plateforme | texte (29), unique | O | forte, car une clé a une valeur marchande |
| Dates d'import, d'attribution et d'affichage de la clé | gérer le stock et savoir si le joueur a vu sa clé (plus de remboursement après) | date et heure | O / F / F | non |

Le portail ne stocke aucune donnée bancaire. Le paiement doit passer par un prestataire certifié PCI-DSS, donc les numéros de carte ne passent jamais par nos serveurs. Ne pas avoir la donnée reste la meilleure façon de la protéger.

### Support et actualités

| Donnée | À quoi elle sert | Type | O/F | Sensibilité |
|---|---|---|---|---|
| Ticket : référence, titre, description | comprendre et reproduire le bug | texte | O | perso (texte libre) |
| Jeu, plateforme, version, fréquence du bug | aider la QA à qualifier le bug | liens, texte, liste | F | non |
| Statut (de « nouveau » à « clos ») | suivi du ticket selon ITIL | liste | O | non |
| Priorité (de basse à critique) | traiter d'abord les bugs les plus graves | liste | F, fixée par le support | non |
| Technicien | savoir qui s'occupe du ticket | lien vers un compte | F | non |
| Messages (contenu, date, note interne ou pas) | échanges entre le joueur et le support | texte, date, booléen | O | perso |
| Historique (statut, auteur, date, commentaire) | garder une trace de chaque étape | liens, date, texte | O | non |
| Actualité (titre, chapô, contenu, catégorie, date, publiée) | page d'accueil et page Actualités | texte, liste, date, booléen | O | non |

## Ce que j'ai choisi de ne pas stocker

- Nom, prénom, adresse, téléphone : on vend uniquement des clés, il n'y a rien à livrer.
- Date de naissance : le contrôle de l'âge (PEGI) est déjà fait par Steam, le PlayStation Store et l'eShop.
- Numéro de carte bancaire : c'est le prestataire de paiement qui s'en occupe.
- Captures d'écran dans les tickets : je les ai mises de côté pour l'instant, parce qu'une capture peut montrer des infos perso. On pourra les ajouter plus tard avec un floutage.

## Comment je protège les données sensibles

- Les mots de passe sont hachés avec `password_hash()` (bcrypt) et vérifiés avec `password_verify()`. Il faut au moins 10 caractères avec une lettre, un chiffre et un caractère spécial.
- Pour les jetons d'API, je ne garde que leur empreinte SHA-256. Le jeton en clair n'est montré qu'une seule fois, au moment de sa création.
- Les clés d'activation restent masquées (par exemple `•••••-•••••-K79ZN`) tant que le joueur ne demande pas à les voir, et un joueur ne peut afficher que ses propres clés.
- Les adresses IP du journal de connexion sont supprimées au bout de 30 jours.
- L'application se connecte à la base avec un compte qui a seulement les droits `SELECT`, `INSERT`, `UPDATE` et `DELETE`, et la base n'est accessible que depuis le serveur lui-même.
- Une page « Confidentialité » explique au joueur quelles données on garde, pourquoi et combien de temps.

Cette liste m'a servi de base pour construire le modèle de données du [livrable 2](L2_representation_bdd.md).
