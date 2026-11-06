-- =====================================================================
-- PixelWave Studio · Portail joueurs
-- Livrable 5 : jeu de données de test
--
-- Données 100 % fictives (domaines .test, clés inventées).
-- Mot de passe de tous les comptes de démonstration : voir database/README.md
-- Ordre d'insertion = ordre de création : tables référencées d'abord.
--
-- Cas couverts :
--   - un joueur sans achat (NoobSaibot), un compte désactivé (OldTimer)
--   - une plateforme sans produit (Xbox), un genre sans jeu (Plateforme)
--   - un produit retiré de la vente, un jeu pas encore sorti (précommande)
--   - promotions en cours, expirée et à venir
--   - un produit en rupture de stock de clés (Starlit Courier sur Switch)
--   - une commande remboursée, une commande multi-plateformes
--   - clés révélées / non révélées / disponibles
--   - tickets dans chaque statut, ticket non qualifié (sans priorité ni technicien)
--   - une actualité brouillon, un jeton API valide, expiré et révoqué
-- =====================================================================

USE pixelwave;

-- ---------------------------------------------------------------------
-- Tables de référence
-- ---------------------------------------------------------------------
INSERT INTO role (id_role, code, libelle) VALUES
    (1, 'joueur',  'Joueur'),
    (2, 'support', 'Support / QA'),
    (3, 'admin',   'Administrateur');

INSERT INTO plateforme (id_plateforme, code, nom) VALUES
    (1, 'PC',     'PC'),
    (2, 'PS5',    'PlayStation 5'),
    (3, 'SWITCH', 'Nintendo Switch'),
    (4, 'XBOX',   'Xbox Series');           -- aucun produit : cas limite

INSERT INTO genre (id_genre, libelle) VALUES
    (1, 'Course arcade'),
    (2, 'Gestion'),
    (3, 'Exploration'),
    (4, 'Aventure'),
    (5, 'Stratégie'),
    (6, 'Plateforme');                       -- aucun jeu : cas limite

INSERT INTO statut_ticket (id_statut, code, libelle, ordre, est_final) VALUES
    (1, 'nouveau',        'Nouveau',              1, FALSE),
    (2, 'en_cours',       'En cours',             2, FALSE),
    (3, 'attente_joueur', 'En attente du joueur', 3, FALSE),
    (4, 'resolu',         'Résolu',               4, FALSE),
    (5, 'clos',           'Clos',                 5, TRUE);

INSERT INTO priorite (id_priorite, code, libelle, niveau) VALUES
    (1, 'basse',    'Basse',    1),
    (2, 'moyenne',  'Moyenne',  2),
    (3, 'haute',    'Haute',    3),
    (4, 'critique', 'Critique', 4);

INSERT INTO categorie_actualite (id_categorie, libelle) VALUES
    (1, 'Mise à jour'),
    (2, 'Studio'),
    (3, 'Communauté');

-- ---------------------------------------------------------------------
-- Utilisateurs (hachages bcrypt générés avec password_hash() de PHP)
-- ---------------------------------------------------------------------
INSERT INTO utilisateur (id_utilisateur, pseudo, email, mot_de_passe_hash, id_role, newsletter, actif, date_inscription, derniere_connexion) VALUES
    (1,  'nadia.admin',   'nadia@pixelwave.test',      '$2y$11$xpR4OV9o5ktkSx7Q5xIE3.aW7sGpVueHAkCWTz1bN/heMDHjsE9TG', 3, FALSE, TRUE,  '2026-01-05 09:00:00', '2026-11-05 18:12:00'),
    (2,  'karim.support', 'karim@pixelwave.test',      '$2y$11$FYLqqtCe4UU9YKprm.086.ak5BQEXUvQsQLua2FjXdPAz5Z4PR9vS', 2, FALSE, TRUE,  '2026-01-05 09:05:00', '2026-11-06 08:30:00'),
    (3,  'julie.qa',      'julie@pixelwave.test',      '$2y$11$9lJKyLy7m3BY8I6EqwZuWe53EoAuE8gQjMrA14lj67FnSih/MgSvO', 2, FALSE, TRUE,  '2026-02-10 10:00:00', '2026-11-04 17:45:00'),
    (4,  'LeaPixel',      'lea.martin@exemple.test',   '$2y$11$Z44IzbmQJJOdt8TaUYilN.ejBDYXJP8PwJOpy8owCuSTj7mbN3RVm', 1, TRUE,  TRUE,  '2026-03-01 20:14:00', '2026-11-05 21:02:00'),
    (5,  'DriftKing',     'tom.durand@exemple.test',   '$2y$11$MOrJVfcT/OPg.Fo0QiKrW.4Ow3CvbwTvTqO9AfxPK8Qkx3VFPj2uu', 1, FALSE, TRUE,  '2026-04-10 18:30:00', '2026-10-30 19:40:00'),
    (6,  'ForgeMaster',   'ines.bernard@exemple.test', '$2y$11$TsZSWrgXRpv8SYdUJ7Sbm.2E8Ylbe.pEj7MPtWumjdW0S694OxWQW', 1, TRUE,  TRUE,  '2026-05-19 12:00:00', '2026-10-26 10:05:00'),
    (7,  'AbyssDiver',    'hugo.petit@exemple.test',   '$2y$11$3CenjLy1Trz3oBcTyYBUU.hHsuzDaYNfIb4ABgSCDlXpaxcQmZl9.', 1, FALSE, TRUE,  '2026-10-04 22:10:00', '2026-11-05 23:15:00'),
    (8,  'StarRunner',    'sarah.moreau@exemple.test', '$2y$11$KXigo6ZBGeo1ZJkEbpne4.g/xBUVtxEBkflFN97IoJRmEf28g.As.', 1, TRUE,  TRUE,  '2026-10-23 08:00:00', '2026-10-24 14:20:00'),
    (9,  'NoobSaibot',    'yanis.leroy@exemple.test',  '$2y$11$3NXXjkK5U3CeAeHOL29y/exxEKvir5xkdT5G.G0a63HTG6vLh4/9K', 1, FALSE, TRUE,  '2026-11-01 16:45:00', NULL),        -- jamais connecté, aucun achat
    (10, 'OldTimer',      'paul.roux@exemple.test',    '$2y$11$j9lJnhIdSOxTk3y3EUuDUeH1UdSZkK9MV4RUSYGu/OBu21o1i8U8m', 1, FALSE, FALSE, '2026-01-20 11:11:00', '2026-02-02 09:00:00'), -- compte désactivé
    (11, 'Kenji_JP',      'kenji@exemple.test',        '$2y$11$uVOulpqHkTrfth8hGaXP7OTKjn8zYRiB7TCgrT1GXuNJ8DBc.VW9C', 1, FALSE, TRUE,  '2026-11-02 07:30:00', '2026-11-06 07:55:00'),
    (12, 'Mila',          'mila@exemple.test',         '$2y$11$sJrasAbZd8CXVObrNsTgjO4StogJmtNfvoN7qFRcgg7Nm3/fohbca', 1, TRUE,  TRUE,  '2026-06-15 15:00:00', '2026-09-12 20:00:00');

-- ---------------------------------------------------------------------
-- Catalogue
-- ---------------------------------------------------------------------
INSERT INTO produit (id_produit, type, titre, slug, accroche, description, prix, date_sortie, id_genre, id_jeu_parent, visuel, actif) VALUES
    (1, 'jeu', 'Neon Drift', 'neon-drift',
        'Course arcade rétro dans une ville au néon.',
        'Pilote des bolides pixelisés sur des circuits urbains, débloque des voitures et défie tes amis en écran partagé.',
        19.99, '2023-03-14', 1, NULL, 'neon', TRUE),
    (2, 'jeu', 'Tiny Forge', 'tiny-forge',
        'Gère la plus petite forge du royaume.',
        'Récolte, fabrique et vends des objets à des aventuriers exigeants. Agrandis ton atelier saison après saison.',
        14.99, '2024-06-20', 2, NULL, 'forge', TRUE),
    (3, 'jeu', 'Abyssal Tides', 'abyssal-tides',
        'Explore un océan qui change à chaque plongée.',
        'Pilote un petit sous-marin, cartographie les fonds et découvre ce qui se cache sous la dernière fosse.',
        24.99, '2025-09-18', 3, NULL, 'abyss', TRUE),
    (4, 'jeu', 'Starlit Courier', 'starlit-courier',
        'Livre des colis entre les étoiles.',
        'Un jeu d''aventure narratif où chaque livraison raconte une histoire. Choix multiples et trois fins possibles.',
        29.99, '2026-10-23', 4, NULL, 'starlit', TRUE),
    (5, 'jeu', 'Pocket Galaxy', 'pocket-galaxy',
        'Une galaxie entière dans ta poche.',
        'Jeu de stratégie au tour par tour en parties courtes. Sortie prévue le 10 décembre 2026, précommande ouverte.',
        12.99, '2026-12-10', 5, NULL, 'galaxy', TRUE),
    (6, 'dlc', 'Neon Drift : Circuit Nocturne', 'neon-drift-circuit-nocturne',
        'Douze circuits de nuit et une météo qui change pendant la course.',
        'Extension pour Neon Drift : douze nouveaux circuits nocturnes, pluie dynamique et deux nouvelles voitures.',
        9.99, '2026-10-20', NULL, 1, 'nocturne', TRUE),
    (7, 'dlc', 'Tiny Forge : Atelier d''hiver', 'tiny-forge-atelier-hiver',
        'La saison froide arrive dans ta forge.',
        'Nouvelles recettes, clients venus du Nord et décorations d''hiver pour ton atelier.',
        7.99, '2025-12-01', NULL, 2, 'hiver', TRUE),
    (8, 'dlc', 'Neon Drift : Livrées rétro', 'neon-drift-livrees-retro',
        'Huit peintures inspirées des années 80.',
        'Pack cosmétique de huit livrées rétro pour les voitures de Neon Drift.',
        3.99, '2024-02-02', NULL, 1, 'retro', TRUE),
    (9, 'dlc', 'Tiny Forge : Pack de démarrage', 'tiny-forge-pack-demarrage',
        'Offre de lancement (retirée de la vente).',
        NULL,
        1.99, '2024-06-20', NULL, 2, 'defaut', FALSE);   -- produit retiré : cas limite

INSERT INTO produit_plateforme (id_produit, id_plateforme) VALUES
    (1, 1), (1, 2),
    (2, 1), (2, 3),
    (3, 1),
    (4, 1), (4, 2), (4, 3),
    (5, 1), (5, 3),
    (6, 1), (6, 2),
    (7, 1), (7, 3),
    (8, 1), (8, 2),
    (9, 1);

INSERT INTO promotion (id_promotion, id_produit, pourcentage, date_debut, date_fin) VALUES
    (1, 3, 30, '2026-10-01', '2026-11-30'),   -- en cours : Abyssal Tides 24,99 -> 17,49
    (2, 7, 30, '2026-10-15', '2026-11-15'),   -- en cours : Atelier d'hiver 7,99 -> 5,59
    (3, 1, 50, '2026-07-01', '2026-07-15'),   -- expirée
    (4, 4, 20, '2026-12-15', '2026-12-31');   -- à venir

-- ---------------------------------------------------------------------
-- Actualités
-- ---------------------------------------------------------------------
INSERT INTO actualite (id_actualite, titre, slug, chapo, contenu, id_categorie, id_auteur, id_produit, date_publication, publiee) VALUES
    (1, 'Neon Drift 1.4 : les notes de mise à jour', 'neon-drift-1-4-notes-de-mise-a-jour',
        'Nouveau mode photo et correction des plantages au chargement des circuits.',
        'La version 1.4 ajoute un mode photo, corrige le plantage au chargement du circuit Port Néon sur PS5 et améliore la stabilité en écran partagé.',
        1, 1, 1, '2026-10-14 10:00:00', TRUE),
    (2, 'Abyssal Tides arrive sur consoles', 'abyssal-tides-arrive-sur-consoles',
        'Les précommandes ouvrent sur le portail avec une clé pour la plateforme de ton choix.',
        'Abyssal Tides sortira sur PS5 et Switch au printemps 2027. Les joueurs PC gardent leur progression grâce à la sauvegarde dans le cloud.',
        2, 1, 3, '2026-10-08 09:30:00', TRUE),
    (3, 'Concours de constructions Tiny Forge', 'concours-de-constructions-tiny-forge',
        'Partage ta plus belle forge sur Discord avant le 31 octobre.',
        'Publie une capture de ta forge dans le salon #concours de notre Discord. Les trois plus belles forges gagnent le DLC Atelier d''hiver.',
        3, 1, 2, '2026-10-02 14:00:00', TRUE),
    (4, 'Starlit Courier est disponible', 'starlit-courier-est-disponible',
        'Notre nouveau jeu narratif sort aujourd''hui sur PC, PS5 et Switch.',
        'Starlit Courier est disponible dès maintenant. Merci aux 1 200 testeurs de la bêta pour leurs retours.',
        2, 1, 4, '2026-10-23 08:00:00', TRUE),
    (5, 'Pocket Galaxy : la date de sortie', 'pocket-galaxy-date-de-sortie',
        'Brouillon en cours de relecture.',
        'Texte en cours de rédaction.',
        2, 1, 5, NULL, FALSE);                    -- brouillon : non visible

-- ---------------------------------------------------------------------
-- Commandes, lignes et clés d'activation
-- ---------------------------------------------------------------------
INSERT INTO commande (id_commande, reference, id_utilisateur, date_commande, statut) VALUES
    (1, 'CMD-2026-000001', 4,  '2026-03-02 20:31:00', 'payee'),
    (2, 'CMD-2026-000002', 5,  '2026-04-11 18:45:00', 'payee'),
    (3, 'CMD-2026-000003', 6,  '2026-05-20 12:10:00', 'payee'),
    (4, 'CMD-2026-000004', 4,  '2026-10-21 19:02:00', 'payee'),
    (5, 'CMD-2026-000005', 7,  '2026-10-05 22:20:00', 'payee'),
    (6, 'CMD-2026-000006', 6,  '2026-10-20 09:40:00', 'payee'),
    (7, 'CMD-2026-000007', 8,  '2026-10-24 14:05:00', 'payee'),
    (8, 'CMD-2026-000008', 5,  '2026-09-01 21:00:00', 'remboursee'),  -- remboursement : cas limite
    (9, 'CMD-2026-000009', 11, '2026-11-02 07:45:00', 'payee');

INSERT INTO ligne_commande (id_ligne, id_commande, id_produit, id_plateforme, prix_unitaire) VALUES
    (1,  1, 1, 1, 19.99),
    (2,  1, 8, 1,  3.99),
    (3,  2, 1, 2, 19.99),
    (4,  3, 2, 3, 14.99),
    (5,  4, 6, 1,  9.99),
    (6,  4, 3, 1, 17.49),   -- acheté pendant la promotion
    (7,  5, 3, 1, 17.49),
    (8,  6, 7, 3,  5.59),
    (9,  7, 4, 2, 29.99),
    (10, 7, 4, 3, 29.99),   -- même jeu sur deux plateformes
    (11, 8, 2, 1, 14.99),
    (12, 9, 5, 1, 12.99),   -- précommande
    (13, 9, 1, 1, 19.99);

-- Clés vendues (une par ligne de commande)
INSERT INTO cle_activation (id_cle, code, id_produit, id_plateforme, id_ligne, date_import, date_attribution, date_revelation) VALUES
    (1,  'HWGQ4-96RAF-HUG6A', 1, 1, 1,  '2026-01-10 08:00:00', '2026-03-02 20:31:00', '2026-03-02 20:33:00'),
    (2,  '9WP3S-YY2EX-FUU7K', 8, 1, 2,  '2026-01-10 08:00:00', '2026-03-02 20:31:00', '2026-03-02 20:34:00'),
    (3,  'VBZZ7-5F39H-593S4', 1, 2, 3,  '2026-01-10 08:00:00', '2026-04-11 18:45:00', '2026-04-11 18:50:00'),
    (4,  '8BQJD-G58K8-QH78S', 2, 3, 4,  '2026-01-10 08:00:00', '2026-05-20 12:10:00', '2026-05-20 12:15:00'),
    (5,  '3V835-NUTAC-K79ZN', 6, 1, 5,  '2026-10-15 08:00:00', '2026-10-21 19:02:00', NULL),
    (6,  'S9795-CUW7G-6S6M5', 3, 1, 6,  '2026-09-01 08:00:00', '2026-10-21 19:02:00', '2026-10-21 19:10:00'),
    (7,  '8ET9Z-GN4K6-AFA6F', 3, 1, 7,  '2026-09-01 08:00:00', '2026-10-05 22:20:00', '2026-10-05 22:21:00'),
    (8,  'NRHCT-YKAW9-JQ8JU', 7, 3, 8,  '2026-09-01 08:00:00', '2026-10-20 09:40:00', NULL),
    (9,  'F4LJV-42KN2-AG2ED', 4, 2, 9,  '2026-10-20 08:00:00', '2026-10-24 14:05:00', '2026-10-24 14:07:00'),
    (10, 'YYBB8-WV6WH-97LKP', 4, 3, 10, '2026-10-20 08:00:00', '2026-10-24 14:05:00', NULL),
    (11, '5X7QE-3CNMC-D6N8B', 2, 1, 11, '2026-01-10 08:00:00', '2026-09-01 21:00:00', '2026-09-01 21:02:00'),
    (12, 'JPJLM-2T54F-2VMYM', 5, 1, 12, '2026-10-30 08:00:00', '2026-11-02 07:45:00', NULL),
    (13, 'S794U-7MW86-HTGPQ', 1, 1, 13, '2026-01-10 08:00:00', '2026-11-02 07:45:00', '2026-11-02 07:47:00');

-- Clés en stock (disponibles à la vente)
INSERT INTO cle_activation (code, id_produit, id_plateforme, date_import) VALUES
    ('AF7X4-MBYLY-PHTPU', 1, 1, '2026-10-01 08:00:00'),
    ('TJGSG-5LGDY-MGWTE', 1, 1, '2026-10-01 08:00:00'),
    ('UWLSS-EA4S4-ADGRB', 1, 1, '2026-10-01 08:00:00'),
    ('VHGYW-7C3WP-75HZS', 1, 2, '2026-10-01 08:00:00'),
    ('2UYD5-BUXUH-V4WBX', 1, 2, '2026-10-01 08:00:00'),
    ('NDHLH-HCJYV-B5JY4', 2, 1, '2026-10-01 08:00:00'),
    ('22R4Q-JMB3S-KQBBT', 2, 1, '2026-10-01 08:00:00'),
    ('84SWJ-FMCSX-WDAR8', 2, 3, '2026-10-01 08:00:00'),
    ('DQK3Z-QTXNZ-Y778V', 2, 3, '2026-10-01 08:00:00'),
    ('NL4K9-AY4CM-FVZSJ', 3, 1, '2026-10-01 08:00:00'),
    ('GBPVC-32LGF-K2XSV', 3, 1, '2026-10-01 08:00:00'),
    ('CNLUU-UYJ2N-A85YL', 3, 1, '2026-10-01 08:00:00'),
    ('BMWKL-BHAZE-ZVPKR', 4, 1, '2026-10-20 08:00:00'),
    ('JGW9G-E2SVH-Y89HR', 4, 1, '2026-10-20 08:00:00'),
    ('Y7YFE-F9VSQ-LW5XW', 4, 1, '2026-10-20 08:00:00'),
    ('93WMN-7QYY4-2PK6J', 4, 2, '2026-10-20 08:00:00'),
    ('5TXHW-T6SKT-QXKHT', 4, 2, '2026-10-20 08:00:00'),
    -- Starlit Courier Switch : plus aucune clé (rupture de stock)
    ('U733B-X4XG9-P5YXD', 5, 1, '2026-10-30 08:00:00'),
    ('3MATS-JX488-XACQH', 5, 1, '2026-10-30 08:00:00'),
    -- Pocket Galaxy Switch : pas encore de clés reçues
    ('TWBP7-TZFNA-GKXQ7', 6, 1, '2026-10-15 08:00:00'),
    ('PLVF4-JGBT4-CMQ8X', 6, 1, '2026-10-15 08:00:00'),
    ('XYW8S-9WSC9-JB8ZS', 6, 1, '2026-10-15 08:00:00'),
    ('7NZT3-VPD4E-V8FW9', 6, 2, '2026-10-15 08:00:00'),
    ('WXZME-MS2GP-B7RP5', 6, 2, '2026-10-15 08:00:00'),
    ('JPT9J-WZ3NK-KKJDC', 7, 1, '2026-10-01 08:00:00'),
    ('TVMJT-YJ7LM-DMK49', 7, 1, '2026-10-01 08:00:00'),
    ('BVQZF-74KWK-C8ADG', 7, 3, '2026-10-01 08:00:00'),
    ('YSXBL-NF55N-LRQ9P', 7, 3, '2026-10-01 08:00:00'),
    ('ZL557-ZPV68-FPNHF', 8, 1, '2026-10-01 08:00:00'),
    ('YLHMP-5N5BK-TUZ3A', 8, 1, '2026-10-01 08:00:00'),
    ('87TY9-JGKQY-72DWS', 8, 2, '2026-10-01 08:00:00');

-- ---------------------------------------------------------------------
-- Paniers en cours
-- ---------------------------------------------------------------------
INSERT INTO panier_ligne (id_utilisateur, id_produit, id_plateforme, date_ajout) VALUES
    (4, 4, 1, '2026-11-05 21:00:00'),     -- LeaPixel : Starlit Courier PC
    (5, 6, 2, '2026-10-30 19:35:00'),     -- DriftKing : Circuit Nocturne PS5
    (5, 7, 1, '2026-10-30 19:36:00');     -- DriftKing : Atelier d'hiver PC

-- ---------------------------------------------------------------------
-- Tickets de support, messages et historique
-- ---------------------------------------------------------------------
INSERT INTO ticket (id_ticket, reference, id_auteur, id_produit, id_plateforme, titre, description, version_jeu, frequence, id_statut, id_priorite, id_technicien, date_creation, date_maj) VALUES
    (1, 'BUG-2026-0001', 5, 1, 2, 'Crash au chargement du circuit Port Néon',
        'Le jeu se ferme pendant l''écran de chargement du circuit Port Néon, à chaque fois.', '1.3.2', 'toujours',
        4, 3, 2, '2026-09-28 20:10:00', '2026-10-14 10:05:00'),
    (2, 'BUG-2026-0002', 4, 6, 1, 'La pluie ne s''affiche pas sur le circuit Quai 9',
        'La météo annonce de la pluie mais la piste reste sèche, environ une course sur trois.', '1.4.0', 'parfois',
        2, 2, 3, '2026-10-22 21:30:00', '2026-10-23 09:15:00'),
    (3, 'BUG-2026-0003', 6, 2, 3, 'Sauvegarde perdue après mise en veille',
        'J''ai mis la console en veille pendant une partie, au réveil ma forge était revenue à la saison 2.', '2.1.0', 'une_fois',
        3, 3, 2, '2026-10-25 10:00:00', '2026-10-26 08:40:00'),
    (4, 'BUG-2026-0004', 7, 3, 1, 'Texte qui dépasse dans le journal de bord',
        'Dans le journal de bord, les longues descriptions de créatures sortent du cadre.', '1.0.3', 'toujours',
        1, NULL, NULL, '2026-11-05 23:12:00', '2026-11-05 23:12:00'),   -- non qualifié
    (5, 'BUG-2026-0005', 4, 3, 1, 'Ma clé Abyssal Tides est indiquée comme déjà utilisée',
        'Steam me dit que la clé est déjà utilisée alors que je viens de l''acheter.', NULL, NULL,
        5, 4, 2, '2026-10-21 19:15:00', '2026-10-22 11:00:00'),
    (6, 'BUG-2026-0006', 11, 1, 1, 'Manette non reconnue au lancement',
        'Ma manette sans fil n''est détectée qu''après être revenu au menu principal.', '1.4.0', 'parfois',
        1, NULL, NULL, '2026-11-06 07:58:00', '2026-11-06 07:58:00');

INSERT INTO message_ticket (id_ticket, id_auteur, contenu, interne, date_envoi) VALUES
    (1, 2, 'Merci pour ton signalement. Nous reproduisons le plantage sur PS5, un correctif est en préparation.', FALSE, '2026-09-29 09:20:00'),
    (1, 2, 'Reproduit sur kit de dev PS5 : fuite mémoire au chargement des textures du port. Remonté à l''équipe moteur.', TRUE, '2026-09-29 09:25:00'),
    (1, 5, 'Super, merci ! J''attends la mise à jour.', FALSE, '2026-09-29 18:00:00'),
    (1, 2, 'La version 1.4 corrige le problème. Peux-tu nous confirmer que tout fonctionne ?', FALSE, '2026-10-14 10:05:00'),
    (2, 3, 'Bien reçu. Peux-tu préciser si tu joues en qualité graphique « Haute » ou « Ultra » ?', FALSE, '2026-10-23 09:15:00'),
    (2, 4, 'Je joue en Ultra, avec la synchronisation verticale activée.', FALSE, '2026-10-23 20:40:00'),
    (3, 2, 'Pour t''aider, peux-tu nous envoyer l''heure approximative de la mise en veille et ta version du jeu ?', FALSE, '2026-10-26 08:40:00'),
    (5, 2, 'Nous avons vérifié : la clé avait été attribuée deux fois suite à un import en double. Une nouvelle clé t''a été envoyée.', FALSE, '2026-10-22 10:50:00'),
    (5, 4, 'La nouvelle clé fonctionne, merci beaucoup !', FALSE, '2026-10-22 10:58:00');

INSERT INTO historique_ticket (id_ticket, id_statut, id_auteur, date_changement, commentaire) VALUES
    (1, 1, 5, '2026-09-28 20:10:00', 'Création du ticket'),
    (1, 2, 2, '2026-09-29 09:20:00', 'Pris en charge, priorité haute'),
    (1, 4, 2, '2026-10-14 10:05:00', 'Corrigé dans la version 1.4'),
    (2, 1, 4, '2026-10-22 21:30:00', 'Création du ticket'),
    (2, 2, 3, '2026-10-23 09:15:00', 'Pris en charge'),
    (3, 1, 6, '2026-10-25 10:00:00', 'Création du ticket'),
    (3, 2, 2, '2026-10-26 08:35:00', 'Pris en charge'),
    (3, 3, 2, '2026-10-26 08:40:00', 'Informations demandées au joueur'),
    (4, 1, 7, '2026-11-05 23:12:00', 'Création du ticket'),
    (5, 1, 4, '2026-10-21 19:15:00', 'Création du ticket'),
    (5, 2, 2, '2026-10-22 09:00:00', 'Pris en charge, priorité critique (achat bloqué)'),
    (5, 4, 2, '2026-10-22 10:50:00', 'Nouvelle clé envoyée'),
    (5, 5, 2, '2026-10-22 11:00:00', 'Confirmé par le joueur'),
    (6, 1, 11, '2026-11-06 07:58:00', 'Création du ticket');

-- ---------------------------------------------------------------------
-- Jetons API (empreintes SHA-256 des jetons de démonstration)
-- ---------------------------------------------------------------------
INSERT INTO jeton_api (id_utilisateur, jeton_hash, libelle, date_creation, date_expiration, date_derniere_utilisation, revoque) VALUES
    (4, 'a33d5c268e01055527dbd3e5c88a24cc93e2d4bf7b305a9f90fd543a336e1ee5', 'Postman',        '2026-11-01 10:00:00', '2027-06-30 23:59:59', '2026-11-05 21:05:00', FALSE),
    (5, '18a47065a1f01260e6ffd9a499f2a5ae6b7f98ba5d53cacbd4943eb83fec4d46', 'Ancien launcher', '2026-04-11 19:00:00', '2026-07-11 19:00:00', '2026-07-10 22:00:00', FALSE),  -- expiré
    (4, '609bb5c0abeda7ba993b230a9ad2b047062677e48875c99845dac4475000f29b', 'Téléphone perdu', '2026-03-05 12:00:00', '2027-03-05 12:00:00', '2026-06-01 08:00:00', TRUE);   -- révoqué

-- ---------------------------------------------------------------------
-- Journal de connexion
-- ---------------------------------------------------------------------
INSERT INTO tentative_connexion (email, adresse_ip, date_tentative, reussie) VALUES
    ('lea.martin@exemple.test',  '203.0.113.24',  '2026-11-05 21:01:40', FALSE),
    ('lea.martin@exemple.test',  '203.0.113.24',  '2026-11-05 21:02:00', TRUE),
    ('hugo.petit@exemple.test',  '198.51.100.7',  '2026-11-05 23:15:00', TRUE),
    ('admin@pixelwave.test',     '192.0.2.200',   '2026-11-06 03:12:01', FALSE),
    ('admin@pixelwave.test',     '192.0.2.200',   '2026-11-06 03:12:03', FALSE),
    ('admin@pixelwave.test',     '192.0.2.200',   '2026-11-06 03:12:05', FALSE),
    ('kenji@exemple.test',       '2001:db8::42',  '2026-11-06 07:55:00', TRUE);
