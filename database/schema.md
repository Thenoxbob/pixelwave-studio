# Schéma de la base (Mermaid)

GitHub affiche ces diagrammes directement. Version image : [livrable 2](../docs/monde-2/1-conception-backend/L2_representation_bdd.md).

## Comptes, catalogue, commandes et clés

```mermaid
erDiagram
    role ||--o{ utilisateur : "attribue"
    utilisateur ||--o{ commande : "passe"
    utilisateur ||--o{ panier_ligne : "remplit"
    utilisateur ||--o{ jeton_api : "possède"
    genre |o--o{ produit : "classe"
    produit |o--o{ produit : "étend (DLC)"
    produit ||--|{ produit_plateforme : "proposé sur"
    plateforme ||--o{ produit_plateforme : "accueille"
    produit ||--o{ promotion : "bénéficie de"
    commande ||--|{ ligne_commande : "contient"
    produit_plateforme ||--o{ ligne_commande : "vendue dans"
    produit_plateforme ||--o{ cle_activation : "en stock"
    produit_plateforme ||--o{ panier_ligne : "ajoutée à"
    ligne_commande ||--o| cle_activation : "reçoit"

    role {
        TINYINT id_role PK
        VARCHAR code UK
        VARCHAR libelle
    }
    utilisateur {
        INT id_utilisateur PK
        VARCHAR pseudo UK
        VARCHAR email UK
        VARCHAR mot_de_passe_hash
        TINYINT id_role FK
        BOOLEAN newsletter
        BOOLEAN actif
        DATETIME date_inscription
        DATETIME derniere_connexion
    }
    genre {
        SMALLINT id_genre PK
        VARCHAR libelle UK
    }
    plateforme {
        TINYINT id_plateforme PK
        VARCHAR code UK
        VARCHAR nom
    }
    produit {
        INT id_produit PK
        ENUM type
        VARCHAR titre
        VARCHAR slug UK
        VARCHAR accroche
        TEXT description
        DECIMAL prix
        DATE date_sortie
        SMALLINT id_genre FK
        INT id_jeu_parent FK
        VARCHAR visuel
        BOOLEAN actif
    }
    produit_plateforme {
        INT id_produit PK,FK
        TINYINT id_plateforme PK,FK
    }
    promotion {
        INT id_promotion PK
        INT id_produit FK
        TINYINT pourcentage
        DATE date_debut
        DATE date_fin
    }
    commande {
        INT id_commande PK
        VARCHAR reference UK
        INT id_utilisateur FK
        DATETIME date_commande
        ENUM statut
    }
    ligne_commande {
        INT id_ligne PK
        INT id_commande FK
        INT id_produit FK
        TINYINT id_plateforme FK
        DECIMAL prix_unitaire
    }
    cle_activation {
        INT id_cle PK
        VARCHAR code UK
        INT id_produit FK
        TINYINT id_plateforme FK
        INT id_ligne FK,UK
        DATETIME date_import
        DATETIME date_attribution
        DATETIME date_revelation
    }
    panier_ligne {
        INT id_panier_ligne PK
        INT id_utilisateur FK
        INT id_produit FK
        TINYINT id_plateforme FK
        DATETIME date_ajout
    }
    jeton_api {
        INT id_jeton PK
        INT id_utilisateur FK
        CHAR jeton_hash UK
        VARCHAR libelle
        DATETIME date_expiration
        BOOLEAN revoque
    }
```

## Support, actualités et sécurité

```mermaid
erDiagram
    utilisateur ||--o{ ticket : "signale"
    utilisateur |o--o{ ticket : "traite (technicien)"
    produit |o--o{ ticket : "concerne"
    plateforme |o--o{ ticket : "sur"
    statut_ticket ||--o{ ticket : "état"
    priorite |o--o{ ticket : "niveau"
    ticket ||--|{ message_ticket : "contient"
    utilisateur ||--o{ message_ticket : "écrit"
    ticket ||--|{ historique_ticket : "trace"
    statut_ticket ||--o{ historique_ticket : "nouveau statut"
    utilisateur ||--o{ historique_ticket : "auteur"
    categorie_actualite ||--o{ actualite : "classe"
    utilisateur ||--o{ actualite : "rédige"
    produit |o--o{ actualite : "évoque"

    utilisateur {
        INT id_utilisateur PK
        VARCHAR pseudo UK
        TINYINT id_role FK
    }
    produit {
        INT id_produit PK
        VARCHAR titre
    }
    plateforme {
        TINYINT id_plateforme PK
        VARCHAR code UK
    }
    statut_ticket {
        TINYINT id_statut PK
        VARCHAR code UK
        VARCHAR libelle
        TINYINT ordre
        BOOLEAN est_final
    }
    priorite {
        TINYINT id_priorite PK
        VARCHAR code UK
        VARCHAR libelle
        TINYINT niveau
    }
    ticket {
        INT id_ticket PK
        VARCHAR reference UK
        INT id_auteur FK
        INT id_produit FK
        TINYINT id_plateforme FK
        VARCHAR titre
        TEXT description
        VARCHAR version_jeu
        ENUM frequence
        TINYINT id_statut FK
        TINYINT id_priorite FK
        INT id_technicien FK
        DATETIME date_creation
        DATETIME date_maj
    }
    message_ticket {
        INT id_message PK
        INT id_ticket FK
        INT id_auteur FK
        TEXT contenu
        BOOLEAN interne
        DATETIME date_envoi
    }
    historique_ticket {
        INT id_historique PK
        INT id_ticket FK
        TINYINT id_statut FK
        INT id_auteur FK
        DATETIME date_changement
        VARCHAR commentaire
    }
    categorie_actualite {
        TINYINT id_categorie PK
        VARCHAR libelle UK
    }
    actualite {
        INT id_actualite PK
        VARCHAR titre
        VARCHAR slug UK
        VARCHAR chapo
        TEXT contenu
        TINYINT id_categorie FK
        INT id_auteur FK
        INT id_produit FK
        DATETIME date_publication
        BOOLEAN publiee
    }
    tentative_connexion {
        BIGINT id_tentative PK
        VARCHAR email
        VARCHAR adresse_ip
        DATETIME date_tentative
        BOOLEAN reussie
    }
```
