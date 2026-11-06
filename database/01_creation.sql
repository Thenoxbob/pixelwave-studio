-- =====================================================================
-- PixelWave Studio · Portail joueurs
-- Livrable 4 : script de création des tables (MPD)
-- SGBD cible : MariaDB 10.11+ / MySQL 8+   ·   moteur InnoDB, utf8mb4
--
-- Ordre de création :
--   1. tables de référence (sans clé étrangère)
--   2. tables qui référencent les précédentes
--   3. tables associatives et tables de suivi
-- Le script est rejouable : il supprime puis recrée la base.
-- =====================================================================

DROP DATABASE IF EXISTS pixelwave;
CREATE DATABASE pixelwave CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pixelwave;

-- ---------------------------------------------------------------------
-- 1. Tables de référence
-- ---------------------------------------------------------------------

-- Profils d'accès au portail (joueur, support, admin)
CREATE TABLE role (
    id_role     TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(20)      NOT NULL,
    libelle     VARCHAR(50)      NOT NULL,
    PRIMARY KEY (id_role),
    UNIQUE KEY uq_role_code (code)
) ENGINE=InnoDB;

-- Plateformes de jeu (PC, PS5, Switch...)
CREATE TABLE plateforme (
    id_plateforme TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code          VARCHAR(10)      NOT NULL,
    nom           VARCHAR(50)      NOT NULL,
    PRIMARY KEY (id_plateforme),
    UNIQUE KEY uq_plateforme_code (code)
) ENGINE=InnoDB;

-- Genres de jeux (Course arcade, Exploration...)
CREATE TABLE genre (
    id_genre  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle   VARCHAR(50)       NOT NULL,
    PRIMARY KEY (id_genre),
    UNIQUE KEY uq_genre_libelle (libelle)
) ENGINE=InnoDB;

-- Statuts d'un ticket, dans l'ordre du cycle de vie ITIL
CREATE TABLE statut_ticket (
    id_statut  TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       VARCHAR(20)      NOT NULL,
    libelle    VARCHAR(50)      NOT NULL,
    ordre      TINYINT UNSIGNED NOT NULL,
    est_final  BOOLEAN          NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id_statut),
    UNIQUE KEY uq_statut_code (code)
) ENGINE=InnoDB;

-- Niveaux de priorité (matrice impact x urgence)
CREATE TABLE priorite (
    id_priorite TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(20)      NOT NULL,
    libelle     VARCHAR(50)      NOT NULL,
    niveau      TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_priorite),
    UNIQUE KEY uq_priorite_code (code),
    CONSTRAINT ck_priorite_niveau CHECK (niveau BETWEEN 1 AND 4)
) ENGINE=InnoDB;

-- Catégories d'actualités (Mise à jour, Studio, Communauté)
CREATE TABLE categorie_actualite (
    id_categorie TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle      VARCHAR(50)      NOT NULL,
    PRIMARY KEY (id_categorie),
    UNIQUE KEY uq_categorie_libelle (libelle)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Tables principales
-- ---------------------------------------------------------------------

-- Comptes du portail : joueurs et membres du studio (support, admin)
CREATE TABLE utilisateur (
    id_utilisateur     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pseudo             VARCHAR(30)      NOT NULL,
    email              VARCHAR(255)     NOT NULL,
    mot_de_passe_hash  VARCHAR(255)     NOT NULL,          -- bcrypt / argon2, jamais en clair
    id_role            TINYINT UNSIGNED NOT NULL,
    newsletter         BOOLEAN          NOT NULL DEFAULT FALSE,  -- consentement explicite (RGPD)
    actif              BOOLEAN          NOT NULL DEFAULT TRUE,
    date_inscription   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME         NULL,
    PRIMARY KEY (id_utilisateur),
    UNIQUE KEY uq_utilisateur_pseudo (pseudo),
    UNIQUE KEY uq_utilisateur_email (email),
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (id_role) REFERENCES role (id_role)
) ENGINE=InnoDB;

-- Catalogue : jeux et contenus additionnels (DLC)
CREATE TABLE produit (
    id_produit    INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    type          ENUM('jeu','dlc')  NOT NULL,
    titre         VARCHAR(100)       NOT NULL,
    slug          VARCHAR(120)       NOT NULL,             -- identifiant lisible dans l'URL
    accroche      VARCHAR(255)       NOT NULL,
    description   TEXT               NULL,
    prix          DECIMAL(6,2)       NOT NULL,             -- prix de base TTC en euros
    date_sortie   DATE               NULL,                 -- NULL = date non annoncée
    id_genre      SMALLINT UNSIGNED  NULL,                 -- NULL pour un DLC (genre du jeu parent)
    id_jeu_parent INT UNSIGNED       NULL,                 -- renseigné uniquement pour un DLC
    visuel        VARCHAR(30)        NOT NULL DEFAULT 'defaut',  -- code de l'illustration pixel
    actif         BOOLEAN            NOT NULL DEFAULT TRUE,      -- FALSE = retiré de la vente
    PRIMARY KEY (id_produit),
    UNIQUE KEY uq_produit_slug (slug),
    CONSTRAINT fk_produit_genre  FOREIGN KEY (id_genre)      REFERENCES genre (id_genre),
    CONSTRAINT fk_produit_parent FOREIGN KEY (id_jeu_parent) REFERENCES produit (id_produit),
    CONSTRAINT ck_produit_prix   CHECK (prix >= 0),
    CONSTRAINT ck_produit_dlc    CHECK (
        (type = 'jeu' AND id_jeu_parent IS NULL) OR
        (type = 'dlc' AND id_jeu_parent IS NOT NULL)
    )
) ENGINE=InnoDB;

-- Actualités du studio affichées sur le portail
CREATE TABLE actualite (
    id_actualite     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    titre            VARCHAR(150)     NOT NULL,
    slug             VARCHAR(170)     NOT NULL,
    chapo            VARCHAR(300)     NOT NULL,
    contenu          TEXT             NOT NULL,
    id_categorie     TINYINT UNSIGNED NOT NULL,
    id_auteur        INT UNSIGNED     NOT NULL,
    id_produit       INT UNSIGNED     NULL,                -- jeu concerné, facultatif
    date_publication DATETIME         NULL,                -- NULL = brouillon
    publiee          BOOLEAN          NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id_actualite),
    UNIQUE KEY uq_actualite_slug (slug),
    CONSTRAINT fk_actualite_categorie FOREIGN KEY (id_categorie) REFERENCES categorie_actualite (id_categorie),
    CONSTRAINT fk_actualite_auteur    FOREIGN KEY (id_auteur)    REFERENCES utilisateur (id_utilisateur),
    CONSTRAINT fk_actualite_produit   FOREIGN KEY (id_produit)   REFERENCES produit (id_produit) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Promotions temporaires sur un produit
CREATE TABLE promotion (
    id_promotion INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_produit   INT UNSIGNED     NOT NULL,
    pourcentage  TINYINT UNSIGNED NOT NULL,
    date_debut   DATE             NOT NULL,
    date_fin     DATE             NOT NULL,
    PRIMARY KEY (id_promotion),
    KEY idx_promotion_dates (id_produit, date_debut, date_fin),
    CONSTRAINT fk_promotion_produit FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE,
    CONSTRAINT ck_promotion_pct     CHECK (pourcentage BETWEEN 1 AND 90),
    CONSTRAINT ck_promotion_dates   CHECK (date_fin >= date_debut)
) ENGINE=InnoDB;

-- Commandes passées sur la boutique (le montant se calcule à partir des lignes)
CREATE TABLE commande (
    id_commande    INT UNSIGNED                          NOT NULL AUTO_INCREMENT,
    reference      VARCHAR(20)                           NOT NULL,   -- ex. CMD-2026-000042
    id_utilisateur INT UNSIGNED                          NOT NULL,
    date_commande  DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut         ENUM('payee','remboursee','annulee')  NOT NULL DEFAULT 'payee',
    PRIMARY KEY (id_commande),
    UNIQUE KEY uq_commande_reference (reference),
    CONSTRAINT fk_commande_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
) ENGINE=InnoDB;

-- Tickets de support et signalements de bugs (cycle ITIL)
CREATE TABLE ticket (
    id_ticket     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    reference     VARCHAR(20)      NOT NULL,               -- ex. BUG-2026-0007
    id_auteur     INT UNSIGNED     NOT NULL,
    id_produit    INT UNSIGNED     NULL,                   -- jeu concerné (facultatif pour une question de compte)
    id_plateforme TINYINT UNSIGNED NULL,
    titre         VARCHAR(150)     NOT NULL,
    description   TEXT             NOT NULL,
    version_jeu   VARCHAR(20)      NULL,
    frequence     ENUM('toujours','parfois','une_fois') NULL,
    id_statut     TINYINT UNSIGNED NOT NULL,
    id_priorite   TINYINT UNSIGNED NULL,                   -- NULL tant que le support n'a pas qualifié
    id_technicien INT UNSIGNED     NULL,                   -- membre du support affecté
    date_creation DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_maj      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_ticket),
    UNIQUE KEY uq_ticket_reference (reference),
    KEY idx_ticket_statut (id_statut),
    CONSTRAINT fk_ticket_auteur     FOREIGN KEY (id_auteur)     REFERENCES utilisateur (id_utilisateur),
    CONSTRAINT fk_ticket_produit    FOREIGN KEY (id_produit)    REFERENCES produit (id_produit),
    CONSTRAINT fk_ticket_plateforme FOREIGN KEY (id_plateforme) REFERENCES plateforme (id_plateforme),
    CONSTRAINT fk_ticket_statut     FOREIGN KEY (id_statut)     REFERENCES statut_ticket (id_statut),
    CONSTRAINT fk_ticket_priorite   FOREIGN KEY (id_priorite)   REFERENCES priorite (id_priorite),
    CONSTRAINT fk_ticket_technicien FOREIGN KEY (id_technicien) REFERENCES utilisateur (id_utilisateur)
) ENGINE=InnoDB;

-- Jetons d'accès à l'API REST (seule l'empreinte SHA-256 est stockée)
CREATE TABLE jeton_api (
    id_jeton                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_utilisateur            INT UNSIGNED NOT NULL,
    jeton_hash                CHAR(64)     NOT NULL,
    libelle                   VARCHAR(50)  NOT NULL,
    date_creation             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration           DATETIME     NOT NULL,
    date_derniere_utilisation DATETIME     NULL,
    revoque                   BOOLEAN      NOT NULL DEFAULT FALSE,
    PRIMARY KEY (id_jeton),
    UNIQUE KEY uq_jeton_hash (jeton_hash),
    CONSTRAINT fk_jeton_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Journal des tentatives de connexion (anti force brute, conservé 30 jours)
CREATE TABLE tentative_connexion (
    id_tentative   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email          VARCHAR(255)    NOT NULL,
    adresse_ip     VARCHAR(45)     NOT NULL,                -- IPv4 ou IPv6
    date_tentative DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reussie        BOOLEAN         NOT NULL,
    PRIMARY KEY (id_tentative),
    KEY idx_tentative_recherche (email, date_tentative),
    KEY idx_tentative_ip (adresse_ip, date_tentative)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. Tables associatives et tables de suivi
-- ---------------------------------------------------------------------

-- Un produit est disponible sur une ou plusieurs plateformes
CREATE TABLE produit_plateforme (
    id_produit    INT UNSIGNED     NOT NULL,
    id_plateforme TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id_produit, id_plateforme),
    CONSTRAINT fk_pp_produit    FOREIGN KEY (id_produit)    REFERENCES produit (id_produit) ON DELETE CASCADE,
    CONSTRAINT fk_pp_plateforme FOREIGN KEY (id_plateforme) REFERENCES plateforme (id_plateforme)
) ENGINE=InnoDB;

-- Contenu d'une commande : un produit, pour une plateforme, au prix payé ce jour-là
CREATE TABLE ligne_commande (
    id_ligne      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_commande   INT UNSIGNED     NOT NULL,
    id_produit    INT UNSIGNED     NOT NULL,
    id_plateforme TINYINT UNSIGNED NOT NULL,
    prix_unitaire DECIMAL(6,2)     NOT NULL,                -- figé au moment de l'achat
    PRIMARY KEY (id_ligne),
    CONSTRAINT fk_ligne_commande FOREIGN KEY (id_commande) REFERENCES commande (id_commande) ON DELETE CASCADE,
    -- la plateforme choisie doit exister pour ce produit
    CONSTRAINT fk_ligne_offre    FOREIGN KEY (id_produit, id_plateforme) REFERENCES produit_plateforme (id_produit, id_plateforme),
    CONSTRAINT ck_ligne_prix     CHECK (prix_unitaire >= 0)
) ENGINE=InnoDB;

-- Stock de clés d'activation. Statut déduit : disponible (id_ligne NULL),
-- attribuée (id_ligne renseigné), révélée (date_revelation renseignée).
CREATE TABLE cle_activation (
    id_cle           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    code             VARCHAR(29)      NOT NULL,
    id_produit       INT UNSIGNED     NOT NULL,
    id_plateforme    TINYINT UNSIGNED NOT NULL,
    id_ligne         INT UNSIGNED     NULL,
    date_import      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_attribution DATETIME         NULL,
    date_revelation  DATETIME         NULL,
    PRIMARY KEY (id_cle),
    UNIQUE KEY uq_cle_code  (code),
    UNIQUE KEY uq_cle_ligne (id_ligne),                     -- une ligne de commande = une seule clé
    KEY idx_cle_stock (id_produit, id_plateforme, id_ligne),
    CONSTRAINT fk_cle_offre FOREIGN KEY (id_produit, id_plateforme) REFERENCES produit_plateforme (id_produit, id_plateforme),
    CONSTRAINT fk_cle_ligne FOREIGN KEY (id_ligne) REFERENCES ligne_commande (id_ligne),
    CONSTRAINT ck_cle_dates CHECK (date_revelation IS NULL OR id_ligne IS NOT NULL)
) ENGINE=InnoDB;

-- Panier en cours d'un joueur
CREATE TABLE panier_ligne (
    id_panier_ligne INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_utilisateur  INT UNSIGNED     NOT NULL,
    id_produit      INT UNSIGNED     NOT NULL,
    id_plateforme   TINYINT UNSIGNED NOT NULL,
    date_ajout      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_panier_ligne),
    UNIQUE KEY uq_panier (id_utilisateur, id_produit, id_plateforme),
    CONSTRAINT fk_panier_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE,
    CONSTRAINT fk_panier_offre       FOREIGN KEY (id_produit, id_plateforme) REFERENCES produit_plateforme (id_produit, id_plateforme) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Échanges sur un ticket (joueur <-> support). interne = note visible du support uniquement
CREATE TABLE message_ticket (
    id_message INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_ticket  INT UNSIGNED NOT NULL,
    id_auteur  INT UNSIGNED NOT NULL,
    contenu    TEXT         NOT NULL,
    interne    BOOLEAN      NOT NULL DEFAULT FALSE,
    date_envoi DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_message),
    KEY idx_message_ticket (id_ticket, date_envoi),
    CONSTRAINT fk_message_ticket FOREIGN KEY (id_ticket) REFERENCES ticket (id_ticket) ON DELETE CASCADE,
    CONSTRAINT fk_message_auteur FOREIGN KEY (id_auteur) REFERENCES utilisateur (id_utilisateur)
) ENGINE=InnoDB;

-- Historique des changements de statut d'un ticket (traçabilité ITIL)
CREATE TABLE historique_ticket (
    id_historique   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    id_ticket       INT UNSIGNED     NOT NULL,
    id_statut       TINYINT UNSIGNED NOT NULL,
    id_auteur       INT UNSIGNED     NOT NULL,
    date_changement DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    commentaire     VARCHAR(255)     NULL,
    PRIMARY KEY (id_historique),
    KEY idx_historique_ticket (id_ticket, date_changement),
    CONSTRAINT fk_historique_ticket FOREIGN KEY (id_ticket) REFERENCES ticket (id_ticket) ON DELETE CASCADE,
    CONSTRAINT fk_historique_statut FOREIGN KEY (id_statut) REFERENCES statut_ticket (id_statut),
    CONSTRAINT fk_historique_auteur FOREIGN KEY (id_auteur) REFERENCES utilisateur (id_utilisateur)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Vue utilitaire : prix affiché d'un produit, promotion du jour incluse
-- ---------------------------------------------------------------------
CREATE VIEW v_produit_prix AS
SELECT p.id_produit,
       p.prix                                                   AS prix_base,
       COALESCE(MAX(pr.pourcentage), 0)                         AS remise_pct,
       ROUND(p.prix * (100 - COALESCE(MAX(pr.pourcentage), 0)) / 100, 2) AS prix_final
FROM produit p
LEFT JOIN promotion pr
       ON pr.id_produit = p.id_produit
      AND CURRENT_DATE BETWEEN pr.date_debut AND pr.date_fin
GROUP BY p.id_produit, p.prix;
