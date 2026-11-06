<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

final class CommandeRepository extends Repository
{
    public function lister(int $idUtilisateur): array
    {
        return $this->tous(
            'SELECT co.id_commande, co.reference, co.date_commande, co.statut,
                    COUNT(l.id_ligne) AS nb_articles, SUM(l.prix_unitaire) AS montant_total
             FROM commande co
             JOIN ligne_commande l ON l.id_commande = co.id_commande
             WHERE co.id_utilisateur = ?
             GROUP BY co.id_commande, co.reference, co.date_commande, co.statut
             ORDER BY co.date_commande DESC',
            [$idUtilisateur]
        );
    }

    public function trouver(string $reference, int $idUtilisateur): ?array
    {
        $commande = $this->un(
            'SELECT id_commande, reference, date_commande, statut FROM commande WHERE reference = ? AND id_utilisateur = ?',
            [$reference, $idUtilisateur]
        );
        if ($commande === null) {
            return null;
        }
        $commande['lignes'] = $this->tous(
            'SELECT p.titre, pf.code AS plateforme, l.prix_unitaire, c.id_cle
             FROM ligne_commande l
             JOIN produit p    ON p.id_produit = l.id_produit
             JOIN plateforme pf ON pf.id_plateforme = l.id_plateforme
             LEFT JOIN cle_activation c ON c.id_ligne = l.id_ligne
             WHERE l.id_commande = ?',
            [$commande['id_commande']]
        );
        $commande['montant_total'] = number_format(array_sum(array_column($commande['lignes'], 'prix_unitaire')), 2, '.', '');
        return $commande;
    }
}
