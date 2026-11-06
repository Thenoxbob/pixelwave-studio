<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pixelwave\Core\HttpException;
use Pixelwave\Repository\TicketRepository;
use Pixelwave\Repository\UtilisateurRepository;
use Pixelwave\Service\DiscordNotifier;
use Pixelwave\Service\TicketService;
use Pixelwave\Tests\BaseDeTest;

/** TI-20 à TI-27 : signalement de bugs et traitement ITIL */
final class TicketTest extends TestCase
{
    private TicketService $service;
    private array $lea;
    private array $tom;
    private array $karim;

    protected function setUp(): void
    {
        BaseDeTest::reinitialiser();
        $this->service = new TicketService(discord: new DiscordNotifier(''));   // pas d'appel réseau en test
        $u = new UtilisateurRepository();
        $this->lea = $u->trouverParId(4);
        $this->tom = $u->trouverParId(5);
        $this->karim = $u->trouverParId(2);
    }

    private function signalement(array $surcharge = []): array
    {
        return $surcharge + [
            'id_produit' => '1', 'plateforme' => 'PC', 'version_jeu' => '1.4.0', 'frequence' => 'parfois',
            'titre' => 'Freeze dans le tunnel', 'description' => "L'image se fige trois secondes dans le tunnel du circuit Quai 9.",
        ];
    }

    public function testSignalementCreeUnTicketNouveauAvecHistorique(): void
    {
        $ref = $this->service->signaler($this->lea, $this->signalement());
        $this->assertMatchesRegularExpression('/^BUG-\d{4}-\d{4}$/', $ref);
        $t = (new TicketRepository())->trouverParReference($ref);
        $this->assertSame('nouveau', $t['statut']);
        $this->assertNull($t['priorite'], 'la priorité est fixée par le support, pas par le joueur');
        $this->assertCount(1, (new TicketRepository())->historique((int) $t['id_ticket']));
    }

    public function testSignalementIncompletEstRefuse(): void
    {
        try {
            $this->service->signaler($this->lea, $this->signalement(['titre' => 'Bug', 'description' => 'ça plante', 'plateforme' => 'N64', 'version_jeu' => '<b>1</b>']));
            $this->fail('Erreur attendue');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->statut);
            $this->assertSame(['titre', 'description', 'version_jeu', 'plateforme'], array_keys($e->details));
        }
    }

    public function testUnJoueurNeVoitPasLeTicketDUnAutre(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $this->service->consulter('BUG-2026-0001', $this->lea);      // ticket de DriftKing
    }

    public function testLeSupportVoitTousLesTicketsEtLesNotesInternes(): void
    {
        $t = $this->service->consulter('BUG-2026-0001', $this->karim);
        $repo = new TicketRepository();
        $this->assertCount(4, $repo->messages((int) $t['id_ticket'], true));
        $this->assertCount(3, $repo->messages((int) $t['id_ticket'], false), 'la note interne est cachée au joueur');
    }

    public function testReponseDuJoueurRelanceUnTicketEnAttente(): void
    {
        $ines = (new UtilisateurRepository())->trouverParId(6);
        $t = $this->service->consulter('BUG-2026-0003', $ines);
        $this->assertSame('attente_joueur', $t['statut']);
        $this->service->repondre($t, $ines, ['contenu' => 'Mise en veille vers 21h, version 2.1.0.']);
        $this->assertSame('en_cours', (new TicketRepository())->trouverParReference('BUG-2026-0003')['statut']);
    }

    public function testPasDeReponseSurUnTicketClos(): void
    {
        $t = $this->service->consulter('BUG-2026-0005', $this->lea);
        try {
            $this->service->repondre($t, $this->lea, ['contenu' => 'Encore moi']);
            $this->fail('409 attendu');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->statut);
        }
    }

    public function testQualificationParLeSupportEstTracee(): void
    {
        $t = $this->service->consulter('BUG-2026-0004', $this->karim);
        $this->service->traiter($t, $this->karim, ['statut' => 'en_cours', 'priorite' => 'basse', 'id_technicien' => '3', 'commentaire' => 'Affichage uniquement']);
        $apres = (new TicketRepository())->trouverParReference('BUG-2026-0004');
        $this->assertSame(['en_cours', 'basse', 'julie.qa'], [$apres['statut'], $apres['priorite'], $apres['technicien']]);
        $this->assertCount(2, (new TicketRepository())->historique((int) $t['id_ticket']));
    }

    public function testOnNePeutPasAffecterUnTicketAUnJoueur(): void
    {
        $t = $this->service->consulter('BUG-2026-0004', $this->karim);
        try {
            $this->service->traiter($t, $this->karim, ['id_technicien' => '5']);
            $this->fail('Erreur attendue');
        } catch (HttpException $e) {
            $this->assertArrayHasKey('id_technicien', $e->details);
        }
    }
}
