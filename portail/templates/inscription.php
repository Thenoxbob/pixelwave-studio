<?php
use Pixelwave\Core\Csrf;
use Pixelwave\Core\View;

$err = fn(string $nom) => View::partiel('partials/champ_erreur', ['erreurs' => $erreurs, 'nom' => $nom]);
$aria = fn(string $nom) => !empty($erreurs[$nom]) ? ' aria-invalid="true" aria-describedby="err-' . $nom . '"' : '';
?>
<section class="formulaire-carte">
    <h1>Créer un compte joueur</h1>
    <p class="intro">Nous ne demandons que le strict nécessaire : un pseudo, un e-mail et un mot de passe.</p>
    <?php if (!empty($erreurs['general'])): ?><div class="alerte alerte--erreur" role="alert"><?= e($erreurs['general']) ?></div><?php endif ?>
    <form method="post" action="/inscription" class="formulaire" novalidate data-valider>
        <?= Csrf::champ() ?>
        <label class="champ">
            <span class="champ__label">Pseudo</span>
            <input name="pseudo" value="<?= e($saisie['pseudo'] ?? '') ?>" minlength="3" maxlength="30" pattern="[A-Za-z0-9_.\-]+" data-message-motif="Lettres, chiffres, point, tiret et tiret bas uniquement." autocomplete="nickname" required<?= $aria('pseudo') ?>>
            <?= $err('pseudo') ?>
        </label>
        <label class="champ">
            <span class="champ__label">Adresse e-mail</span>
            <input type="email" name="email" value="<?= e($saisie['email'] ?? '') ?>" autocomplete="email" required<?= $aria('email') ?>>
            <?= $err('email') ?>
        </label>
        <label class="champ">
            <span class="champ__label">Mot de passe</span>
            <input type="password" name="mot_de_passe" autocomplete="new-password" data-regle="mot-de-passe" required<?= $aria('mot_de_passe') ?>>
            <span class="champ__aide">10 caractères minimum, dont une lettre, un chiffre et un caractère spécial.</span>
            <?= $err('mot_de_passe') ?>
        </label>
        <label class="champ">
            <span class="champ__label">Confirmer le mot de passe</span>
            <input type="password" name="confirmation" autocomplete="new-password" data-confirme="mot_de_passe" required<?= $aria('confirmation') ?>>
            <?= $err('confirmation') ?>
        </label>
        <label class="case">
            <input type="checkbox" name="newsletter" value="1"<?= !empty($saisie['newsletter']) ? ' checked' : '' ?>>
            <span>Je souhaite recevoir les actualités du studio par e-mail (facultatif).</span>
        </label>
        <label class="case">
            <input type="checkbox" name="cgu" value="1" required<?= !empty($saisie['cgu']) ? ' checked' : '' ?><?= $aria('cgu') ?>>
            <span>J'accepte les <a class="lien" href="/aide#cgv">conditions d'utilisation</a> et la <a class="lien" href="/confidentialite">politique de confidentialité</a>.</span>
        </label>
        <?= $err('cgu') ?>
        <button class="btn btn--plein btn--grand btn--bloc">Créer mon compte</button>
    </form>
    <p class="formulaire-carte__pied">Déjà inscrit ? <a class="lien" href="/connexion">Se connecter</a></p>
</section>
