<?php use Pixelwave\Core\View; ?>
<h1 class="titre-page">Boutique</h1>

<form class="filtres" method="get" action="/boutique" data-auto-submit>
    <div class="filtres__types" role="group" aria-label="Type de produit">
        <?php foreach (['' => 'Tout', 'jeu' => 'Jeux', 'dlc' => 'DLC', 'promo' => 'Promotions'] as $val => $lib): ?>
            <button class="puce<?= $filtres['type'] === $val ? ' puce--active' : '' ?>" name="type" value="<?= e($val) ?>"<?= $filtres['type'] === $val ? ' aria-pressed="true"' : '' ?>><?= e($lib) ?></button>
        <?php endforeach ?>
    </div>
    <input type="hidden" name="type" value="<?= e($filtres['type']) ?>" disabled data-type-courant>
    <div class="filtres__champs">
        <label class="champ">
            <span class="champ__label">Plateforme</span>
            <select name="plateforme">
                <option value="">Toutes</option>
                <?php foreach ($plateformes as $pf): ?>
                    <option value="<?= e($pf['code']) ?>"<?= $filtres['plateforme'] === $pf['code'] ? ' selected' : '' ?>><?= e($pf['nom']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label class="champ">
            <span class="champ__label">Trier par</span>
            <select name="tri">
                <?php foreach (['nouveautes' => 'Nouveautés', 'prix_croissant' => 'Prix croissant', 'prix_decroissant' => 'Prix décroissant', 'titre' => 'Nom'] as $val => $lib): ?>
                    <option value="<?= $val ?>"<?= $filtres['tri'] === $val ? ' selected' : '' ?>><?= $lib ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label class="champ champ--large">
            <span class="champ__label">Rechercher</span>
            <input type="search" name="q" value="<?= e($filtres['q']) ?>" placeholder="Nom d'un jeu ou d'un DLC" maxlength="100">
        </label>
        <noscript><button class="btn btn--contour">Filtrer</button></noscript>
    </div>
</form>

<p class="resultats" aria-live="polite"><?= count($produits) ?> résultat<?= count($produits) > 1 ? 's' : '' ?></p>

<?php if ($produits): ?>
    <div class="grille grille--5">
        <?php foreach ($produits as $p): ?>
            <?= View::partiel('partials/carte_produit', ['p' => $p]) ?>
        <?php endforeach ?>
    </div>
<?php else: ?>
    <div class="vide">
        <p>Aucun produit ne correspond à ta recherche.</p>
        <a class="btn btn--contour" href="/boutique">Réinitialiser les filtres</a>
    </div>
<?php endif ?>
