<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

/** Jeux possédés et clés d'activation d'un joueur. */
final class BibliothequeRepository extends Repository
{
    /** Produits achetés (commandes payées) avec leur clé. Le code n'est renvoyé que s'il a été révélé. */
    public function contenu(int $idUtilisateur): array
    {
        return $this->tous(
            "SELECT c.id_cle, p.id_produit, p.titre, p.slug, p.type, p.visuel, p.date_sortie,
                    pf.code AS plateforme_code, pf.nom AS plateforme,
                    co.reference AS commande, co.date_commande,
                    c.date_revelation,
                    CASE WHEN c.date_revelation IS NULL THEN NULL ELSE c.code END AS code,
                    CONCAT('•••••-•••••-', RIGHT(c.code, 5)) AS code_masque
             FROM commande co
             JOIN ligne_commande l ON l.id_commande = co.id_commande
             JOIN cle_activation c ON c.id_ligne = l.id_ligne
             JOIN produit p        ON p.id_produit = l.id_produit
             JOIN plateforme pf    ON pf.id_plateforme = l.id_plateforme
             WHERE co.id_utilisateur = ? AND co.statut = 'payee'
             ORDER BY co.date_commande DESC, p.titre",
            [$idUtilisateur]
        );
    }

    public function possede(int $idUtilisateur, int $idProduit, int $idPlateforme): bool
    {
        return $this->un(
            "SELECT 1 FROM commande co JOIN ligne_commande l ON l.id_commande = co.id_commande
             WHERE co.id_utilisateur = ? AND co.statut = 'payee' AND l.id_produit = ? AND l.id_plateforme = ?",
            [$idUtilisateur, $idProduit, $idPlateforme]
        ) !== null;
    }

    /**
     * Révèle une clé et retourne son code.
     * Retourne null si la clé n'existe pas ou n'appartient pas au joueur.
     */
    public function reveler(int $idCle, int $idUtilisateur): ?array
    {
        $cle = $this->un(
            "SELECT c.id_cle, c.code, c.date_revelation, p.titre, pf.nom AS plateforme
             FROM cle_activation c
             JOIN ligne_commande l ON l.id_ligne = c.id_ligne
             JOIN commande co      ON co.id_commande = l.id_commande
             JOIN produit p        ON p.id_produit = c.id_produit
             JOIN plateforme pf    ON pf.id_plateforme = c.id_plateforme
             WHERE c.id_cle = ? AND co.id_utilisateur = ? AND co.statut = 'payee'",
            [$idCle, $idUtilisateur]
        );
        if ($cle === null) {
            return null;
        }
        if ($cle['date_revelation'] === null) {
            $this->executer('UPDATE cle_activation SET date_revelation = NOW() WHERE id_cle = ? AND date_revelation IS NULL', [$idCle]);
            $cle['date_revelation'] = date('Y-m-d H:i:s');
        }
        return $cle;
    }
}
