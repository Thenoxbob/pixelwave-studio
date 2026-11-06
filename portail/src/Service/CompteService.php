<?php
declare(strict_types=1);

namespace Pixelwave\Service;

use Pixelwave\Core\Config;
use Pixelwave\Core\HttpException;
use Pixelwave\Core\Validator;
use Pixelwave\Repository\UtilisateurRepository;

/** Inscription, connexion et sécurité des comptes. Utilisé par le site et par l'API. */
final class CompteService
{
    public function __construct(private readonly UtilisateurRepository $utilisateurs = new UtilisateurRepository())
    {
    }

    /** Crée un compte joueur. Lève une HttpException 400 avec le détail des champs invalides. */
    public function inscrire(array $donnees): int
    {
        $v = new Validator($donnees);
        $v->requis('pseudo')->longueur('pseudo', 3, 30)
          ->motif('pseudo', '/^[A-Za-z0-9_.-]+$/', 'Lettres, chiffres, point, tiret et tiret bas uniquement.')
          ->requis('email')->email('email')
          ->requis('mot_de_passe')->motDePasse('mot_de_passe');
        if (($donnees['mot_de_passe'] ?? '') !== ($donnees['confirmation'] ?? null)) {
            $v->ajouter('confirmation', 'Les deux mots de passe ne correspondent pas.');
        }
        if (empty($donnees['cgu'])) {
            $v->ajouter('cgu', "Tu dois accepter les conditions d'utilisation.");
        }
        $email = strtolower((string) $v->valeur('email'));
        if (!isset($v->erreurs()['email']) && $this->utilisateurs->emailExiste($email)) {
            $v->ajouter('email', 'Un compte existe déjà avec cette adresse.');
        }
        if (!isset($v->erreurs()['pseudo']) && $this->utilisateurs->pseudoExiste((string) $v->valeur('pseudo'))) {
            $v->ajouter('pseudo', 'Ce pseudo est déjà pris.');
        }
        $v->verifier();

        return $this->utilisateurs->creerJoueur((string) $v->valeur('pseudo'), $email, (string) $donnees['mot_de_passe'], !empty($donnees['newsletter']));
    }

    /**
     * Vérifie e-mail + mot de passe. Message volontairement identique que l'e-mail
     * existe ou non (pas d'énumération des comptes). Blocage après trop d'échecs.
     */
    public function authentifier(string $email, string $motDePasse, string $ip): array
    {
        $email = strtolower(trim($email));
        $echecs = $this->utilisateurs->echecsRecents($email, $ip);
        if ($echecs['email'] >= Config::get('connexion_max_echecs_email', 5) || $echecs['ip'] >= Config::get('connexion_max_echecs_ip', 20)) {
            throw new HttpException(429, 'trop_de_tentatives', 'Trop de tentatives de connexion. Réessaie dans 15 minutes.');
        }

        $u = $email !== '' ? $this->utilisateurs->trouverParEmail($email) : null;
        // password_verify est appelé même si le compte n'existe pas : temps de réponse constant
        $hash = $u['mot_de_passe_hash'] ?? '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
        $ok = password_verify($motDePasse, $hash) && $u !== null && $u['actif'];

        $this->utilisateurs->enregistrerTentative($email, $ip, $ok);
        if (!$ok) {
            throw new HttpException(401, 'identifiants_invalides', 'E-mail ou mot de passe incorrect.');
        }

        if (password_needs_rehash($u['mot_de_passe_hash'], PASSWORD_DEFAULT)) {
            $this->utilisateurs->majMotDePasse((int) $u['id_utilisateur'], $motDePasse);
        }
        $this->utilisateurs->majDerniereConnexion((int) $u['id_utilisateur']);
        return $u;
    }

    public function changerMotDePasse(array $utilisateur, array $donnees): void
    {
        $v = new Validator($donnees);
        $v->requis('actuel')->requis('nouveau')->motDePasse('nouveau');
        if (!isset($v->erreurs()['actuel']) && !password_verify((string) ($donnees['actuel'] ?? ''), $utilisateur['mot_de_passe_hash'])) {
            $v->ajouter('actuel', 'Mot de passe actuel incorrect.');
        }
        if (($donnees['nouveau'] ?? '') !== ($donnees['confirmation'] ?? null)) {
            $v->ajouter('confirmation', 'Les deux mots de passe ne correspondent pas.');
        }
        $v->verifier();
        $this->utilisateurs->majMotDePasse((int) $utilisateur['id_utilisateur'], (string) $donnees['nouveau']);
    }
}
