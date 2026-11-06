<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Pixelwave\Core\HttpException;
use Pixelwave\Core\Router;

/** TU-09 à TU-12 : routage des URL */
final class RouterTest extends TestCase
{
    private Router $routeur;

    protected function setUp(): void
    {
        $this->routeur = new Router();
        $this->routeur->get('/boutique/{slug}', fn() => 'produit');
        $this->routeur->get('/api/v1/tickets/{reference}', fn() => 'lire');
        $this->routeur->patch('/api/v1/tickets/{reference}', fn() => 'modifier');
    }

    public function testExtraitLesParametresDeLUrl(): void
    {
        [$g, $params] = $this->routeur->trouver('GET', '/boutique/neon-drift');
        $this->assertSame('produit', $g());
        $this->assertSame(['slug' => 'neon-drift'], $params);
    }

    public function testChoisitLaRouteSelonLaMethode(): void
    {
        [$g] = $this->routeur->trouver('PATCH', '/api/v1/tickets/BUG-2026-0001');
        $this->assertSame('modifier', $g());
    }

    public function testUrlInconnueDonne404(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);
        $this->routeur->trouver('GET', '/inexistant');
    }

    public function testMauvaiseMethodeDonne405AvecMethodesAutorisees(): void
    {
        try {
            $this->routeur->trouver('DELETE', '/api/v1/tickets/BUG-2026-0001');
            $this->fail('405 attendu');
        } catch (HttpException $e) {
            $this->assertSame(405, $e->statut);
            $this->assertSame(['GET', 'PATCH'], $e->details['autorisees']);
        }
    }
}
