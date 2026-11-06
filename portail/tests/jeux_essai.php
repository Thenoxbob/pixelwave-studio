<?php
declare(strict_types=1);

// Jeux d'essai du cahier de recette : valeurs valides, limites et invalides.
// Chaque valeur passe par les mêmes règles que le portail (Validator côté serveur).
//   php tests/jeux_essai.php

require __DIR__ . '/bootstrap.php';

use Pixelwave\Core\Validator;

$cas = [
    // [donnée, type de cas, valeur, règle à appliquer, résultat attendu]
    ['Pseudo', 'valide', 'LeaPixel', 'pseudo', true],
    ['Pseudo', 'limite basse (3)', 'abc', 'pseudo', true],
    ['Pseudo', 'limite haute (30)', str_repeat('a', 30), 'pseudo', true],
    ['Pseudo', 'invalide (2)', 'ab', 'pseudo', false],
    ['Pseudo', 'invalide (31)', str_repeat('a', 31), 'pseudo', false],
    ['Pseudo', 'invalide (espace)', 'Lea Pixel', 'pseudo', false],
    ['Mot de passe', 'valide', 'Un-Mot2Passe!', 'mdp', true],
    ['Mot de passe', 'limite (10 caractères)', 'Abcdefgh1!', 'mdp', true],
    ['Mot de passe', 'invalide (9 caractères)', 'Abcdefg1!', 'mdp', false],
    ['Mot de passe', 'invalide (sans caractère spécial)', 'motdepasse123', 'mdp', false],
    ['Mot de passe', 'invalide (sans lettre)', '1234567890!!', 'mdp', false],
    ['E-mail', 'valide', 'lea.martin@exemple.test', 'email', true],
    ['E-mail', 'invalide', 'lea.martin@', 'email', false],
    ['Titre de ticket', 'limite basse (5)', 'Crash', 'titre', true],
    ['Titre de ticket', 'limite haute (150)', str_repeat('x', 150), 'titre', true],
    ['Titre de ticket', 'invalide (3)', 'Bug', 'titre', false],
    ['Titre de ticket', 'invalide (151)', str_repeat('x', 151), 'titre', false],
    ['Description', 'limite (20 caractères, accents compris)', 'Écran noir au départ', 'description', true],
    ['Description', 'invalide (19)', 'Écran noir au dépar', 'description', false],
    ['Version du jeu', 'valide', '1.4.0', 'version', true],
    ['Version du jeu', 'invalide (balise HTML)', '<b>1</b>', 'version', false],
];

$ok = 0;
printf("%-16s %-38s %-26s %-9s %s\n", 'Donnée', 'Cas', 'Valeur', 'Attendu', 'Obtenu');
foreach ($cas as [$donnee, $type, $valeur, $regle, $attendu]) {
    $v = new Validator(['x' => $valeur]);
    match ($regle) {
        'pseudo'      => $v->longueur('x', 3, 30)->motif('x', '/^[A-Za-z0-9_.-]+$/', 'format'),
        'mdp'         => $v->motDePasse('x'),
        'email'       => $v->email('x'),
        'titre'       => $v->longueur('x', 5, 150),
        'description' => $v->longueur('x', 20, 5000),
        'version'     => $v->longueur('x', 1, 20)->motif('x', '/^[0-9A-Za-z.\-]+$/', 'format'),
    };
    $obtenu = $v->estValide();
    $ok += $obtenu === $attendu;
    printf("%-16s %-38s %-26s %-9s %s\n", $donnee, $type, strlen($valeur) > 24 ? substr($valeur, 0, 10) . '… (' . strlen($valeur) . ')' : $valeur,
        $attendu ? 'accepté' : 'refusé', ($obtenu ? 'accepté' : 'refusé') . ($obtenu === $attendu ? '  OK' : '  ÉCART'));
}
printf("\n%d/%d jeux d'essai conformes\n", $ok, count($cas));
exit($ok === count($cas) ? 0 : 1);
