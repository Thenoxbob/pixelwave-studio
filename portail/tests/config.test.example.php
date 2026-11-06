<?php
// Configuration des tests : copier en config.test.php.
// La base « pixelwave_test » est supprimée et recréée à chaque test d'intégration :
// ne jamais pointer vers la base de production.
return [
    'env' => 'dev',
    'url' => 'http://127.0.0.1:8080',
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'pixelwave_test',
        'user'     => 'root',
        'password' => '',
    ],
    // compte autorisé à créer / supprimer la base de test
    'db_admin' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'user'     => 'root',
        'password' => '',
    ],
    'discord_webhook' => '',
    'jeton_duree_jours' => 90,
    'connexion_max_echecs_email' => 5,
    'connexion_max_echecs_ip'    => 20,
];
