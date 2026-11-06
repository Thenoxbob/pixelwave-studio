<?php use Pixelwave\Core\View; ?>
<nav class="fil" aria-label="Fil d'Ariane"><a href="/actualites">Actualités</a> / <span aria-current="page"><?= e($actualite['titre']) ?></span></nav>
<article class="article">
    <p class="surtitre"><?= e($actualite['categorie']) ?> · <?= e(date_fr($actualite['date_publication'])) ?></p>
    <h1><?= e($actualite['titre']) ?></h1>
    <p class="article__chapo"><?= e($actualite['chapo']) ?></p>
    <div class="article__visuel"><?= View::partiel('partials/visuel', ['code' => $actualite['visuel'] ?? 'defaut']) ?></div>
    <p><?= nl2br(e($actualite['contenu'])) ?></p>
    <?php if ($actualite['produit_slug']): ?>
        <a class="btn btn--plein" href="/boutique/<?= e($actualite['produit_slug']) ?>">Voir <?= e($actualite['produit']) ?> dans la boutique</a>
    <?php endif ?>
</article>
