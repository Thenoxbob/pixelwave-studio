<?php
declare(strict_types=1);

namespace Pixelwave\Core;

/** Accès en lecture à la configuration (config/config.php). */
final class Config
{
    private static array $valeurs = [];

    public static function charger(array $valeurs): void
    {
        self::$valeurs = $valeurs;
    }

    /** Lecture avec notation pointée : Config::get('db.host'). */
    public static function get(string $cle, mixed $defaut = null): mixed
    {
        $valeur = self::$valeurs;
        foreach (explode('.', $cle) as $morceau) {
            if (!is_array($valeur) || !array_key_exists($morceau, $valeur)) {
                return $defaut;
            }
            $valeur = $valeur[$morceau];
        }
        return $valeur;
    }
}
