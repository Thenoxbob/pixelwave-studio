<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pixelwave\Core\HttpException;
use Pixelwave\Repository\BibliothequeRepository;
use Pixelwave\Repository\PanierRepository;
use Pixelwave\Repository\ProduitRepository;
use Pixelwave\Service\BoutiqueService;
use Pixelwave\Tests\BaseDeTest;

/** TI-01 à TI-10 : boutique, panier, commande et clés (base MariaDB réelle) */
final class BoutiqueTest extends TestCase
{
    private const LEA = 4;          // possède Neon Drift PC, Livrées rétro PC, Circuit Nocturne PC, Abyssal Tides PC
    private const NOOB = 9;         // aucun achat
    private const DRIFTKING = 5;    // commande remboursée sur Tiny Forge PC

    private BoutiqueService $boutique;

    protected function setUp(): void
    {
        BaseDeTest::reinitialiser();
        $this->boutique = new BoutiqueService();
    }

    private function codeErreur(callable $action): string
    {
        try {
            $action();
        } catch (HttpException $e) {
            return $e->statut . ' ' . $e->codeErreur;
        }
        $this->fail('Une HttpException était attendue');
    }

    public function testCatalogueNAfficheQueLesProduitsEnVente(): void
    {
        $titres = array_column((new ProduitRepository())->lister(), 'titre');
        $this->assertCount(8, $titres);
        $this->assertFalse(in_array('Tiny Forge : Pack de démarrage', $titres, true));
    }

    public function testFiltrePromotionEtPrixRemise(): void
    {
        $promos = (new ProduitRepository())->lister(['type' => 'promo']);
        // tri par défaut : nouveautés (Atelier d'hiver sorti après Abyssal Tides)
        $this->assertSame(["Tiny Forge : Atelier d'hiver", 'Abyssal Tides'], array_column($promos, 'titre'));
        $this->assertSame(['5.59', '17.49'], array_column($promos, 'prix_final'));
    }

    public function testRechercheNeTraitePasLesJokersSql(): void
    {
        $this->assertCount(0, (new ProduitRepository())->lister(['q' => '%']));
        $this->assertCount(3, (new ProduitRepository())->lister(['q' => 'neon']));
    }

    public function testAjoutAuPanierPuisCommandeAttribueUneCle(): void
    {
        $this->boutique->ajouterAuPanier(self::NOOB, 3, 'PC');           // Abyssal Tides, en promotion
        $stockAvant = (int) BaseDeTest::valeur('SELECT COUNT(*) FROM cle_activation WHERE id_produit = 3 AND id_ligne IS NULL');

        $reference = $this->boutique->validerCommande(self::NOOB);

        $this->assertMatchesRegularExpression('/^CMD-\d{4}-\d{6}$/', $reference);
        $this->assertSame(0, (new PanierRepository())->compter(self::NOOB), 'le panier est vidé');
        $this->assertSame($stockAvant - 1, (int) BaseDeTest::valeur('SELECT COUNT(*) FROM cle_activation WHERE id_produit = 3 AND id_ligne IS NULL'));
        $this->assertSame('17.49', BaseDeTest::valeur('SELECT l.prix_unitaire FROM ligne_commande l JOIN commande c USING (id_commande) WHERE c.reference = ?', [$reference]), 'prix remisé figé');
        $biblio = (new BibliothequeRepository())->contenu(self::NOOB);
        $this->assertCount(1, $biblio);
        $this->assertNull($biblio[0]['code'], 'la clé reste masquée tant qu\'elle n\'est pas révélée');
    }

    public function testPlateformeNonProposeeEstRefusee(): void
    {
        $this->assertSame('400 plateforme_invalide', $this->codeErreur(fn() => $this->boutique->ajouterAuPanier(self::NOOB, 3, 'PS5')));
    }

    public function testRuptureDeStockEstSignalee(): void
    {
        $this->assertSame('409 stock_epuise', $this->codeErreur(fn() => $this->boutique->ajouterAuPanier(self::NOOB, 4, 'SWITCH')));
    }

    public function testProduitDejaPossedeEstRefuse(): void
    {
        $this->assertSame('409 deja_possede', $this->codeErreur(fn() => $this->boutique->ajouterAuPanier(self::LEA, 1, 'PC')));
    }

    public function testProduitRetireDeLaVenteEstIntrouvable(): void
    {
        $this->assertSame('404 produit_introuvable', $this->codeErreur(fn() => $this->boutique->ajouterAuPanier(self::NOOB, 9, 'PC')));
    }

    public function testCommandeAnnuleeEntierementSiUneCleManque(): void
    {
        $this->boutique->ajouterAuPanier(self::NOOB, 1, 'PC');
        $this->boutique->ajouterAuPanier(self::NOOB, 8, 'PS5');          // dernière clé en stock
        $stockNeonAvant = (int) BaseDeTest::valeur('SELECT COUNT(*) FROM cle_activation WHERE id_produit = 1 AND id_plateforme = 1 AND id_ligne IS NULL');
        // un autre joueur achète la dernière clé entre l'ajout au panier et le paiement
        BaseDeTest::pdo()->exec('DELETE FROM cle_activation WHERE id_produit = 8 AND id_plateforme = 2 AND id_ligne IS NULL');

        $this->assertSame('409 stock_epuise', $this->codeErreur(fn() => $this->boutique->validerCommande(self::NOOB)));

        // transaction annulée : aucune commande, panier intact, aucune clé consommée
        $this->assertSame(0, (int) BaseDeTest::valeur('SELECT COUNT(*) FROM commande WHERE id_utilisateur = ?', [self::NOOB]));
        $this->assertSame(2, (new PanierRepository())->compter(self::NOOB));
        $this->assertSame($stockNeonAvant, (int) BaseDeTest::valeur('SELECT COUNT(*) FROM cle_activation WHERE id_produit = 1 AND id_plateforme = 1 AND id_ligne IS NULL'));
    }

    public function testCommandeRembourseeNeDonnePlusAccesAuJeu(): void
    {
        $titres = array_column((new BibliothequeRepository())->contenu(self::DRIFTKING), 'titre');
        $this->assertSame(['Neon Drift'], $titres);
        // ...et le joueur peut donc racheter Tiny Forge sur PC
        $this->assertGreaterThan(0, $this->boutique->ajouterAuPanier(self::DRIFTKING, 2, 'PC'));
    }

    public function testPanierVideNePeutPasEtreValide(): void
    {
        $this->assertSame('400 panier_vide', $this->codeErreur(fn() => $this->boutique->validerCommande(self::NOOB)));
    }
}
