<section class="erreur-page">
    <p class="erreur-page__code"><?= (int) $statut ?></p>
    <h1><?= $statut === 404 ? 'Page introuvable' : ($statut === 403 ? 'Accès refusé' : 'Oups') ?></h1>
    <p><?= e($message) ?></p>
    <a class="btn btn--plein" href="/">Retour à l'accueil</a>
</section>
