<div class="titre-ligne">
    <h1 class="titre-page"><?= e($titre) ?></h1>
    <?php if (!$staff): ?><a class="btn btn--plein" href="/support/nouveau">Signaler un bug</a><?php endif ?>
</div>

<?php if ($staff): ?>
    <nav class="filtres__types" aria-label="Filtrer par statut">
        <a class="puce<?= $statut === '' ? ' puce--active' : '' ?>" href="/support">Ouverts</a>
        <?php foreach ($statuts as $s): ?>
            <a class="puce<?= $statut === $s['code'] ? ' puce--active' : '' ?>" href="/support?statut=<?= e($s['code']) ?>"><?= e($s['libelle']) ?></a>
        <?php endforeach ?>
    </nav>
<?php else: ?>
    <p class="intro">Retrouve ici tes signalements et les réponses de l'équipe. <a class="lien" href="/aide">Consulter l'aide</a></p>
<?php endif ?>

<?php if (!$tickets): ?>
    <div class="vide"><p><?= $staff ? 'Aucun ticket dans cette file.' : "Tu n'as encore rien signalé. Tout roule !" ?></p></div>
<?php else: ?>
    <table class="tableau tableau--tickets">
        <thead>
            <tr><th>Référence</th><th>Titre</th><th>Jeu</th><?php if ($staff): ?><th>Joueur</th><th>Priorité</th><th>Technicien</th><?php endif ?><th>Statut</th><th>Mis à jour</th></tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
            <tr>
                <td><a class="lien" href="/support/<?= e($t['reference']) ?>"><?= e($t['reference']) ?></a></td>
                <td><?= e($t['titre']) ?></td>
                <td><?= e($t['produit'] ?? '—') ?></td>
                <?php if ($staff): ?>
                    <td><?= e($t['auteur']) ?></td>
                    <td><?= $t['priorite'] ? '<span class="priorite priorite--' . e($t['priorite']) . '">' . e($t['priorite_libelle']) . '</span>' : '<em>À qualifier</em>' ?></td>
                    <td><?= e($t['technicien'] ?? '—') ?></td>
                <?php endif ?>
                <td><span class="statut statut--<?= e($t['statut']) ?>"><?= e($t['statut_libelle']) ?></span></td>
                <td><?= e(date_fr($t['date_maj'])) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
