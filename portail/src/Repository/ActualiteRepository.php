<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

final class ActualiteRepository extends Repository
{
    private const SELECT = '
        SELECT a.id_actualite, a.titre, a.slug, a.chapo, a.contenu, a.date_publication,
               c.libelle AS categorie, u.pseudo AS auteur, p.titre AS produit, p.slug AS produit_slug, p.visuel
        FROM actualite a
        JOIN categorie_actualite c ON c.id_categorie = a.id_categorie
        JOIN utilisateur u         ON u.id_utilisateur = a.id_auteur
        LEFT JOIN produit p        ON p.id_produit = a.id_produit';

    /** Seules les actualités publiées sont visibles : les brouillons restent internes. */
    public function lister(int $limite = 50): array
    {
        return $this->tous(self::SELECT . ' WHERE a.publiee = TRUE ORDER BY a.date_publication DESC LIMIT ' . max(1, $limite));
    }

    public function trouverParSlug(string $slug): ?array
    {
        return $this->un(self::SELECT . ' WHERE a.slug = ? AND a.publiee = TRUE', [$slug]);
    }

    public function trouverParId(int $id): ?array
    {
        return $this->un(self::SELECT . ' WHERE a.id_actualite = ? AND a.publiee = TRUE', [$id]);
    }
}
