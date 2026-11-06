<?php
declare(strict_types=1);

namespace Pixelwave\Core;

use Pixelwave\Repository\UtilisateurRepository;

/** Utilisateur connecté et contrôle des droits. */
final class Auth
{
    private static ?array $utilisateur = null;
    private static bool $charge = false;

    /** Utilisateur courant (session du portail ou jeton API), ou null. */
    public static function utilisateur(): ?array
    {
        if (!self::$charge) {
            self::$charge = true;
            $id = $_SESSION['id_utilisateur'] ?? null;
            if ($id !== null) {
                $u = (new UtilisateurRepository())->trouverParId((int) $id);
                // un compte désactivé entre-temps perd immédiatement l'accès
                self::$utilisateur = ($u && $u['actif']) ? $u : null;
                if (self::$utilisateur === null) {
                    unset($_SESSION['id_utilisateur']);
                }
            }
        }
        return self::$utilisateur;
    }

    /** Définit l'utilisateur pour la requête en cours (jeton API, tests). */
    public static function definir(?array $utilisateur): void
    {
        self::$utilisateur = $utilisateur;
        self::$charge = true;
    }

    public static function connecter(array $utilisateur): void
    {
        Session::regenerer();
        $_SESSION['id_utilisateur'] = (int) $utilisateur['id_utilisateur'];
        unset($_SESSION['csrf']);
        self::definir($utilisateur);
    }

    public static function deconnecter(): void
    {
        Session::detruire();
        self::definir(null);
    }

    public static function role(): ?string
    {
        return self::utilisateur()['role'] ?? null;
    }

    public static function estStaff(?array $u = null): bool
    {
        $u ??= self::utilisateur();
        return $u !== null && in_array($u['role'], ['support', 'admin'], true);
    }

    /** Exige une connexion (401) et éventuellement un des rôles donnés (403). */
    public static function exiger(array $roles = []): array
    {
        $u = self::utilisateur();
        if ($u === null) {
            throw new HttpException(401, 'non_authentifie', 'Connexion requise.');
        }
        if ($roles && !in_array($u['role'], $roles, true)) {
            throw new HttpException(403, 'acces_refuse', "Tu n'as pas les droits nécessaires pour cette action.");
        }
        return $u;
    }
}
