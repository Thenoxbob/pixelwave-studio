<?php use Pixelwave\Core\Csrf; ?>
<section class="formulaire-carte">
    <h1>Connexion</h1>
    <p class="intro">Accède à ta bibliothèque, à tes clés et au suivi de tes signalements.</p>
    <?php if ($erreur): ?><div class="alerte alerte--erreur" role="alert"><?= e($erreur) ?></div><?php endif ?>
    <form method="post" action="/connexion" class="formulaire" novalidate>
        <?= Csrf::champ() ?>
        <input type="hidden" name="retour" value="<?= e($retour) ?>">
        <label class="champ">
            <span class="champ__label">Adresse e-mail</span>
            <input type="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
        </label>
        <label class="champ">
            <span class="champ__label">Mot de passe</span>
            <input type="password" name="mot_de_passe" autocomplete="current-password" required>
        </label>
        <button class="btn btn--plein btn--grand btn--bloc">Se connecter</button>
    </form>
    <p class="formulaire-carte__pied">Pas encore de compte ? <a class="lien" href="/inscription">Créer un compte</a></p>
</section>
