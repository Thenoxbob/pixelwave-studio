# Livrable 4 : script de création des tables

Le script est dans [`database/01_creation.sql`](../../../database/01_creation.sql). Il est écrit pour MariaDB 10.11 ou plus (ça marche aussi avec MySQL 8), avec le moteur InnoDB et l'encodage `utf8mb4` pour les accents.

## Ordre de création

Une table qui est référencée doit exister avant la table qui la référence, sinon MariaDB refuse la clé étrangère. J'ai donc créé :

1. d'abord les tables de référence, qui n'ont pas de clé étrangère : `role`, `plateforme`, `genre`, `statut_ticket`, `priorite`, `categorie_actualite` ;
2. ensuite les tables principales : `utilisateur`, `produit`, `actualite`, `promotion`, `commande`, `ticket`, `jeton_api`, `tentative_connexion` ;
3. puis les tables associatives et de suivi : `produit_plateforme`, `ligne_commande`, `cle_activation`, `panier_ligne`, `message_ticket`, `historique_ticket` ;
4. et pour finir la vue `v_produit_prix`.

Le script commence par `DROP DATABASE IF EXISTS`, donc on peut le relancer autant de fois qu'on veut, il recrée toujours la même structure.

## Les contraintes

| Contrainte | Exemples dans ma base | Ce qu'elle empêche ou impose |
|---|---|---|
| `PRIMARY KEY` et `AUTO_INCREMENT` | toutes les tables | chaque ligne a un identifiant unique créé automatiquement |
| `NOT NULL` | `utilisateur.email`, `ticket.titre` | le champ est obligatoire |
| `UNIQUE` | e-mail, pseudo, code de clé, `cle_activation.id_ligne` | pas de doublon ; une ligne de commande ne peut recevoir qu'une seule clé |
| `DEFAULT` | `utilisateur.actif` vaut vrai, `commande.statut` vaut « payee » | valeur mise par défaut |
| `FOREIGN KEY` | 31 clés étrangères | impossible de pointer vers une ligne qui n'existe pas |
| clé étrangère sur deux colonnes | `cle_activation (id_produit, id_plateforme)` vers `produit_plateforme` | pas de clé pour une plateforme où le jeu n'est pas vendu |
| `ON DELETE CASCADE` | messages d'un ticket, panier d'un utilisateur | supprimer un ticket supprime aussi ses messages |
| `ON DELETE SET NULL` | `actualite.id_produit` | retirer un produit ne supprime pas l'actualité qui en parle |
| `CHECK` | `prix >= 0`, réduction entre 1 et 90 %, date de fin après la date de début | pas de valeurs absurdes |
| `CHECK` sur une règle métier | `ck_produit_dlc` | un DLC doit avoir un jeu parent, un jeu ne doit pas en avoir |
| `ENUM` | type de produit, statut de commande, fréquence d'un bug | seulement les valeurs prévues |

## Les types que j'ai choisis

- `INT UNSIGNED` pour les identifiants, et `TINYINT UNSIGNED` pour les petites listes (rôles, plateformes, statuts).
- `DECIMAL(6,2)` pour les prix. Je n'ai pas utilisé `FLOAT` parce qu'il fait des erreurs d'arrondi, ce qui n'est pas acceptable pour de l'argent.
- `VARCHAR(n)` pour les textes courts. J'ai prévu 255 caractères pour l'empreinte du mot de passe, au cas où on passe plus tard à argon2 qui donne des empreintes plus longues.
- `CHAR(64)` pour l'empreinte SHA-256 d'un jeton, qui fait toujours 64 caractères.
- `VARCHAR(45)` pour les adresses IP, pour que l'IPv6 rentre aussi.
- `TEXT` pour les descriptions et les messages.
- `DATE` et `DATETIME` pour les dates.
- `BOOLEAN` pour les champs oui/non (`actif`, `newsletter`, `interne`...).

## Exécuter le script

Dans phpMyAdmin : onglet Importer, choisir `01_creation.sql`, puis Exécuter. En ligne de commande :

```bash
mariadb -u root -p < database/01_creation.sql
```

## Captures

J'ai exécuté le script sur MariaDB 11.4 avec le client en ligne de commande. Les 20 tables et la vue sont bien créées :

![Tables créées](img/L4_creation_tables.png)

Ici la structure d'une table et la liste des 31 clés étrangères :

![Structure et contraintes](img/L4_structure_cle_activation.png)

Sur le serveur, on peut faire la même vérification dans phpMyAdmin (onglet Structure de la base `pixelwave`, puis Concepteur pour voir les liens entre les tables).
