<?php
declare(strict_types=1);

namespace Pixelwave\Core;

/** Lecture de la requête HTTP courante. */
final class Request
{
    private ?array $json = null;

    public function __construct(
        public readonly string $methode,
        public readonly string $chemin,
        public readonly array $query = [],
        public readonly array $post = [],
        private readonly array $entetes = [],
        private readonly string $corps = '',
        public readonly string $ip = '0.0.0.0'
    ) {
    }

    public static function depuisGlobales(): self
    {
        $chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $chemin = '/' . trim($chemin, '/');

        $entetes = [];
        foreach ($_SERVER as $cle => $valeur) {
            if (str_starts_with($cle, 'HTTP_')) {
                $entetes[strtolower(str_replace('_', '-', substr($cle, 5)))] = $valeur;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $entetes['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        // Derrière Apache, l'en-tête Authorization peut arriver sous ce nom
        if (!isset($entetes['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $entetes['authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $chemin,
            $_GET,
            $_POST,
            $entetes,
            (string) file_get_contents('php://input'),
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        );
    }

    public function entete(string $nom): ?string
    {
        return $this->entetes[strtolower($nom)] ?? null;
    }

    public function estApi(): bool
    {
        return str_starts_with($this->chemin, '/api/');
    }

    /** Corps JSON décodé. Erreur 400 si le JSON est invalide. */
    public function json(): array
    {
        if ($this->json === null) {
            if (trim($this->corps) === '') {
                $this->json = [];
            } else {
                $donnees = json_decode($this->corps, true);
                if (!is_array($donnees)) {
                    throw new HttpException(400, 'json_invalide', 'Le corps de la requête doit être un objet JSON valide.');
                }
                $this->json = $donnees;
            }
        }
        return $this->json;
    }

    public function param(string $nom, mixed $defaut = null): mixed
    {
        $valeur = $this->query[$nom] ?? $defaut;
        return is_string($valeur) ? trim($valeur) : $valeur;
    }

    public function champ(string $nom, mixed $defaut = null): mixed
    {
        $valeur = $this->post[$nom] ?? $defaut;
        return is_string($valeur) ? trim($valeur) : $valeur;
    }
}
