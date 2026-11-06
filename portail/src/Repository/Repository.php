<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

use PDO;
use Pixelwave\Core\Database;

/** Base commune : chaque repository regroupe les requêtes SQL d'une ressource. */
abstract class Repository
{
    protected function pdo(): PDO
    {
        return Database::pdo();
    }

    protected function un(string $sql, array $params = []): ?array
    {
        $req = $this->pdo()->prepare($sql);
        $req->execute($params);
        $ligne = $req->fetch();
        return $ligne === false ? null : $ligne;
    }

    protected function tous(string $sql, array $params = []): array
    {
        $req = $this->pdo()->prepare($sql);
        $req->execute($params);
        return $req->fetchAll();
    }

    protected function executer(string $sql, array $params = []): int
    {
        $req = $this->pdo()->prepare($sql);
        $req->execute($params);
        return $req->rowCount();
    }
}
