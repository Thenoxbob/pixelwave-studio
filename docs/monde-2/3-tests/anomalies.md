# Suivi des anomalies · Campagne du 06/11/2026

Gravité : **bloquante** (une fonctionnalité est inutilisable) · **majeure** (fonctionne mal, contournement possible) · **mineure** (gêne visuelle ou de confort).

Chaque anomalie est à reporter en issue GitHub avec le modèle [`anomalie.md`](../../../.github/ISSUE_TEMPLATE/anomalie.md), puis fermée par le commit de correction.

| ID | Titre | Écran / route | Scénario | Gravité | Cause | Correction | Re-test | Statut |
|---|---|---|---|---|---|---|---|---|
| ANO-01 | La page « Ma bibliothèque » affiche « Warning: Array to string conversion » au lieu des jeux | `/bibliotheque` | REC-14 | **Bloquante** | La vue reçoit une variable `$contenu` (liste des jeux) qui écrasait la variable `$contenu` du gabarit commun (HTML de la page). | Variable du gabarit renommée `$corpsPage` (`View::rendre`, `layout.php`). | page affichée, 4 jeux ; tests auto OK | ✅ Corrigée |
| ANO-02 | Les boutons des formulaires occupent toute la largeur du panneau | `/compte` | REC-01 | Mineure | Le conteneur `.formulaire` est en flexbox colonne : les enfants s'étirent par défaut. | `.formulaire > .btn:not(.btn--bloc) { align-self: flex-start }` | visuel conforme à la maquette | ✅ Corrigée |
| ANO-03 | Cartes « Promotions en cours » de l'accueil beaucoup plus hautes que sur la maquette | `/` | REC-08 | Mineure | Même ratio d'image (4/5) pour les grilles à 4 et à 5 colonnes. | Ratio 4/3 sur l'accueil, 3/4 dans la boutique. | conforme à la maquette Figma | ✅ Corrigée |
| ANO-04 | Blocs de clé non alignés d'une ligne à l'autre de la bibliothèque | `/bibliotheque` | REC-14 | Mineure | Largeur de colonne variable (`minmax(300px, auto)`) selon la longueur du bouton. | Colonne fixe de 340 px. | blocs alignés | ✅ Corrigée |
| ANO-05 | Le message « Connecte-toi pour accéder à cette page. » s'affiche deux fois | `/connexion?retour=...` | REC-07 | Mineure | Deux accès successifs à une page protégée ajoutaient deux fois le même message flash en session. | Un message flash identique n'est plus ajouté deux fois (`Session::flash`). | un seul message après deux accès | ✅ Corrigée |

## Erreurs dans les tests eux-mêmes

Deux tests automatisés ont échoué au premier lancement **à cause d'une erreur dans le test, pas dans l'application** :

| Test | Problème | Correction |
|---|---|---|
| `BoutiqueTest::testFiltrePromotionEtPrixRemise` | le test attendait Abyssal Tides en premier, alors que le tri par défaut est « Nouveautés » (Atelier d'hiver est plus récent) | ordre attendu corrigé |
| `CompteTest::testInscriptionRefuseDoublonsEtMotDePasseFaible` | le test supposait un ordre précis des champs en erreur | comparaison indépendante de l'ordre |

## Bilan

5 anomalies détectées (1 bloquante, 4 mineures), 5 corrigées et re-testées, **0 ouverte**. La campagne de non-régression complète a été relancée après la dernière correction (voir [rapport d'exécution](rapport_execution.md)).
