<?php
declare(strict_types=1);

namespace Pixelwave\Core;

/**
 * Routeur minimal : associe « méthode + motif d'URL » à un gestionnaire.
 * Les segments {nom} deviennent des paramètres : /boutique/{slug}
 */
final class Router
{
    private array $routes = [];

    public function ajouter(string $methode, string $motif, callable $gestionnaire): void
    {
        $regex = preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $motif);
        $this->routes[] = [$methode, '#^' . $regex . '$#', $gestionnaire];
    }

    public function get(string $motif, callable $g): void    { $this->ajouter('GET', $motif, $g); }
    public function post(string $motif, callable $g): void   { $this->ajouter('POST', $motif, $g); }
    public function patch(string $motif, callable $g): void  { $this->ajouter('PATCH', $motif, $g); }
    public function delete(string $motif, callable $g): void { $this->ajouter('DELETE', $motif, $g); }

    /**
     * Retourne [gestionnaire, paramètres].
     * 404 si aucune URL ne correspond, 405 si l'URL existe avec une autre méthode.
     */
    public function trouver(string $methode, string $chemin): array
    {
        $methodesPossibles = [];
        foreach ($this->routes as [$m, $regex, $gestionnaire]) {
            if (preg_match($regex, $chemin, $correspondances)) {
                if ($m === $methode) {
                    $params = array_filter($correspondances, 'is_string', ARRAY_FILTER_USE_KEY);
                    return [$gestionnaire, array_map('rawurldecode', $params)];
                }
                $methodesPossibles[] = $m;
            }
        }
        if ($methodesPossibles) {
            throw new HttpException(405, 'methode_non_autorisee', 'Méthode non autorisée sur cette ressource.', ['autorisees' => $methodesPossibles]);
        }
        throw new HttpException(404, 'introuvable', 'Ressource introuvable.');
    }
}
