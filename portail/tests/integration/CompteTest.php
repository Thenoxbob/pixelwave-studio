<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pixelwave\Core\HttpException;
use Pixelwave\Repository\BibliothequeRepository;
use Pixelwave\Repository\JetonRepository;
use Pixelwave\Service\CompteService;
use Pixelwave\Tests\BaseDeTest;

/** TI-11 à TI-19 : comptes, authentification, clés et jetons */
final class CompteTest extends TestCase
{
    private const MDP = 'Pixelwave!2026';
    private CompteService $comptes;

    protected function setUp(): void
    {
        BaseDeTest::reinitialiser();
        $this->comptes = new CompteService();
    }

    private function inscription(array $surcharge = []): array
    {
        return $surcharge + [
            'pseudo' => 'NouvelleRecrue', 'email' => 'Recrue@Exemple.test',
            'mot_de_passe' => 'Un-Mot2Passe!', 'confirmation' => 'Un-Mot2Passe!', 'cgu' => '1',
        ];
    }

    public function testInscriptionHacheLeMotDePasse(): void
    {
        $id = $this->comptes->inscrire($this->inscription());
        $ligne = BaseDeTest::pdo()->query("SELECT email, mot_de_passe_hash, id_role FROM utilisateur WHERE id_utilisateur = $id")->fetch();
        $this->assertSame('recrue@exemple.test', $ligne['email'], 'e-mail normalisé en minuscules');
        $this->assertNotSame('Un-Mot2Passe!', $ligne['mot_de_passe_hash']);
        $this->assertTrue(password_verify('Un-Mot2Passe!', $ligne['mot_de_passe_hash']));
        $this->assertSame(1, (int) $ligne['id_role'], 'rôle joueur');
    }

    public function testInscriptionRefuseDoublonsEtMotDePasseFaible(): void
    {
        try {
            $this->comptes->inscrire($this->inscription(['pseudo' => 'LeaPixel', 'email' => 'lea.martin@exemple.test', 'mot_de_passe' => 'azerty', 'confirmation' => 'azerty']));
            $this->fail('Erreur de validation attendue');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->statut);
            $champs = array_keys($e->details);
            sort($champs);
            $this->assertSame(['email', 'mot_de_passe', 'pseudo'], $champs);
        }
    }

    public function testInscriptionSansAccepterLesCguEstRefusee(): void
    {
        try {
            $this->comptes->inscrire($this->inscription(['cgu' => '']));
            $this->fail('Erreur attendue');
        } catch (HttpException $e) {
            $this->assertArrayHasKey('cgu', $e->details);
        }
    }

    public function testConnexionReussieEtJournalisee(): void
    {
        $u = $this->comptes->authentifier('LEA.MARTIN@exemple.test', self::MDP, '203.0.113.50');
        $this->assertSame('LeaPixel', $u['pseudo']);
        $this->assertSame(1, (int) BaseDeTest::valeur("SELECT COUNT(*) FROM tentative_connexion WHERE adresse_ip = '203.0.113.50' AND reussie = TRUE"));
    }

    public function testMauvaisMotDePasseEtCompteInconnuOntLeMemeMessage(): void
    {
        $messages = [];
        foreach ([['lea.martin@exemple.test', 'mauvais'], ['inconnu@exemple.test', self::MDP]] as [$email, $mdp]) {
            try {
                $this->comptes->authentifier($email, $mdp, '203.0.113.51');
            } catch (HttpException $e) {
                $messages[] = $e->statut . ' ' . $e->getMessage();
            }
        }
        $this->assertCount(2, $messages);
        $this->assertSame($messages[0], $messages[1], 'pas d\'énumération des comptes');
    }

    public function testCompteDesactiveNePeutPasSeConnecter(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(401);
        $this->comptes->authentifier('paul.roux@exemple.test', self::MDP, '203.0.113.52');
    }

    public function testBlocageApresCinqEchecs(): void
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->comptes->authentifier('tom.durand@exemple.test', 'faux' . $i, '198.51.100.20');
            } catch (HttpException) {
            }
        }
        try {
            // même le bon mot de passe est refusé pendant 15 minutes
            $this->comptes->authentifier('tom.durand@exemple.test', self::MDP, '198.51.100.20');
            $this->fail('Blocage attendu');
        } catch (HttpException $e) {
            $this->assertSame(429, $e->statut);
        }
    }

    public function testUnJoueurNePeutPasReleverLaCleDUnAutre(): void
    {
        $biblio = new BibliothequeRepository();
        $this->assertNull($biblio->reveler(3, 4), 'clé 3 = DriftKing, demandée par LeaPixel');
        $cle = $biblio->reveler(5, 4);
        $this->assertSame('3V835-NUTAC-K79ZN', $cle['code']);
        $this->assertNotNull(BaseDeTest::valeur('SELECT date_revelation FROM cle_activation WHERE id_cle = 5'));
    }

    public function testJetonApiValideExpireEtRevoque(): void
    {
        $jetons = new JetonRepository();
        $this->assertSame('LeaPixel', $jetons->utilisateurPourJeton('pw_demo_LeaPixel_2026_9f8e7d6c5b4a')['pseudo']);
        $this->assertNull($jetons->utilisateurPourJeton('pw_demo_DriftKing_expire_0000'), 'expiré');
        $this->assertNull($jetons->utilisateurPourJeton('pw_demo_LeaPixel_revoque_1111'), 'révoqué');

        [$nouveau] = $jetons->creer(9, 'Test', 30);
        $this->assertMatchesRegularExpression('/^pw_[a-f0-9]{48}$/', $nouveau);
        $this->assertSame(0, (int) BaseDeTest::valeur('SELECT COUNT(*) FROM jeton_api WHERE jeton_hash = ?', [$nouveau]), 'jamais stocké en clair');
        $this->assertSame('NoobSaibot', $jetons->utilisateurPourJeton($nouveau)['pseudo']);
    }
}
