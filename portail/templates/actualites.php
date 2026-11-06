<?php use Pixelwave\Core\View; ?>
<h1 class="titre-page">Actualités du studio</h1>
<div class="grille grille--3">
    <?php foreach ($actualites as $a): ?>
        <?= View::partiel('partials/carte_actualite', ['a' => $a]) ?>
    <?php endforeach ?>
</div>
