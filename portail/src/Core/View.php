<?php
declare(strict_types=1);

namespace Pixelwave\Core;

/** Rendu des templates PHP dans le gabarit commun. */
final class View
{
    private const DOSSIER = __DIR__ . '/../../templates/';

    public static function rendre(string $template, array $donnees = [], int $statut = 200): string
    {
        http_response_code($statut);
        $contenu = self::partiel($template, $donnees);
        return self::partiel('layout', ['corpsPage' => $contenu] + $donnees);
    }

    public static function partiel(string $template, array $donnees = []): string
    {
        extract($donnees, EXTR_SKIP);
        ob_start();
        require self::DOSSIER . $template . '.php';
        return (string) ob_get_clean();
    }

    public static function rediriger(string $url, int $statut = 303): string
    {
        // uniquement des chemins internes : évite les redirections ouvertes
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = '/';
        }
        http_response_code($statut);
        header('Location: ' . $url);
        return '';
    }

    public static function json(mixed $donnees, int $statut = 200): string
    {
        http_response_code($statut);
        header('Content-Type: application/json; charset=utf-8');
        if ($statut === 204) {
            return '';
        }
        return json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
