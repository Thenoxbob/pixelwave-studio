<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

/**
 * Jetons d'accès à l'API. Le jeton en clair n'est montré qu'une fois à sa
 * création : la base ne garde que son empreinte SHA-256.
 */
final class JetonRepository extends Repository
{
    public static function empreinte(string $jeton): string
    {
        return hash('sha256', $jeton);
    }

    /** Retourne [jeton en clair, date d'expiration]. */
    public function creer(int $idUtilisateur, string $libelle, int $jours): array
    {
        $jeton = 'pw_' . bin2hex(random_bytes(24));
        $expiration = date('Y-m-d H:i:s', strtotime("+$jours days"));
        $this->executer('INSERT INTO jeton_api (id_utilisateur, jeton_hash, libelle, date_expiration) VALUES (?, ?, ?, ?)',
            [$idUtilisateur, self::empreinte($jeton), $libelle, $expiration]);
        return [$jeton, $expiration];
    }

    /** Utilisateur associé à un jeton valide (non révoqué, non expiré, compte actif). */
    public function utilisateurPourJeton(string $jeton): ?array
    {
        $ligne = $this->un(
            'SELECT j.id_jeton, j.id_utilisateur FROM jeton_api j
             JOIN utilisateur u ON u.id_utilisateur = j.id_utilisateur
             WHERE j.jeton_hash = ? AND j.revoque = FALSE AND j.date_expiration > NOW() AND u.actif = TRUE',
            [self::empreinte($jeton)]
        );
        if ($ligne === null) {
            return null;
        }
        $this->executer('UPDATE jeton_api SET date_derniere_utilisation = NOW() WHERE id_jeton = ?', [$ligne['id_jeton']]);
        $utilisateur = (new UtilisateurRepository())->trouverParId((int) $ligne['id_utilisateur']);
        return $utilisateur ? $utilisateur + ['id_jeton' => (int) $ligne['id_jeton']] : null;
    }

    public function lister(int $idUtilisateur): array
    {
        return $this->tous(
            'SELECT id_jeton, libelle, date_creation, date_expiration, date_derniere_utilisation, revoque,
                    (revoque = FALSE AND date_expiration > NOW()) AS valide
             FROM jeton_api WHERE id_utilisateur = ? ORDER BY date_creation DESC',
            [$idUtilisateur]
        );
    }

    public function revoquer(int $idJeton, int $idUtilisateur): bool
    {
        return $this->executer('UPDATE jeton_api SET revoque = TRUE WHERE id_jeton = ? AND id_utilisateur = ? AND revoque = FALSE',
            [$idJeton, $idUtilisateur]) === 1;
    }
}
