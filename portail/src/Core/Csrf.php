<?php
declare(strict_types=1);

namespace Pixelwave\Core;

/**
 * Jeton anti-CSRF : chaque formulaire POST (et chaque appel API fait depuis
 * le portail avec le cookie de session) doit renvoyer ce jeton.
 */
final class Csrf
{
    public static function jeton(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function champ(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::jeton()) . '">';
    }

    public static function valide(?string $jeton): bool
    {
        return is_string($jeton) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $jeton);
    }

    public static function verifier(?string $jeton): void
    {
        if (!self::valide($jeton)) {
            throw new HttpException(403, 'csrf_invalide', 'Session expirée ou formulaire invalide. Recharge la page et réessaie.');
        }
    }
}
