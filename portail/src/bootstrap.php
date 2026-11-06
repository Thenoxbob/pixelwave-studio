<?php
declare(strict_types=1);

// Chargement automatique des classes : Pixelwave\Core\Router -> src/Core/Router.php
spl_autoload_register(function (string $classe): void {
    $prefixe = 'Pixelwave\\';
    if (strncmp($classe, $prefixe, strlen($prefixe)) !== 0) {
        return;
    }
    $chemin = __DIR__ . '/' . str_replace('\\', '/', substr($classe, strlen($prefixe))) . '.php';
    if (is_file($chemin)) {
        require $chemin;
    }
});

require __DIR__ . '/helpers.php';

use Pixelwave\Core\Config;

$fichierConfig = getenv('PIXELWAVE_CONFIG') ?: dirname(__DIR__) . '/config/config.php';
if (!is_file($fichierConfig)) {
    http_response_code(500);
    exit('Configuration manquante : copier config/config.example.php en config/config.php');
}
Config::charger(require $fichierConfig);

date_default_timezone_set('Europe/Paris');

if (Config::get('env') === 'dev') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
