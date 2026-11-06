<?php
declare(strict_types=1);

namespace Pixelwave\Core;

use RuntimeException;

/**
 * Erreur métier associée à un code HTTP.
 * $codeErreur est l'identifiant stable renvoyé par l'API (ex. « stock_epuise »).
 */
class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $statut,
        public readonly string $codeErreur,
        string $message,
        public readonly array $details = []
    ) {
        parent::__construct($message, $statut);
    }
}
