<?php
// Copier ce fichier en config.php puis adapter les valeurs.
// config.php est ignoré par Git : il contient les secrets du serveur.

return [
    // 'dev' affiche les erreurs, 'prod' les journalise seulement
    'env' => 'prod',

    // URL publique du portail, sans / final
    'url' => 'https://portail.pixelwave.test',

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'pixelwave',
        'user'     => 'pixelwave_app',
        'password' => 'A_REMPLACER',
    ],

    // Service externe : webhook Discord appelé à chaque nouveau signalement de bug.
    // Laisser vide pour désactiver.
    'discord_webhook' => '',

    // Durée de validité d'un jeton API (en jours)
    'jeton_duree_jours' => 90,

    // Anti force brute : échecs autorisés sur 15 minutes
    'connexion_max_echecs_email' => 5,
    'connexion_max_echecs_ip'    => 20,
];
