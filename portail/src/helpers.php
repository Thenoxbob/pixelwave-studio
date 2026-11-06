<?php
declare(strict_types=1);

// Fonctions utilitaires disponibles partout (templates compris)

/** Échappe une valeur avant affichage HTML (protection XSS). */
function e(mixed $valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Formate un montant : 17.5 -> « 17,50 € ». */
function prix(float|string $montant): string
{
    return number_format((float) $montant, 2, ',', ' ') . ' €';
}

/** Formate une date SQL : « 14 oct. 2026 ». */
function date_fr(?string $date, bool $avecHeure = false): string
{
    if ($date === null || $date === '') {
        return '—';
    }
    $mois = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    $t = strtotime($date);
    $texte = date('j', $t) . ' ' . $mois[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
    return $avecHeure ? $texte . ' à ' . date('H\hi', $t) : $texte;
}

/** Longueur d'une chaîne UTF-8 (sans dépendre de l'extension mbstring). */
function longueur(string $texte): int
{
    return preg_match_all('/./su', $texte);
}

/** Extrait d'un texte sans couper un caractère UTF-8 en deux. */
function extrait(string $texte, int $max): string
{
    if (longueur($texte) <= $max) {
        return $texte;
    }
    preg_match('/^.{0,' . $max . '}/su', $texte, $m);
    return rtrim($m[0]) . '…';
}
