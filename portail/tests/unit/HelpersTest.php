<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pixelwave\Repository\JetonRepository;

/** TU-13 à TU-17 : fonctions utilitaires et sécurité de l'affichage */
final class HelpersTest extends TestCase
{
    public function testEchappementEmpecheLInjectionHtml(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
        $this->assertSame('L&#039;Atelier d&#039;hiver', e("L'Atelier d'hiver"));
    }

    public function testFormatDesPrix(): void
    {
        $this->assertSame('17,49 €', prix('17.49'));
        $this->assertSame('1 250,00 €', prix(1250));
    }

    public function testFormatDesDates(): void
    {
        $this->assertSame('14 oct. 2026', date_fr('2026-10-14 10:00:00'));
        $this->assertSame('2 mars 2026 à 20h31', date_fr('2026-03-02 20:31:00', true));
        $this->assertSame('—', date_fr(null));
    }

    public function testExtraitNeCoupePasUnCaractere(): void
    {
        $this->assertSame('Été…', extrait('Été chaud', 3));
        $this->assertSame('court', extrait('court', 10));
    }

    public function testEmpreinteDesJetonsApi(): void
    {
        $h = JetonRepository::empreinte('pw_demo_LeaPixel_2026_9f8e7d6c5b4a');
        $this->assertSame(64, strlen($h));
        $this->assertSame('a33d5c268e01055527dbd3e5c88a24cc93e2d4bf7b305a9f90fd543a336e1ee5', $h);
    }
}
