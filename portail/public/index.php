<?php
declare(strict_types=1);

// Contrôleur frontal : toutes les requêtes passent par ce fichier (voir .htaccess / vhost Apache)

// Serveur de développement PHP : laisse passer les fichiers statiques (CSS, JS)
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/src/bootstrap.php';

use Pixelwave\Controller\ApiController;
use Pixelwave\Controller\SiteController;
use Pixelwave\Core\Auth;
use Pixelwave\Core\Config;
use Pixelwave\Core\HttpException;
use Pixelwave\Core\Request;
use Pixelwave\Core\Router;
use Pixelwave\Core\Session;
use Pixelwave\Core\View;

// En-têtes de sécurité envoyés sur toutes les réponses
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");

$requete = Request::depuisGlobales();
if (!$requete->estApi() || $requete->entete('Authorization') === null) {
    Session::demarrer();
}

$site = new SiteController();
$api = new ApiController();
$routes = new Router();

// --- Pages ---------------------------------------------------------------
$routes->get('/', [$site, 'accueil']);
$routes->get('/boutique', [$site, 'boutique']);
$routes->get('/boutique/{slug}', [$site, 'produit']);
$routes->get('/actualites', [$site, 'actualites']);
$routes->get('/actualites/{slug}', [$site, 'actualite']);
$routes->get('/aide', [$site, 'aide']);
$routes->get('/confidentialite', [$site, 'confidentialite']);

$routes->get('/connexion', [$site, 'connexion']);
$routes->post('/connexion', [$site, 'connexion']);
$routes->get('/inscription', [$site, 'inscription']);
$routes->post('/inscription', [$site, 'inscription']);
$routes->post('/deconnexion', [$site, 'deconnexion']);
$routes->get('/compte', [$site, 'compte']);
$routes->post('/compte/mot-de-passe', [$site, 'changerMotDePasse']);
$routes->post('/compte/preferences', [$site, 'preferences']);
$routes->post('/compte/jetons', [$site, 'creerJeton']);
$routes->post('/compte/jetons/{id}/revoquer', [$site, 'revoquerJeton']);

$routes->get('/panier', [$site, 'panier']);
$routes->post('/panier/ajouter', [$site, 'ajouterPanier']);
$routes->post('/panier/retirer', [$site, 'retirerPanier']);
$routes->post('/panier/valider', [$site, 'validerPanier']);
$routes->get('/bibliotheque', [$site, 'bibliotheque']);
$routes->post('/bibliotheque/cles/{id}/reveler', [$site, 'revelerCle']);

$routes->get('/support', [$site, 'support']);
$routes->get('/support/nouveau', [$site, 'nouveauTicket']);
$routes->post('/support/nouveau', [$site, 'nouveauTicket']);
$routes->get('/support/{reference}', [$site, 'ticket']);
$routes->post('/support/{reference}/messages', [$site, 'repondreTicket']);
$routes->post('/support/{reference}/traitement', [$site, 'traiterTicket']);

// --- API REST v1 -----------------------------------------------------------
$routes->get('/api/v1/sante', [$api, 'sante']);
$routes->post('/api/v1/auth/jetons', [$api, 'creerJeton']);
$routes->delete('/api/v1/auth/jetons/courant', [$api, 'revoquerJeton']);
$routes->get('/api/v1/plateformes', [$api, 'plateformes']);
$routes->get('/api/v1/produits', [$api, 'produits']);
$routes->get('/api/v1/produits/{id}', [$api, 'produit']);
$routes->get('/api/v1/actualites', [$api, 'actualites']);
$routes->get('/api/v1/actualites/{id}', [$api, 'actualite']);
$routes->get('/api/v1/moi', [$api, 'moi']);
$routes->get('/api/v1/moi/bibliotheque', [$api, 'bibliotheque']);
$routes->post('/api/v1/moi/cles/{id}/revelation', [$api, 'revelerCle']);
$routes->get('/api/v1/moi/panier', [$api, 'panier']);
$routes->post('/api/v1/moi/panier', [$api, 'ajouterPanier']);
$routes->delete('/api/v1/moi/panier/{id}', [$api, 'retirerPanier']);
$routes->get('/api/v1/moi/commandes', [$api, 'commandes']);
$routes->post('/api/v1/moi/commandes', [$api, 'creerCommande']);
$routes->get('/api/v1/tickets', [$api, 'tickets']);
$routes->post('/api/v1/tickets', [$api, 'creerTicket']);
$routes->get('/api/v1/tickets/{reference}', [$api, 'ticket']);
$routes->patch('/api/v1/tickets/{reference}', [$api, 'traiterTicket']);
$routes->post('/api/v1/tickets/{reference}/messages', [$api, 'ajouterMessage']);

// --- Exécution et gestion des erreurs ----------------------------------------
try {
    [$gestionnaire, $params] = $routes->trouver($requete->methode, $requete->chemin);
    echo $params ? $gestionnaire($requete, $params) : $gestionnaire($requete);
} catch (HttpException $e) {
    if ($e->statut === 405) {
        header('Allow: ' . implode(', ', $e->details['autorisees'] ?? []));
    }
    if ($requete->estApi()) {
        $erreur = ['code' => $e->codeErreur, 'message' => $e->getMessage()];
        if ($e->details && $e->statut !== 405) {
            $erreur['details'] = $e->details;
        }
        echo View::json(['erreur' => $erreur], $e->statut);
    } elseif ($e->statut === 401) {
        Session::flash('info', 'Connecte-toi pour accéder à cette page.');
        echo View::rediriger('/connexion?retour=' . rawurlencode($requete->chemin));
    } else {
        echo View::rendre('erreur', ['titre' => 'Erreur ' . $e->statut, 'page' => '', 'statut' => $e->statut, 'message' => $e->getMessage()], $e->statut);
    }
} catch (\Throwable $e) {
    // Erreur inattendue : détail dans le journal du serveur, message neutre pour l'utilisateur
    error_log('[portail] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $message = Config::get('env') === 'dev' ? $e->getMessage() : 'Une erreur interne est survenue. Réessaie dans quelques instants.';
    if ($requete->estApi()) {
        echo View::json(['erreur' => ['code' => 'erreur_interne', 'message' => $message]], 500);
    } else {
        Auth::definir(null);
        echo View::rendre('erreur', ['titre' => 'Erreur', 'page' => '', 'statut' => 500, 'message' => $message], 500);
    }
}
