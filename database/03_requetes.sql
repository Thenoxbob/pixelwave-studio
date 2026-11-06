-- =====================================================================
-- PixelWave Studio · Portail joueurs
-- Livrable 6 : requêtes SQL d'exploitation
--
-- Chaque requête répond à un besoin du portail (cahier des charges §5).
-- La partie « modification » est encadrée par une transaction annulée
-- (ROLLBACK) : le script peut être rejoué sans abîmer le jeu de données.
-- =====================================================================

USE pixelwave;

-- =====================================================================
-- A. LIRE, FILTRER, TRIER
-- =====================================================================

-- R1 · Boutique : produits en vente, du plus récent au plus ancien, avec le prix du jour
SELECT p.titre, p.type, v.prix_base, v.remise_pct, v.prix_final, p.date_sortie
FROM produit p
JOIN v_produit_prix v ON v.id_produit = p.id_produit
WHERE p.actif = TRUE
ORDER BY p.date_sortie DESC;

-- R2 · Boutique, filtre « Promotions » : produits remisés aujourd'hui
SELECT p.titre, pr.pourcentage, pr.date_fin AS fin_promo
FROM produit p
JOIN promotion pr ON pr.id_produit = p.id_produit
WHERE CURRENT_DATE BETWEEN pr.date_debut AND pr.date_fin
  AND p.actif = TRUE
ORDER BY pr.pourcentage DESC, p.titre;

-- R3 · Recherche dans la boutique (champ « Nom d'un jeu ou d'un DLC »)
SELECT titre, type, prix
FROM produit
WHERE actif = TRUE
  AND titre LIKE CONCAT('%', 'neon', '%')
ORDER BY titre;

-- R4 · Page d'accueil : les 3 dernières actualités publiées
SELECT a.titre, c.libelle AS categorie, a.chapo, a.date_publication
FROM actualite a
JOIN categorie_actualite c ON c.id_categorie = a.id_categorie
WHERE a.publiee = TRUE
ORDER BY a.date_publication DESC
LIMIT 3;

-- R5 · Connexion : retrouver un compte actif à partir de l'e-mail
--      (le mot de passe est vérifié ensuite en PHP avec password_verify)
SELECT u.id_utilisateur, u.pseudo, u.mot_de_passe_hash, r.code AS role
FROM utilisateur u
JOIN role r ON r.id_role = u.id_role
WHERE u.email = 'lea.martin@exemple.test'
  AND u.actif = TRUE;

-- =====================================================================
-- B. JOINTURES
-- =====================================================================

-- R6 · Fiche produit : plateformes disponibles et stock de clés restant
SELECT p.titre, pf.nom AS plateforme,
       COUNT(c.id_cle) AS cles_disponibles
FROM produit p
JOIN produit_plateforme pp ON pp.id_produit = p.id_produit
JOIN plateforme pf         ON pf.id_plateforme = pp.id_plateforme
LEFT JOIN cle_activation c ON c.id_produit = pp.id_produit
                          AND c.id_plateforme = pp.id_plateforme
                          AND c.id_ligne IS NULL
WHERE p.slug = 'starlit-courier'
GROUP BY p.titre, pf.nom
ORDER BY pf.nom;

-- R7 · « Ma bibliothèque » : jeux possédés par LeaPixel et état de leurs clés
--      (les commandes remboursées ne donnent plus accès au jeu)
SELECT p.titre, pf.nom AS plateforme, co.date_commande,
       CASE WHEN c.date_revelation IS NULL THEN 'à révéler' ELSE c.code END AS cle
FROM commande co
JOIN ligne_commande l  ON l.id_commande = co.id_commande
JOIN produit p         ON p.id_produit = l.id_produit
JOIN plateforme pf     ON pf.id_plateforme = l.id_plateforme
JOIN cle_activation c  ON c.id_ligne = l.id_ligne
WHERE co.id_utilisateur = 4
  AND co.statut = 'payee'
ORDER BY co.date_commande DESC;

-- R8 · DLC et jeu parent (auto-jointure sur produit)
SELECT d.titre AS dlc, j.titre AS jeu_parent, d.prix
FROM produit d
JOIN produit j ON j.id_produit = d.id_jeu_parent
WHERE d.type = 'dlc'
ORDER BY j.titre, d.titre;

-- R9 · File du support : tickets ouverts, du plus prioritaire au moins prioritaire
--      (LEFT JOIN : un ticket nouveau n'a encore ni priorité ni technicien)
SELECT t.reference, t.titre, s.libelle AS statut,
       COALESCE(pr.libelle, 'À qualifier') AS priorite,
       COALESCE(tech.pseudo, '—')         AS technicien,
       joueur.pseudo AS joueur, p.titre AS jeu, t.date_creation
FROM ticket t
JOIN statut_ticket s     ON s.id_statut = t.id_statut
JOIN utilisateur joueur  ON joueur.id_utilisateur = t.id_auteur
LEFT JOIN priorite pr    ON pr.id_priorite = t.id_priorite
LEFT JOIN utilisateur tech ON tech.id_utilisateur = t.id_technicien
LEFT JOIN produit p      ON p.id_produit = t.id_produit
WHERE s.est_final = FALSE
ORDER BY (t.id_priorite IS NULL) DESC, pr.niveau DESC, t.date_creation;

-- R10 · Détail d'un ticket vu par le joueur : messages publics seulement
SELECT m.date_envoi, u.pseudo, r.code AS role, m.contenu
FROM message_ticket m
JOIN ticket t      ON t.id_ticket = m.id_ticket
JOIN utilisateur u ON u.id_utilisateur = m.id_auteur
JOIN role r        ON r.id_role = u.id_role
WHERE t.reference = 'BUG-2026-0001'
  AND m.interne = FALSE
ORDER BY m.date_envoi;

-- R11 · Joueurs n'ayant jamais rien acheté (relance marketing, si newsletter acceptée)
SELECT u.pseudo, u.email, u.newsletter
FROM utilisateur u
LEFT JOIN commande co ON co.id_utilisateur = u.id_utilisateur
WHERE u.id_role = 1
  AND u.actif = TRUE
  AND co.id_commande IS NULL;

-- =====================================================================
-- C. AGRÉGATS ET REGROUPEMENTS
-- =====================================================================

-- R12 · Montant de chaque commande (calculé à partir des lignes, jamais stocké)
SELECT co.reference, u.pseudo, co.statut,
       COUNT(l.id_ligne)    AS nb_articles,
       SUM(l.prix_unitaire) AS montant_total
FROM commande co
JOIN utilisateur u    ON u.id_utilisateur = co.id_utilisateur
JOIN ligne_commande l ON l.id_commande = co.id_commande
GROUP BY co.id_commande, co.reference, u.pseudo, co.statut
ORDER BY co.date_commande;

-- R13 · Indicateur direction : chiffre d'affaires et ventes par produit (commandes payées)
SELECT p.titre,
       COUNT(l.id_ligne)              AS ventes,
       SUM(l.prix_unitaire)           AS chiffre_affaires,
       ROUND(AVG(l.prix_unitaire), 2) AS prix_moyen_paye
FROM ligne_commande l
JOIN commande co ON co.id_commande = l.id_commande
JOIN produit p   ON p.id_produit = l.id_produit
WHERE co.statut = 'payee'
GROUP BY p.id_produit, p.titre
ORDER BY chiffre_affaires DESC;

-- R14 · Indicateur QA : nombre de tickets par jeu et par statut
SELECT COALESCE(p.titre, 'Hors jeu') AS jeu, s.libelle AS statut, COUNT(*) AS nb_tickets
FROM ticket t
JOIN statut_ticket s ON s.id_statut = t.id_statut
LEFT JOIN produit p  ON p.id_produit = t.id_produit
GROUP BY p.titre, s.ordre, s.libelle
ORDER BY jeu, s.ordre;

-- R15 · Alerte stock : offres (produit + plateforme) avec moins de 2 clés disponibles
SELECT p.titre, pf.nom AS plateforme, COUNT(c.id_cle) AS stock
FROM produit_plateforme pp
JOIN produit p    ON p.id_produit = pp.id_produit
JOIN plateforme pf ON pf.id_plateforme = pp.id_plateforme
LEFT JOIN cle_activation c ON c.id_produit = pp.id_produit
                          AND c.id_plateforme = pp.id_plateforme
                          AND c.id_ligne IS NULL
WHERE p.actif = TRUE
GROUP BY pp.id_produit, pp.id_plateforme, p.titre, pf.nom
HAVING COUNT(c.id_cle) < 2
ORDER BY stock, p.titre;

-- R16 · Sécurité : adresses IP avec au moins 3 échecs de connexion sur les dernières 24 h
--      (en démo, la fenêtre part du 06/11/2026 pour coller au jeu de données)
SELECT adresse_ip, COUNT(*) AS echecs, MIN(date_tentative) AS premier, MAX(date_tentative) AS dernier
FROM tentative_connexion
WHERE reussie = FALSE
  AND date_tentative >= '2026-11-06 00:00:00' - INTERVAL 24 HOUR
GROUP BY adresse_ip
HAVING COUNT(*) >= 3;

-- R17 · Délai moyen de résolution des tickets résolus ou clos (en heures)
SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.date_creation, h.date_changement)) / 60, 1) AS delai_moyen_heures
FROM ticket t
JOIN historique_ticket h ON h.id_ticket = t.id_ticket
JOIN statut_ticket s     ON s.id_statut = h.id_statut
WHERE s.code = 'resolu';

-- =====================================================================
-- D. INSÉRER, MODIFIER, SUPPRIMER
--    (transaction annulée à la fin : le jeu de données reste intact)
-- =====================================================================
START TRANSACTION;

-- R18 · Inscription d'un nouveau joueur (le hachage est produit par password_hash en PHP)
INSERT INTO utilisateur (pseudo, email, mot_de_passe_hash, id_role, newsletter)
VALUES ('NouveauJoueur', 'nouveau@exemple.test', '$2y$11$exempleDeHachageNonUtilisable.........................', 1, FALSE);

-- R19 · Ajout au panier
INSERT INTO panier_ligne (id_utilisateur, id_produit, id_plateforme)
VALUES (LAST_INSERT_ID(), 1, 1);

-- R20 · Validation d'une commande : création, ligne au prix du jour, attribution d'une clé
INSERT INTO commande (reference, id_utilisateur) VALUES ('CMD-2026-999999', 9);
SET @id_commande = LAST_INSERT_ID();

INSERT INTO ligne_commande (id_commande, id_produit, id_plateforme, prix_unitaire)
SELECT @id_commande, 3, 1, prix_final FROM v_produit_prix WHERE id_produit = 3;
SET @id_ligne = LAST_INSERT_ID();

-- la clé la plus ancienne du stock est verrouillée puis attribuée (pas de double vente)
SELECT id_cle INTO @id_cle
FROM cle_activation
WHERE id_produit = 3 AND id_plateforme = 1 AND id_ligne IS NULL
ORDER BY date_import, id_cle
LIMIT 1
FOR UPDATE;

UPDATE cle_activation
SET id_ligne = @id_ligne, date_attribution = NOW()
WHERE id_cle = @id_cle;

SELECT co.reference, l.prix_unitaire, c.code
FROM commande co
JOIN ligne_commande l ON l.id_commande = co.id_commande
JOIN cle_activation c ON c.id_ligne = l.id_ligne
WHERE co.id_commande = @id_commande;

-- R21 · Révéler une clé (uniquement si elle appartient bien au joueur connecté)
UPDATE cle_activation c
JOIN ligne_commande l ON l.id_ligne = c.id_ligne
JOIN commande co      ON co.id_commande = l.id_commande
SET c.date_revelation = NOW()
WHERE c.id_cle = 5
  AND co.id_utilisateur = 4
  AND c.date_revelation IS NULL;

-- R22 · Support : qualifier et affecter le ticket BUG-2026-0004, puis tracer le changement
UPDATE ticket
SET id_priorite = 1, id_technicien = 3, id_statut = 2
WHERE reference = 'BUG-2026-0004';

INSERT INTO historique_ticket (id_ticket, id_statut, id_auteur, commentaire)
SELECT id_ticket, 2, 3, 'Pris en charge, priorité basse (affichage)'
FROM ticket WHERE reference = 'BUG-2026-0004';

-- R23 · Changer le prix d'un produit : les commandes passées gardent leur prix
UPDATE produit SET prix = 21.99 WHERE id_produit = 1;
SELECT l.id_ligne, l.prix_unitaire AS prix_paye, p.prix AS nouveau_prix
FROM ligne_commande l JOIN produit p ON p.id_produit = l.id_produit
WHERE l.id_produit = 1;

-- R24 · Retirer un article du panier
DELETE FROM panier_ligne
WHERE id_utilisateur = 5 AND id_produit = 7 AND id_plateforme = 1;

-- R25 · RGPD : purge du journal de connexion au-delà de 30 jours
DELETE FROM tentative_connexion
WHERE date_tentative < NOW() - INTERVAL 30 DAY;

-- R26 · Intégrité : la base refuse une clé pour une plateforme non proposée
--       (Abyssal Tides n'existe pas sur PS5) -> erreur 1452 attendue, à exécuter seule
-- INSERT INTO cle_activation (code, id_produit, id_plateforme) VALUES ('TEST0-TEST0-TEST0', 3, 2);

ROLLBACK;
