<?php
use Pixelwave\Core\View;

/** @var array $p produit (ProduitRepository) */
$nouveau = $p['date_sortie'] && $p['date_sortie'] >= date('Y-m-d', strtotime('-30 days')) && $p['date_sortie'] <= date('Y-m-d');
$precommande = $p['date_sortie'] && $p['date_sortie'] > date('Y-m-d');
$sousTitre = $p['type'] === 'dlc'
    ? 'DLC · ' . $p['jeu_parent']
    : $p['genre'] . ' · ' . str_replace(['SWITCH', ','], ['Switch', ', '], (string) $p['plateformes']);
?>
<a class="carte carte--produit" href="/boutique/<?= e($p['slug']) ?>">
    <div class="carte__visuel">
        <?= View::partiel('partials/visuel', ['code' => $p['visuel']]) ?>
        <?php if ((int) $p['remise_pct'] > 0): ?>
            <span class="badge badge--promo">-<?= (int) $p['remise_pct'] ?> %</span>
        <?php elseif ($precommande): ?>
            <span class="badge badge--contour">Précommande</span>
        <?php elseif ($nouveau): ?>
            <span class="badge badge--contour">Nouveau</span>
        <?php endif ?>
        <?php if ($p['type'] === 'dlc'): ?><span class="carte__type">DLC</span><?php endif ?>
    </div>
    <div class="carte__corps">
        <h3 class="carte__titre"><?= e($p['titre']) ?></h3>
        <p class="carte__meta"><?= e($sousTitre) ?></p>
        <p class="carte__prix">
            <?= prix($p['prix_final']) ?>
            <?php if ((int) $p['remise_pct'] > 0): ?><s><?= prix($p['prix_base']) ?></s><?php endif ?>
        </p>
    </div>
</a>
