<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pixelwave\Core\HttpException;
use Pixelwave\Core\Validator;

/** TU-01 à TU-08 : règles de validation des saisies */
final class ValidatorTest extends TestCase
{
    public function testChampObligatoireVideEstRefuse(): void
    {
        $v = (new Validator(['pseudo' => '   ']))->requis('pseudo');
        $this->assertFalse($v->estValide());
        $this->assertArrayHasKey('pseudo', $v->erreurs());
    }

    public function testEmailInvalideEstRefuse(): void
    {
        $v = (new Validator(['email' => 'lea.martin@']))->email('email');
        $this->assertSame('Adresse e-mail invalide.', $v->erreurs()['email']);
    }

    public function testEmailValideEstAccepte(): void
    {
        $this->assertTrue((new Validator(['email' => 'lea.martin@exemple.test']))->email('email')->estValide());
    }

    public function testLongueurCompteLesCaracteresAccentues(): void
    {
        // « Élodie » = 6 caractères mais 7 octets : la règle 3..6 doit passer
        $this->assertTrue((new Validator(['p' => 'Élodie']))->longueur('p', 3, 6)->estValide());
        $this->assertFalse((new Validator(['p' => 'ab']))->longueur('p', 3, 6)->estValide());
    }

    public function testPolitiqueDeMotDePasse(): void
    {
        $this->assertFalse(Validator::motDePasseRobuste('court1!'), 'trop court');
        $this->assertFalse(Validator::motDePasseRobuste('motdepasselong'), 'sans chiffre ni caractère spécial');
        $this->assertFalse(Validator::motDePasseRobuste('motdepasse123'), 'sans caractère spécial');
        $this->assertFalse(Validator::motDePasseRobuste('1234567890!!'), 'sans lettre');
        $this->assertTrue(Validator::motDePasseRobuste('Pixelwave!2026'));
        $this->assertTrue(Validator::motDePasseRobuste('été-à-la-mer-42'), 'lettres accentuées acceptées');
    }

    public function testValeurHorsListeEstRefusee(): void
    {
        $v = (new Validator(['frequence' => 'souvent']))->dansListe('frequence', ['toujours', 'parfois', 'une_fois']);
        $this->assertFalse($v->estValide());
    }

    public function testIdentifiantDoitEtreEntierPositif(): void
    {
        $this->assertFalse((new Validator(['id' => '-3']))->entierPositif('id')->estValide());
        $this->assertFalse((new Validator(['id' => '2 OR 1=1']))->entierPositif('id')->estValide());
        $this->assertTrue((new Validator(['id' => '12']))->entierPositif('id')->estValide());
    }

    public function testVerifierLeveUneErreur400AvecLeDetail(): void
    {
        try {
            (new Validator([]))->requis('titre')->requis('description')->verifier();
            $this->fail('HttpException attendue');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->statut);
            $this->assertSame('donnees_invalides', $e->codeErreur);
            $this->assertCount(2, $e->details);
        }
    }
}
