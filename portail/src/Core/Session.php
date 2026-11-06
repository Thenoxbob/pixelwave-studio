<?php
declare(strict_types=1);

namespace Pixelwave\Core;

/** Session PHP sécurisée + messages flash. */
final class Session
{
    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE || PHP_SAPI === 'cli') {
            return;
        }
        $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
        ini_set('session.use_strict_mode', '1');     // refuse les identifiants de session inventés
        ini_set('session.use_only_cookies', '1');
        session_name('PWSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,                    // cookie envoyé uniquement en HTTPS en production
            'httponly' => true,                      // inaccessible au JavaScript (vol de session par XSS)
            'samesite' => 'Lax',                     // limite les requêtes intersites (CSRF)
        ]);
        session_start();
    }

    /** Nouvel identifiant après connexion (protection contre la fixation de session). */
    public static function regenerer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function detruire(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            session_destroy();
        }
    }

    public static function flash(string $type, string $message): void
    {
        $flash = ['type' => $type, 'message' => $message];
        // un même message n'est affiché qu'une fois, même si plusieurs requêtes l'ont ajouté
        if (!in_array($flash, $_SESSION['flash'] ?? [], true)) {
            $_SESSION['flash'][] = $flash;
        }
    }

    public static function lireFlash(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
