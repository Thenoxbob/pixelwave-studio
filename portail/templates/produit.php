<?php
use Pixelwave\Core\Auth;
use Pixelwave\Core\Csrf;
use Pixelwave\Core\View;

$connecte = Auth::utilisateur() !== null;
$disponibles = array_filter($offres, fn($o) => (int) $o['stock'] > 0 && empty($o['possede']));
$precommande = $produit['date_sortie'] > date('Y-m-d');
?>
<nav class="fil" aria-label="Fil d'Ariane">
    <a href="/boutique">Boutique</a> /
    <?php if ($produit['jeu_parent']): ?><a href="/boutique/<?= e($produit['jeu_parent_slug']) ?>"><?= e($produit['jeu_parent']) ?></a> /<?php endif ?>
    <span aria-current="page"><?= e($produit['titre']) ?></span>
</nav>

<section class="fiche">
    <div class="fiche__visuel"><?= View::partiel('partials/visuel', ['code' => $produit['visuel']]) ?></div>
    <div class="fiche__infos">
        <p class="surtitre"><?= $produit['type'] === 'dlc' ? 'Contenu additionnel · ' . e($produit['jeu_parent']) : e($produit['genre']) ?></p>
        <h1><?= e($produit['titre']) ?></h1>
        <p class="fiche__accroche"><?= e($produit['accroche']) ?></p>
        <?php if ($produit['description']): ?><p><?= e($produit['description']) ?></p><?php endif ?>
        <p class="fiche__date"><?= $precommande ? 'Sortie prévue le ' : 'Sorti le ' ?><?= e(date_fr($produit['date_sortie'])) ?></p>

        <div class="fiche__achat">
            <p class="fiche__prix">
                <?= prix($produit['prix_final']) ?>
                <?php if ((int) $produit['remise_pct'] > 0): ?>
                    <s><?= prix($produit['prix_base']) ?></s> <span class="badge badge--promo">-<?= (int) $produit['remise_pct'] ?> %</span>
                <?php endif ?>
            </p>

            <form method="post" action="/panier/ajouter" class="fiche__form">
                <?= Csrf::champ() ?>
                <input type="hidden" name="id_produit" value="<?= (int) $produit['id_produit'] ?>">
                <input type="hidden" name="slug" value="<?= e($produit['slug']) ?>">
                <fieldset class="choix-plateforme">
                    <legend>Choisis ta plateforme</legend>
                    <?php $premier = true; foreach ($offres as $o):
                        $indispo = (int) $o['stock'] === 0 || !empty($o['possede']); ?>
                        <label class="choix<?= $indispo ? ' choix--indispo' : '' ?>">
                            <input type="radio" name="plateforme" value="<?= e($o['code']) ?>"<?= $indispo ? ' disabled' : '' ?><?= !$indispo && $premier ? ' checked' : '' ?> required>
                            <span><?= e($o['nom']) ?></span>
                            <?php if (!empty($o['possede'])): ?><small>Déjà possédé</small>
                            <?php elseif ((int) $o['stock'] === 0): ?><small>Rupture</small><?php endif ?>
                        </label>
                    <?php if (!$indispo) { $premier = false; } endforeach ?>
                </fieldset>
                <?php if (!$connecte): ?>
                    <a class="btn btn--plein btn--grand" href="/connexion?retour=<?= e(rawurlencode('/boutique/' . $produit['slug'])) ?>">Se connecter pour acheter</a>
                <?php elseif ($disponibles): ?>
                    <button class="btn btn--plein btn--grand"><?= $precommande ? 'Précommander' : 'Ajouter au panier' ?></button>
                <?php else: ?>
                    <button class="btn btn--plein btn--grand" disabled>Indisponible</button>
                <?php endif ?>
                <p class="note">Une clé d'activation t'est attribuée dès la validation de la commande.</p>
            </form>
        </div>
    </div>
</section>

<?php if ($dlcs): ?>
<section class="section">
    <div class="section__entete"><h2>Contenus additionnels</h2></div>
    <div class="grille grille--4">
        <?php foreach ($dlcs as $p): ?><?= View::partiel('partials/carte_produit', ['p' => $p]) ?><?php endforeach ?>
    </div>
</section>
<?php endif ?>

<aside class="bandeau-bug bandeau-bug--discret">
    <div>
        <h2>Un problème avec <?= e($produit['titre']) ?> ?</h2>
        <p>Décris-le à l'équipe QA, tu suivras sa correction depuis ton compte.</p>
    </div>
    <a class="btn btn--contour" href="/support/nouveau?produit=<?= (int) $produit['id_produit'] ?>">Signaler un bug</a>
</aside>
