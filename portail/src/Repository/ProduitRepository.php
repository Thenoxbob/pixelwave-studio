<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

final class ProduitRepository extends Repository
{
    private const SELECT = "
        SELECT p.id_produit, p.type, p.titre, p.slug, p.accroche, p.description, p.date_sortie, p.visuel, p.actif,
               g.libelle AS genre, parent.titre AS jeu_parent, parent.slug AS jeu_parent_slug,
               v.prix_base, v.remise_pct, v.prix_final,
               (SELECT GROUP_CONCAT(pf.code ORDER BY pf.id_plateforme SEPARATOR ',')
                  FROM produit_plateforme pp JOIN plateforme pf ON pf.id_plateforme = pp.id_plateforme
                 WHERE pp.id_produit = p.id_produit) AS plateformes
        FROM produit p
        JOIN v_produit_prix v    ON v.id_produit = p.id_produit
        LEFT JOIN genre g        ON g.id_genre = p.id_genre
        LEFT JOIN produit parent ON parent.id_produit = p.id_jeu_parent";

    public const TRIS = ['nouveautes', 'prix_croissant', 'prix_decroissant', 'titre'];

    /**
     * Catalogue filtré.
     * $filtres : type (jeu|dlc|promo), plateforme (code), q (recherche), tri
     */
    public function lister(array $filtres = []): array
    {
        $where = ['p.actif = TRUE'];
        $params = [];

        $type = $filtres['type'] ?? '';
        if ($type === 'jeu' || $type === 'dlc') {
            $where[] = 'p.type = ?';
            $params[] = $type;
        } elseif ($type === 'promo') {
            $where[] = 'v.remise_pct > 0';
        }
        if (!empty($filtres['plateforme'])) {
            $where[] = 'EXISTS (SELECT 1 FROM produit_plateforme pp JOIN plateforme pf ON pf.id_plateforme = pp.id_plateforme
                                WHERE pp.id_produit = p.id_produit AND pf.code = ?)';
            $params[] = $filtres['plateforme'];
        }
        if (!empty($filtres['q'])) {
            $where[] = 'p.titre LIKE ?';
            $params[] = '%' . addcslashes((string) $filtres['q'], '%_\\') . '%';
        }

        $ordre = match ($filtres['tri'] ?? 'nouveautes') {
            'prix_croissant'   => 'v.prix_final ASC, p.titre',
            'prix_decroissant' => 'v.prix_final DESC, p.titre',
            'titre'            => 'p.titre',
            default            => 'p.date_sortie DESC, p.titre',
        };

        return $this->tous(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . $ordre, $params);
    }

    public function trouverParSlug(string $slug): ?array
    {
        return $this->un(self::SELECT . ' WHERE p.slug = ? AND p.actif = TRUE', [$slug]);
    }

    public function trouverParId(int $id): ?array
    {
        return $this->un(self::SELECT . ' WHERE p.id_produit = ? AND p.actif = TRUE', [$id]);
    }

    /** Plateformes proposées pour un produit avec le stock de clés restant. */
    public function offres(int $idProduit): array
    {
        return $this->tous(
            'SELECT pf.id_plateforme, pf.code, pf.nom, COUNT(c.id_cle) AS stock
             FROM produit_plateforme pp
             JOIN plateforme pf ON pf.id_plateforme = pp.id_plateforme
             LEFT JOIN cle_activation c ON c.id_produit = pp.id_produit AND c.id_plateforme = pp.id_plateforme
                                       AND c.id_ligne IS NULL
             WHERE pp.id_produit = ?
             GROUP BY pf.id_plateforme, pf.code, pf.nom
             ORDER BY pf.id_plateforme',
            [$idProduit]
        );
    }

    public function dlcs(int $idJeu): array
    {
        return $this->tous(self::SELECT . ' WHERE p.id_jeu_parent = ? AND p.actif = TRUE ORDER BY p.date_sortie DESC', [$idJeu]);
    }

    public function enPromotion(int $limite = 4): array
    {
        return array_slice($this->lister(['type' => 'promo']), 0, $limite);
    }

    /** Produit mis en avant sur l'accueil : le DLC ou jeu sorti le plus récemment. */
    public function aLaUne(): ?array
    {
        return $this->un(self::SELECT . ' WHERE p.actif = TRUE AND p.date_sortie <= CURRENT_DATE
                                         ORDER BY p.date_sortie DESC LIMIT 1');
    }

    public function plateformes(): array
    {
        return $this->tous('SELECT id_plateforme, code, nom FROM plateforme ORDER BY id_plateforme');
    }

    public function plateformeParCode(string $code): ?array
    {
        return $this->un('SELECT id_plateforme, code, nom FROM plateforme WHERE code = ?', [$code]);
    }

    /** Jeux (pas les DLC) pour la liste déroulante du signalement de bug. */
    public function jeuxEtDlc(): array
    {
        return $this->tous('SELECT id_produit, titre, type FROM produit WHERE actif = TRUE ORDER BY titre');
    }
}
