<?php
use Pixelwave\Core\Csrf;
use Pixelwave\Core\View;
?>
<h1 class="titre-page">Panier</h1>

<?php if (!$lignes): ?>
    <div class="vide">
        <p>Ton panier est vide.</p>
        <a class="btn btn--plein" href="/boutique">Parcourir la boutique</a>
    </div>
<?php else: ?>
<div class="panier">
    <ul class="panier__liste">
        <?php foreach ($lignes as $l): ?>
            <li class="panier__ligne">
                <div class="panier__visuel"><?= View::partiel('partials/visuel', ['code' => $l['visuel']]) ?></div>
                <div class="panier__infos">
                    <a href="/boutique/<?= e($l['slug']) ?>" class="panier__titre"><?= e($l['titre']) ?></a>
                    <span class="carte__meta"><?= $l['type'] === 'dlc' ? 'DLC' : 'Jeu' ?> · <?= e($l['plateforme']) ?></span>
                </div>
                <div class="panier__prix">
                    <?= prix($l['prix_final']) ?>
                    <?php if ((int) $l['remise_pct'] > 0): ?><s><?= prix($l['prix_base']) ?></s><?php endif ?>
                </div>
                <form method="post" action="/panier/retirer">
                    <?= Csrf::champ() ?>
                    <input type="hidden" name="id_ligne" value="<?= (int) $l['id_panier_ligne'] ?>">
                    <button class="btn btn--lien" aria-label="Retirer <?= e($l['titre']) ?> du panier">Retirer</button>
                </form>
            </li>
        <?php endforeach ?>
    </ul>

    <aside class="panier__resume">
        <h2>Récapitulatif</h2>
        <p class="panier__total"><span>Total TTC</span> <strong><?= prix($total) ?></strong></p>
        <form method="post" action="/panier/valider">
            <?= Csrf::champ() ?>
            <button class="btn btn--plein btn--grand btn--bloc">Payer et recevoir mes clés</button>
        </form>
        <p class="note">Environnement de démonstration : le paiement est simulé, aucune donnée bancaire n'est demandée ni enregistrée. En production, le paiement est délégué à un prestataire certifié PCI-DSS.</p>
    </aside>
</div>
<?php endif ?>
