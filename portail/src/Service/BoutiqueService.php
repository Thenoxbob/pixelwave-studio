<?php
declare(strict_types=1);

namespace Pixelwave\Service;

use Pixelwave\Core\Database;
use Pixelwave\Core\HttpException;
use Pixelwave\Repository\BibliothequeRepository;
use Pixelwave\Repository\PanierRepository;
use Pixelwave\Repository\ProduitRepository;

/** Panier et validation de commande avec attribution des clés d'activation. */
final class BoutiqueService
{
    public function __construct(
        private readonly ProduitRepository $produits = new ProduitRepository(),
        private readonly PanierRepository $panier = new PanierRepository(),
        private readonly BibliothequeRepository $bibliotheque = new BibliothequeRepository()
    ) {
    }

    /** Ajoute un produit au panier pour une plateforme (code PC, PS5, SWITCH...). */
    public function ajouterAuPanier(int $idUtilisateur, int $idProduit, string $codePlateforme): int
    {
        $produit = $this->produits->trouverParId($idProduit);
        if ($produit === null) {
            throw new HttpException(404, 'produit_introuvable', "Ce produit n'existe pas ou n'est plus en vente.");
        }
        $offre = null;
        foreach ($this->produits->offres($idProduit) as $o) {
            if ($o['code'] === $codePlateforme) {
                $offre = $o;
            }
        }
        if ($offre === null) {
            throw new HttpException(400, 'plateforme_invalide', "Ce produit n'est pas disponible sur cette plateforme.", ['plateforme' => 'Plateforme non proposée.']);
        }
        if ((int) $offre['stock'] === 0) {
            throw new HttpException(409, 'stock_epuise', 'Plus aucune clé disponible pour cette plateforme. Reviens bientôt !');
        }
        if ($this->bibliotheque->possede($idUtilisateur, $idProduit, (int) $offre['id_plateforme'])) {
            throw new HttpException(409, 'deja_possede', 'Tu possèdes déjà ce produit sur cette plateforme.');
        }
        if ($this->panier->contient($idUtilisateur, $idProduit, (int) $offre['id_plateforme'])) {
            throw new HttpException(409, 'deja_dans_panier', 'Ce produit est déjà dans ton panier.');
        }
        return $this->panier->ajouter($idUtilisateur, $idProduit, (int) $offre['id_plateforme']);
    }

    /**
     * Transforme le panier en commande payée.
     * Tout se fait dans une transaction : si une seule clé manque, rien n'est enregistré.
     * Les clés sont verrouillées (SELECT ... FOR UPDATE) : deux joueurs ne peuvent pas
     * recevoir la même clé, même s'ils valident au même instant.
     * Le paiement est simulé (démonstration) : aucune donnée bancaire ne transite par le portail.
     */
    public function validerCommande(int $idUtilisateur): string
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $req = $pdo->prepare(
                'SELECT pl.id_panier_ligne, pl.id_produit, pl.id_plateforme, p.titre, p.actif, v.prix_final
                 FROM panier_ligne pl
                 JOIN produit p        ON p.id_produit = pl.id_produit
                 JOIN v_produit_prix v ON v.id_produit = pl.id_produit
                 WHERE pl.id_utilisateur = ?
                 FOR UPDATE'
            );
            $req->execute([$idUtilisateur]);
            $lignes = $req->fetchAll();
            if ($lignes === []) {
                throw new HttpException(400, 'panier_vide', 'Ton panier est vide.');
            }

            $pdo->prepare('INSERT INTO commande (reference, id_utilisateur) VALUES (?, ?)')
                ->execute(['TMP-' . bin2hex(random_bytes(6)), $idUtilisateur]);
            $idCommande = (int) $pdo->lastInsertId();
            $reference = sprintf('CMD-%s-%06d', date('Y'), $idCommande);
            $pdo->prepare('UPDATE commande SET reference = ? WHERE id_commande = ?')->execute([$reference, $idCommande]);

            $insLigne = $pdo->prepare('INSERT INTO ligne_commande (id_commande, id_produit, id_plateforme, prix_unitaire) VALUES (?, ?, ?, ?)');
            $chercheCle = $pdo->prepare(
                'SELECT id_cle FROM cle_activation
                 WHERE id_produit = ? AND id_plateforme = ? AND id_ligne IS NULL
                 ORDER BY date_import, id_cle LIMIT 1 FOR UPDATE'
            );
            $attribueCle = $pdo->prepare('UPDATE cle_activation SET id_ligne = ?, date_attribution = NOW() WHERE id_cle = ?');

            $manquants = [];
            foreach ($lignes as $l) {
                if (!$l['actif'] || $this->bibliotheque->possede($idUtilisateur, (int) $l['id_produit'], (int) $l['id_plateforme'])) {
                    $manquants[] = $l['titre'];
                    continue;
                }
                $chercheCle->execute([$l['id_produit'], $l['id_plateforme']]);
                $idCle = $chercheCle->fetchColumn();
                if ($idCle === false) {
                    $manquants[] = $l['titre'];
                    continue;
                }
                $insLigne->execute([$idCommande, $l['id_produit'], $l['id_plateforme'], $l['prix_final']]);
                $attribueCle->execute([(int) $pdo->lastInsertId(), $idCle]);
            }

            if ($manquants !== []) {
                throw new HttpException(409, 'stock_epuise',
                    'Certains articles ne sont plus disponibles : ' . implode(', ', $manquants) . '. Retire-les du panier pour continuer.',
                    ['articles' => $manquants]);
            }

            $pdo->prepare('DELETE FROM panier_ligne WHERE id_utilisateur = ?')->execute([$idUtilisateur]);
            $pdo->commit();
            return $reference;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
