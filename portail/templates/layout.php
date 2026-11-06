<?php
use Pixelwave\Core\Auth;
use Pixelwave\Core\Csrf;
use Pixelwave\Core\Session;
use Pixelwave\Repository\PanierRepository;

$moi = Auth::utilisateur();
$nbPanier = $moi ? (new PanierRepository())->compter((int) $moi['id_utilisateur']) : 0;
$page ??= '';
$liens = [
    'accueil'      => ['/', 'Accueil'],
    'boutique'     => ['/boutique', 'Boutique'],
    'bibliotheque' => ['/bibliotheque', 'Ma bibliothèque'],
    'actualites'   => ['/actualites', 'Actualités'],
    'support'      => ['/support', $moi && Auth::estStaff($moi) ? 'Tickets' : 'Support'],
];
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(Csrf::jeton()) ?>">
    <title><?= e($titre ?? 'Portail') ?> · PixelWave</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Figtree:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Crect width='16' height='16' fill='%230b0f1d'/%3E%3Crect x='3' y='8' width='3' height='5' fill='%233ddbc4'/%3E%3Crect x='7' y='5' width='3' height='8' fill='%233ddbc4'/%3E%3Crect x='11' y='3' width='3' height='3' fill='%23ff8a5b'/%3E%3C/svg%3E">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
<a class="lien-evitement" href="#contenu">Aller au contenu</a>
<header class="entete">
    <div class="entete__barre">
        <a class="logo" href="/">Pixel<span>Wave</span></a>
        <button class="menu-bouton" type="button" aria-expanded="false" aria-controls="nav-principale">Menu</button>
        <nav id="nav-principale" class="nav" aria-label="Navigation principale">
            <?php foreach ($liens as $cle => [$url, $texte]): ?>
                <a href="<?= $url ?>" class="nav__lien<?= $page === $cle ? ' nav__lien--actif' : '' ?>"<?= $page === $cle ? ' aria-current="page"' : '' ?>><?= e($texte) ?></a>
            <?php endforeach ?>
        </nav>
        <div class="entete__actions">
            <a class="btn btn--contour" href="/panier">Panier (<?= $nbPanier ?>)</a>
            <?php if ($moi): ?>
                <a class="btn btn--plein" href="/compte"><?= e($moi['pseudo']) ?></a>
            <?php else: ?>
                <a class="btn btn--plein" href="/connexion">Mon compte</a>
            <?php endif ?>
        </div>
    </div>
</header>

<main id="contenu" class="page">
    <?php foreach (Session::lireFlash() as $f): ?>
        <div class="alerte alerte--<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
    <?php endforeach ?>
    <?= $corpsPage ?>
</main>

<footer class="pied">
    <nav class="pied__liens" aria-label="Liens utiles">
        <a href="/aide">Aide</a>
        <a href="/aide#cgv">CGV</a>
        <a href="/confidentialite">Confidentialité</a>
        <a href="mailto:support@pixelwave.test">Contact</a>
    </nav>
    <p>© <?= date('Y') ?> PixelWave Studio</p>
</footer>
</body>
</html>
