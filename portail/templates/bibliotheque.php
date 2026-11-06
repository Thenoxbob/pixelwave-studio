<?php
use Pixelwave\Core\Csrf;
use Pixelwave\Core\View;
?>
<h1 class="titre-page">Ma bibliothèque</h1>
<p class="intro">Tes jeux et contenus achetés, avec leur clé d'activation. Une clé n'est affichée en entier qu'à ta demande.</p>

<?php if (!$contenu): ?>
    <div class="vide">
        <p>Tu ne possèdes encore aucun jeu.</p>
        <a class="btn btn--plein" href="/boutique">Découvrir nos jeux</a>
    </div>
<?php else: ?>
<ul class="biblio">
    <?php foreach ($contenu as $c): ?>
        <li class="biblio__item" id="cle-<?= (int) $c['id_cle'] ?>">
            <div class="biblio__visuel"><?= View::partiel('partials/visuel', ['code' => $c['visuel']]) ?></div>
            <div class="biblio__infos">
                <h2 class="biblio__titre"><?= e($c['titre']) ?></h2>
                <p class="carte__meta"><?= $c['type'] === 'dlc' ? 'DLC' : 'Jeu' ?> · <?= e($c['plateforme']) ?> · acheté le <?= e(date_fr($c['date_commande'])) ?></p>
                <?php if ($c['date_sortie'] > date('Y-m-d')): ?>
                    <p class="note">Précommande : la clé sera activable à la sortie, le <?= e(date_fr($c['date_sortie'])) ?>.</p>
                <?php endif ?>
            </div>
            <div class="bloc-cle" data-cle="<?= (int) $c['id_cle'] ?>">
                <span class="bloc-cle__label">Clé <?= e($c['plateforme']) ?></span>
                <code class="bloc-cle__code"><?= e($c['code'] ?? $c['code_masque']) ?></code>
                <?php if ($c['code'] === null): ?>
                    <form method="post" action="/bibliotheque/cles/<?= (int) $c['id_cle'] ?>/reveler" class="bloc-cle__form">
                        <?= Csrf::champ() ?>
                        <button class="btn btn--plein btn--petit" data-action="reveler">Afficher la clé</button>
                    </form>
                <?php else: ?>
                    <button class="btn btn--contour btn--petit" type="button" data-action="copier">Copier</button>
                <?php endif ?>
            </div>
        </li>
    <?php endforeach ?>
</ul>
<p class="note">Une clé ne fonctionne pas ? <a class="lien" href="/support/nouveau">Contacte le support</a> en indiquant la référence de ta commande.</p>
<?php endif ?>
