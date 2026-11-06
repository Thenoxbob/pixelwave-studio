<?php
use Pixelwave\Core\Csrf;
use Pixelwave\Core\View;
use Pixelwave\Repository\TicketRepository;

$err = fn(string $nom) => View::partiel('partials/champ_erreur', ['erreurs' => $erreurs, 'nom' => $nom]);
$clos = $ticket['statut'] === 'clos';
?>
<nav class="fil" aria-label="Fil d'Ariane"><a href="/support">Support</a> / <span aria-current="page"><?= e($ticket['reference']) ?></span></nav>

<div class="ticket">
    <div class="ticket__principal">
        <header class="ticket__entete">
            <p class="surtitre"><?= e($ticket['reference']) ?> · ouvert le <?= e(date_fr($ticket['date_creation'], true)) ?> par <?= e($ticket['auteur']) ?></p>
            <h1><?= e($ticket['titre']) ?></h1>
            <span class="statut statut--<?= e($ticket['statut']) ?>"><?= e($ticket['statut_libelle']) ?></span>
        </header>

        <div class="panneau">
            <p class="ticket__description"><?= nl2br(e($ticket['description'])) ?></p>
        </div>

        <section id="messages" class="fil-messages" aria-label="Échanges">
            <?php foreach ($messages as $m): ?>
                <article class="message<?= $m['role'] !== 'joueur' ? ' message--support' : '' ?><?= $m['interne'] ? ' message--interne' : '' ?>">
                    <header>
                        <strong><?= e($m['auteur']) ?></strong>
                        <?php if ($m['role'] !== 'joueur'): ?><span class="badge badge--contour badge--teal">Équipe PixelWave</span><?php endif ?>
                        <?php if ($m['interne']): ?><span class="badge badge--contour">Note interne</span><?php endif ?>
                        <time datetime="<?= e($m['date_envoi']) ?>"><?= e(date_fr($m['date_envoi'], true)) ?></time>
                    </header>
                    <p><?= nl2br(e($m['contenu'])) ?></p>
                </article>
            <?php endforeach ?>
        </section>

        <?php if (!$clos): ?>
            <form method="post" action="/support/<?= e($ticket['reference']) ?>/messages" class="formulaire panneau" novalidate>
                <?= Csrf::champ() ?>
                <label class="champ">
                    <span class="champ__label"><?= $staff ? 'Répondre au joueur' : 'Ajouter un message' ?></span>
                    <textarea name="contenu" rows="4" maxlength="5000" required></textarea>
                    <?= $err('contenu') ?>
                </label>
                <?php if ($staff): ?>
                    <label class="case"><input type="checkbox" name="interne" value="1"><span>Note interne (invisible pour le joueur)</span></label>
                <?php endif ?>
                <button class="btn btn--plein">Envoyer</button>
            </form>
        <?php else: ?>
            <p class="note">Ce ticket est clos. Si le problème revient, <a class="lien" href="/support/nouveau">ouvre un nouveau signalement</a>.</p>
        <?php endif ?>
    </div>

    <aside class="ticket__cote">
        <div class="panneau">
            <h2>Détails</h2>
            <dl class="details">
                <dt>Jeu</dt><dd><?= e($ticket['produit'] ?? '—') ?></dd>
                <dt>Plateforme</dt><dd><?= e($ticket['plateforme'] ?? '—') ?></dd>
                <dt>Version</dt><dd><?= e($ticket['version_jeu'] ?? '—') ?></dd>
                <dt>Fréquence</dt><dd><?= e(TicketRepository::FREQUENCES[$ticket['frequence']] ?? '—') ?></dd>
                <dt>Priorité</dt><dd><?= e($ticket['priorite_libelle'] ?? 'À qualifier') ?></dd>
                <dt>Suivi par</dt><dd><?= e($ticket['technicien'] ?? 'Pas encore affecté') ?></dd>
            </dl>
        </div>

        <?php if ($staff): ?>
            <form method="post" action="/support/<?= e($ticket['reference']) ?>/traitement" class="panneau formulaire">
                <h2>Traitement</h2>
                <?= Csrf::champ() ?>
                <label class="champ"><span class="champ__label">Statut</span>
                    <select name="statut">
                        <?php foreach ($statuts as $s): ?>
                            <option value="<?= e($s['code']) ?>"<?= $s['code'] === $ticket['statut'] ? ' selected' : '' ?>><?= e($s['libelle']) ?></option>
                        <?php endforeach ?>
                    </select><?= $err('statut') ?></label>
                <label class="champ"><span class="champ__label">Priorité</span>
                    <select name="priorite">
                        <option value="">À qualifier</option>
                        <?php foreach ($priorites as $p): ?>
                            <option value="<?= e($p['code']) ?>"<?= $p['code'] === $ticket['priorite'] ? ' selected' : '' ?>><?= e($p['libelle']) ?></option>
                        <?php endforeach ?>
                    </select><?= $err('priorite') ?></label>
                <label class="champ"><span class="champ__label">Technicien</span>
                    <select name="id_technicien">
                        <option value="">Non affecté</option>
                        <?php foreach ($equipe as $m): ?>
                            <option value="<?= (int) $m['id_utilisateur'] ?>"<?= (int) $m['id_utilisateur'] === (int) $ticket['id_technicien'] ? ' selected' : '' ?>><?= e($m['pseudo']) ?></option>
                        <?php endforeach ?>
                    </select><?= $err('id_technicien') ?></label>
                <label class="champ"><span class="champ__label">Commentaire (historique)</span>
                    <input name="commentaire" maxlength="255"><?= $err('commentaire') ?></label>
                <button class="btn btn--plein btn--bloc">Mettre à jour</button>
            </form>
        <?php endif ?>

        <div class="panneau">
            <h2>Historique</h2>
            <ol class="historique">
                <?php foreach ($historique as $h): ?>
                    <li><strong><?= e($h['statut']) ?></strong> · <?= e(date_fr($h['date_changement'], true)) ?><br><small><?= e($h['auteur']) ?><?= $h['commentaire'] ? ' — ' . e($h['commentaire']) : '' ?></small></li>
                <?php endforeach ?>
            </ol>
        </div>
    </aside>
</div>
