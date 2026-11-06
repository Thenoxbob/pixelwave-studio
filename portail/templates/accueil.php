<?php use Pixelwave\Core\View; ?>
<?php if ($une): ?>
<section class="hero">
    <div class="hero__texte">
        <span class="badge badge--contour"><?= $une['type'] === 'dlc' ? 'Nouveau DLC' : 'Nouveau jeu' ?></span>
        <h1><?= e($une['titre']) ?></h1>
        <p><?= e($une['accroche']) ?> Disponible maintenant.</p>
        <div class="hero__actions">
            <a class="btn btn--plein btn--grand" href="/boutique/<?= e($une['slug']) ?>">Voir le <?= $une['type'] === 'dlc' ? 'DLC' : 'jeu' ?> · <?= prix($une['prix_final']) ?></a>
            <a class="btn btn--contour btn--grand" href="/boutique?tri=nouveautes">Toutes les nouveautés</a>
        </div>
    </div>
    <div class="hero__visuel"><?= View::partiel('partials/visuel', ['code' => $une['visuel']]) ?></div>
</section>
<?php endif ?>

<section class="section">
    <div class="section__entete">
        <h2>Promotions en cours</h2>
        <a class="lien" href="/boutique">Voir toute la boutique</a>
    </div>
    <div class="grille grille--4">
        <?php foreach ($promos as $p): ?>
            <?= View::partiel('partials/carte_produit', ['p' => $p]) ?>
        <?php endforeach ?>
    </div>
</section>

<section class="section">
    <div class="section__entete">
        <h2>Actualités du studio</h2>
        <a class="lien" href="/actualites">Toutes les actualités</a>
    </div>
    <div class="grille grille--3">
        <?php foreach ($actualites as $a): ?>
            <?= View::partiel('partials/carte_actualite', ['a' => $a]) ?>
        <?php endforeach ?>
    </div>
</section>

<section class="bandeau-bug">
    <svg class="bandeau-bug__icone" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 3h2v2h2V3h2v2.3A5 5 0 0 1 17 9h3v2h-3v2h3v2h-3a5 5 0 0 1-10 0H4v-2h3v-2H4V9h3a5 5 0 0 1 2-3.7zm1 5v2h4V8zm0 4v4h4v-4z"/></svg>
    <div>
        <h2>Un bug en jeu ?</h2>
        <p>Signale-le en deux minutes et suis sa correction depuis ton compte.</p>
    </div>
    <a class="btn btn--plein" href="/support/nouveau">Signaler un bug</a>
</section>
