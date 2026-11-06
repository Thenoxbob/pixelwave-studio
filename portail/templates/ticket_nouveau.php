<?php
use Pixelwave\Core\Csrf;
use Pixelwave\Core\View;
use Pixelwave\Repository\TicketRepository;

$err = fn(string $nom) => View::partiel('partials/champ_erreur', ['erreurs' => $erreurs, 'nom' => $nom]);
$val = fn(string $nom) => e($saisie[$nom] ?? '');
?>
<nav class="fil" aria-label="Fil d'Ariane"><a href="/support">Support</a> / <span aria-current="page">Signaler un bug</span></nav>
<section class="formulaire-carte formulaire-carte--large">
    <h1>Signaler un bug</h1>
    <p class="intro">Plus ta description est précise, plus vite l'équipe QA pourra reproduire et corriger le problème.</p>
    <?php if (!empty($erreurs['general'])): ?><div class="alerte alerte--erreur" role="alert"><?= e($erreurs['general']) ?></div><?php endif ?>
    <form method="post" action="/support/nouveau" class="formulaire" novalidate data-valider>
        <?= Csrf::champ() ?>
        <div class="formulaire__colonnes">
            <label class="champ">
                <span class="champ__label">Jeu concerné</span>
                <select name="id_produit">
                    <option value="">— Question générale / compte —</option>
                    <?php foreach ($produits as $p): ?>
                        <option value="<?= (int) $p['id_produit'] ?>"<?= (string) ($saisie['id_produit'] ?? '') === (string) $p['id_produit'] ? ' selected' : '' ?>><?= e($p['titre']) ?></option>
                    <?php endforeach ?>
                </select>
                <?= $err('id_produit') ?>
            </label>
            <label class="champ">
                <span class="champ__label">Plateforme</span>
                <select name="plateforme">
                    <option value="">—</option>
                    <?php foreach ($plateformes as $pf): ?>
                        <option value="<?= e($pf['code']) ?>"<?= ($saisie['plateforme'] ?? '') === $pf['code'] ? ' selected' : '' ?>><?= e($pf['nom']) ?></option>
                    <?php endforeach ?>
                </select>
                <?= $err('plateforme') ?>
            </label>
            <label class="champ">
                <span class="champ__label">Version du jeu <small>(facultatif)</small></span>
                <input name="version_jeu" value="<?= $val('version_jeu') ?>" maxlength="20" pattern="[0-9A-Za-z.\-]+" data-message-motif="Format attendu : 1.4.0" placeholder="ex. 1.4.0">
                <?= $err('version_jeu') ?>
            </label>
            <label class="champ">
                <span class="champ__label">Le problème arrive...</span>
                <select name="frequence">
                    <option value="">—</option>
                    <?php foreach (TicketRepository::FREQUENCES as $code => $lib): ?>
                        <option value="<?= $code ?>"<?= ($saisie['frequence'] ?? '') === $code ? ' selected' : '' ?>><?= $lib ?></option>
                    <?php endforeach ?>
                </select>
                <?= $err('frequence') ?>
            </label>
        </div>
        <label class="champ">
            <span class="champ__label">Titre</span>
            <input name="titre" value="<?= $val('titre') ?>" minlength="5" maxlength="150" placeholder="ex. Crash au chargement du circuit Port Néon" required>
            <?= $err('titre') ?>
        </label>
        <label class="champ">
            <span class="champ__label">Description</span>
            <textarea name="description" rows="7" minlength="20" maxlength="5000" data-compteur required placeholder="Que faisais-tu ? Que s'est-il passé ? Qu'attendais-tu ?"><?= $val('description') ?></textarea>
            <span class="champ__aide">20 caractères minimum. N'indique jamais ton mot de passe ni tes coordonnées bancaires.</span>
            <?= $err('description') ?>
        </label>
        <button class="btn btn--plein btn--grand">Envoyer le signalement</button>
    </form>
</section>
