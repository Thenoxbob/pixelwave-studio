<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

final class PanierRepository extends Repository
{
    public function lignes(int $idUtilisateur): array
    {
        return $this->tous(
            'SELECT pl.id_panier_ligne, pl.id_produit, pl.id_plateforme, p.titre, p.slug, p.type, p.visuel,
                    pf.code AS plateforme_code, pf.nom AS plateforme, v.prix_base, v.remise_pct, v.prix_final
             FROM panier_ligne pl
             JOIN produit p        ON p.id_produit = pl.id_produit
             JOIN plateforme pf    ON pf.id_plateforme = pl.id_plateforme
             JOIN v_produit_prix v ON v.id_produit = pl.id_produit
             WHERE pl.id_utilisateur = ?
             ORDER BY pl.date_ajout',
            [$idUtilisateur]
        );
    }

    public function compter(int $idUtilisateur): int
    {
        return (int) ($this->un('SELECT COUNT(*) AS n FROM panier_ligne WHERE id_utilisateur = ?', [$idUtilisateur])['n'] ?? 0);
    }

    public function contient(int $idUtilisateur, int $idProduit, int $idPlateforme): bool
    {
        return $this->un('SELECT 1 FROM panier_ligne WHERE id_utilisateur = ? AND id_produit = ? AND id_plateforme = ?',
            [$idUtilisateur, $idProduit, $idPlateforme]) !== null;
    }

    public function ajouter(int $idUtilisateur, int $idProduit, int $idPlateforme): int
    {
        $this->executer('INSERT INTO panier_ligne (id_utilisateur, id_produit, id_plateforme) VALUES (?, ?, ?)',
            [$idUtilisateur, $idProduit, $idPlateforme]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** Retire une ligne uniquement si elle appartient à l'utilisateur. */
    public function retirer(int $idUtilisateur, int $idLigne): bool
    {
        return $this->executer('DELETE FROM panier_ligne WHERE id_panier_ligne = ? AND id_utilisateur = ?',
            [$idLigne, $idUtilisateur]) === 1;
    }

    public function vider(int $idUtilisateur): void
    {
        $this->executer('DELETE FROM panier_ligne WHERE id_utilisateur = ?', [$idUtilisateur]);
    }
}
