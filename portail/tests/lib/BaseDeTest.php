<?php
declare(strict_types=1);

namespace Pixelwave\Tests;

use PDO;
use Pixelwave\Core\Auth;
use Pixelwave\Core\Config;
use Pixelwave\Core\Database;

/**
 * Recrée la base de test à partir des scripts du dossier database/
 * (01_creation.sql + 02_jeu_de_donnees.sql) : chaque test part du même état.
 */
final class BaseDeTest
{
    public static function reinitialiser(): void
    {
        $admin = Config::get('db_admin');
        $nomBase = Config::get('db.name');
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $admin['host'], $admin['port']),
            $admin['user'],
            $admin['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dossier = dirname(__DIR__, 3) . '/database/';
        foreach (['01_creation.sql', '02_jeu_de_donnees.sql'] as $fichier) {
            $sql = file_get_contents($dossier . $fichier);
            // la base de test porte un autre nom que la base de production
            $sql = preg_replace('/\bpixelwave\b(?![._\-@])/', $nomBase, $sql);
            $pdo->exec($sql);
        }
        Database::definir(null);   // nouvelle connexion applicative
        Auth::definir(null);
    }

    public static function pdo(): PDO
    {
        return Database::pdo();
    }

    /** Raccourci : une valeur scalaire. */
    public static function valeur(string $sql, array $params = []): mixed
    {
        $req = Database::pdo()->prepare($sql);
        $req->execute($params);
        return $req->fetchColumn();
    }
}
