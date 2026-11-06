<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

final class UtilisateurRepository extends Repository
{
    private const CHAMPS = 'u.id_utilisateur, u.pseudo, u.email, u.mot_de_passe_hash, u.newsletter, u.actif,
                            u.date_inscription, u.derniere_connexion, r.code AS role, r.libelle AS role_libelle';

    public function trouverParId(int $id): ?array
    {
        return $this->un('SELECT ' . self::CHAMPS . ' FROM utilisateur u JOIN role r ON r.id_role = u.id_role
                          WHERE u.id_utilisateur = ?', [$id]);
    }

    public function trouverParEmail(string $email): ?array
    {
        return $this->un('SELECT ' . self::CHAMPS . ' FROM utilisateur u JOIN role r ON r.id_role = u.id_role
                          WHERE u.email = ?', [$email]);
    }

    public function emailExiste(string $email): bool
    {
        return $this->un('SELECT 1 FROM utilisateur WHERE email = ?', [$email]) !== null;
    }

    public function pseudoExiste(string $pseudo): bool
    {
        return $this->un('SELECT 1 FROM utilisateur WHERE pseudo = ?', [$pseudo]) !== null;
    }

    /** Crée un compte joueur et retourne son identifiant. */
    public function creerJoueur(string $pseudo, string $email, string $motDePasse, bool $newsletter): int
    {
        $this->executer(
            "INSERT INTO utilisateur (pseudo, email, mot_de_passe_hash, id_role, newsletter)
             VALUES (?, ?, ?, (SELECT id_role FROM role WHERE code = 'joueur'), ?)",
            [$pseudo, $email, password_hash($motDePasse, PASSWORD_DEFAULT), (int) $newsletter]
        );
        return (int) $this->pdo()->lastInsertId();
    }

    public function majDerniereConnexion(int $id): void
    {
        $this->executer('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = ?', [$id]);
    }

    public function majMotDePasse(int $id, string $motDePasse): void
    {
        $this->executer('UPDATE utilisateur SET mot_de_passe_hash = ? WHERE id_utilisateur = ?',
            [password_hash($motDePasse, PASSWORD_DEFAULT), $id]);
    }

    public function majNewsletter(int $id, bool $newsletter): void
    {
        $this->executer('UPDATE utilisateur SET newsletter = ? WHERE id_utilisateur = ?', [(int) $newsletter, $id]);
    }

    public function enregistrerTentative(string $email, string $ip, bool $reussie): void
    {
        $this->executer('INSERT INTO tentative_connexion (email, adresse_ip, reussie) VALUES (?, ?, ?)',
            [$email, $ip, (int) $reussie]);
    }

    /** Nombre d'échecs de connexion sur les 15 dernières minutes, par e-mail et par IP. */
    public function echecsRecents(string $email, string $ip): array
    {
        $ligne = $this->un(
            'SELECT SUM(email = ?) AS par_email, SUM(adresse_ip = ?) AS par_ip
             FROM tentative_connexion
             WHERE reussie = FALSE AND date_tentative >= NOW() - INTERVAL 15 MINUTE
               AND (email = ? OR adresse_ip = ?)',
            [$email, $ip, $email, $ip]
        );
        return ['email' => (int) ($ligne['par_email'] ?? 0), 'ip' => (int) ($ligne['par_ip'] ?? 0)];
    }

    /** Membres du support pouvant recevoir un ticket. */
    public function listerStaff(): array
    {
        return $this->tous("SELECT u.id_utilisateur, u.pseudo FROM utilisateur u JOIN role r ON r.id_role = u.id_role
                            WHERE r.code IN ('support', 'admin') AND u.actif = TRUE ORDER BY u.pseudo");
    }
}
