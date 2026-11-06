<?php
declare(strict_types=1);

namespace Pixelwave\Core;

/**
 * Validation des données saisies. Les erreurs sont collectées par champ
 * puis renvoyées d'un coup (formulaire ou réponse API 400).
 */
final class Validator
{
    private array $erreurs = [];

    public function __construct(private readonly array $donnees)
    {
    }

    public function valeur(string $champ): mixed
    {
        $v = $this->donnees[$champ] ?? null;
        return is_string($v) ? trim($v) : $v;
    }

    public function requis(string $champ, string $message = 'Ce champ est obligatoire.'): self
    {
        $v = $this->valeur($champ);
        if ($v === null || $v === '' || $v === []) {
            $this->ajouter($champ, $message);
        }
        return $this;
    }

    public function longueur(string $champ, int $min, int $max): self
    {
        $v = $this->valeur($champ);
        if (is_string($v) && $v !== '') {
            $n = longueur($v);
            if ($n < $min || $n > $max) {
                $this->ajouter($champ, "Entre $min et $max caractères.");
            }
        }
        return $this;
    }

    public function email(string $champ): self
    {
        $v = $this->valeur($champ);
        if (is_string($v) && $v !== '' && (filter_var($v, FILTER_VALIDATE_EMAIL) === false || strlen($v) > 255)) {
            $this->ajouter($champ, 'Adresse e-mail invalide.');
        }
        return $this;
    }

    public function motif(string $champ, string $regex, string $message): self
    {
        $v = $this->valeur($champ);
        if (is_string($v) && $v !== '' && !preg_match($regex, $v)) {
            $this->ajouter($champ, $message);
        }
        return $this;
    }

    public function dansListe(string $champ, array $valeurs, string $message = 'Valeur non autorisée.'): self
    {
        $v = $this->valeur($champ);
        if ($v !== null && $v !== '' && !in_array($v, $valeurs, true)) {
            $this->ajouter($champ, $message);
        }
        return $this;
    }

    public function entierPositif(string $champ): self
    {
        $v = $this->valeur($champ);
        if ($v !== null && $v !== '' && filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $this->ajouter($champ, 'Identifiant invalide.');
        }
        return $this;
    }

    /** Politique de mot de passe : 10 caractères minimum, avec lettre, chiffre et caractère spécial. */
    public function motDePasse(string $champ): self
    {
        $v = $this->valeur($champ);
        if (is_string($v) && $v !== '' && !self::motDePasseRobuste($v)) {
            $this->ajouter($champ, '10 caractères minimum, dont une lettre, un chiffre et un caractère spécial.');
        }
        return $this;
    }

    public static function motDePasseRobuste(string $mdp): bool
    {
        return strlen($mdp) >= 10
            && strlen($mdp) <= 128
            && preg_match('/\p{L}/u', $mdp)
            && preg_match('/\d/', $mdp)
            && preg_match('/[^\p{L}\d]/u', $mdp);
    }

    public function ajouter(string $champ, string $message): void
    {
        $this->erreurs[$champ] ??= $message;
    }

    public function erreurs(): array
    {
        return $this->erreurs;
    }

    public function estValide(): bool
    {
        return $this->erreurs === [];
    }

    /** Pour l'API : lève une erreur 400 avec le détail des champs. */
    public function verifier(): void
    {
        if (!$this->estValide()) {
            throw new HttpException(400, 'donnees_invalides', 'Certaines données sont invalides.', $this->erreurs);
        }
    }
}
