<?php
use Pixelwave\Core\Csrf;
use Pixelwave\Core\View;

$err = fn(string $nom) => View::partiel('partials/champ_erreur', ['erreurs' => $erreurs, 'nom' => $nom]);
?>
<div class="titre-ligne">
    <h1 class="titre-page">Mon compte</h1>
    <form method="post" action="/deconnexion"><?= Csrf::champ() ?><button class="btn btn--contour">Se déconnecter</button></form>
</div>

<div class="compte">
    <section class="panneau">
        <h2>Profil</h2>
        <dl class="details">
            <dt>Pseudo</dt><dd><?= e($u['pseudo']) ?></dd>
            <dt>E-mail</dt><dd><?= e($u['email']) ?></dd>
            <dt>Profil</dt><dd><?= e($u['role_libelle']) ?></dd>
            <dt>Membre depuis</dt><dd><?= e(date_fr($u['date_inscription'])) ?></dd>
        </dl>
        <form method="post" action="/compte/preferences" class="formulaire">
            <?= Csrf::champ() ?>
            <label class="case">
                <input type="checkbox" name="newsletter" value="1"<?= $u['newsletter'] ? ' checked' : '' ?>>
                <span>Recevoir les actualités du studio par e-mail</span>
            </label>
            <button class="btn btn--contour btn--petit">Enregistrer</button>
        </form>
    </section>

    <section class="panneau" id="securite">
        <h2>Sécurité</h2>
        <form method="post" action="/compte/mot-de-passe" class="formulaire" novalidate data-valider>
            <?= Csrf::champ() ?>
            <label class="champ"><span class="champ__label">Mot de passe actuel</span>
                <input type="password" name="actuel" autocomplete="current-password" required><?= $err('actuel') ?></label>
            <label class="champ"><span class="champ__label">Nouveau mot de passe</span>
                <input type="password" name="nouveau" autocomplete="new-password" data-regle="mot-de-passe" required>
                <span class="champ__aide">10 caractères minimum, dont une lettre, un chiffre et un caractère spécial.</span><?= $err('nouveau') ?></label>
            <label class="champ"><span class="champ__label">Confirmer</span>
                <input type="password" name="confirmation" autocomplete="new-password" data-confirme="nouveau" required><?= $err('confirmation') ?></label>
            <button class="btn btn--plein">Changer le mot de passe</button>
        </form>
    </section>

    <section class="panneau panneau--large">
        <h2>Mes commandes</h2>
        <?php if (!$commandes): ?>
            <p class="note">Aucune commande pour le moment.</p>
        <?php else: ?>
            <table class="tableau">
                <thead><tr><th>Référence</th><th>Date</th><th>Articles</th><th>Montant</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($commandes as $c): ?>
                    <tr>
                        <td><?= e($c['reference']) ?></td>
                        <td><?= e(date_fr($c['date_commande'])) ?></td>
                        <td><?= (int) $c['nb_articles'] ?></td>
                        <td><?= prix($c['montant_total']) ?></td>
                        <td><span class="statut statut--<?= e($c['statut']) ?>"><?= ['payee' => 'Payée', 'remboursee' => 'Remboursée', 'annulee' => 'Annulée'][$c['statut']] ?></span></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </section>

    <section class="panneau panneau--large" id="api">
        <h2>Accès API</h2>
        <p class="note">Un jeton permet à une application (launcher, bot Discord...) d'accéder à ta bibliothèque et à tes tickets. Ne le partage jamais.</p>
        <?php if ($nouveauJeton): ?>
            <div class="alerte alerte--succes">
                Nouveau jeton créé. Copie-le maintenant, il ne sera plus jamais affiché :
                <code class="jeton" data-copiable><?= e($nouveauJeton) ?></code>
            </div>
        <?php endif ?>
        <form method="post" action="/compte/jetons" class="formulaire formulaire--ligne">
            <?= Csrf::champ() ?>
            <label class="champ"><span class="champ__label">Nom du jeton</span><input name="libelle" maxlength="50" placeholder="ex. Launcher PC"></label>
            <button class="btn btn--contour">Créer un jeton</button>
        </form>
        <?php if ($jetons): ?>
            <table class="tableau">
                <thead><tr><th>Nom</th><th>Créé le</th><th>Expire le</th><th>Dernière utilisation</th><th>État</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($jetons as $j): ?>
                    <tr>
                        <td><?= e($j['libelle']) ?></td>
                        <td><?= e(date_fr($j['date_creation'])) ?></td>
                        <td><?= e(date_fr($j['date_expiration'])) ?></td>
                        <td><?= e(date_fr($j['date_derniere_utilisation'], true)) ?></td>
                        <td><?= $j['revoque'] ? 'Révoqué' : ($j['valide'] ? 'Actif' : 'Expiré') ?></td>
                        <td>
                            <?php if ($j['valide']): ?>
                                <form method="post" action="/compte/jetons/<?= (int) $j['id_jeton'] ?>/revoquer"><?= Csrf::champ() ?><button class="btn btn--lien">Révoquer</button></form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </section>
</div>
