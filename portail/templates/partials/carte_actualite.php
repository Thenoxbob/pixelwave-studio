<?php use Pixelwave\Core\View; ?>
<a class="carte carte--actu" href="/actualites/<?= e($a['slug']) ?>">
    <div class="carte__visuel carte__visuel--large"><?= View::partiel('partials/visuel', ['code' => $a['visuel'] ?? 'defaut']) ?></div>
    <div class="carte__corps">
        <p class="surtitre"><?= e($a['categorie']) ?> · <?= e(date_fr($a['date_publication'])) ?></p>
        <h3 class="carte__titre"><?= e($a['titre']) ?></h3>
        <p class="carte__texte"><?= e($a['chapo']) ?></p>
    </div>
</a>
