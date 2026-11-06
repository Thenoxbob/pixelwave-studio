<?php
declare(strict_types=1);

namespace Pixelwave\Core;

use PDO;

/** Connexion PDO unique à MariaDB. */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Config::get('db.host'),
                (int) Config::get('db.port', 3306),
                Config::get('db.name')
            );
            self::$pdo = new PDO($dsn, Config::get('db.user'), Config::get('db.password'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // toute erreur SQL lève une exception
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                   // vraies requêtes préparées côté serveur
            ]);
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    /** Utilisé par les tests pour injecter ou réinitialiser la connexion. */
    public static function definir(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }
}
