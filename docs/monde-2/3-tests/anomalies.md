# Suivi des anomalies (campagne du 06/11/2026)

Pour la gravité, j'ai utilisé trois niveaux : bloquante quand une fonctionnalité ne marche plus du tout, majeure quand elle marche mal mais qu'on peut contourner le problème, et mineure quand c'est un souci visuel ou de confort.

Chaque anomalie est à reporter dans une issue GitHub avec le modèle [`anomalie.md`](../../../.github/ISSUE_TEMPLATE/anomalie.md), puis à fermer avec le commit de correction.

| ID | Problème | Page | Scénario | Gravité | Cause | Correction | Statut |
|---|---|---|---|---|---|---|---|
| ANO-01 | la page « Ma bibliothèque » affiche « Warning: Array to string conversion » au lieu des jeux | `/bibliotheque` | REC-14 | bloquante | la vue reçoit une variable `$contenu` (la liste des jeux) qui écrasait la variable `$contenu` du gabarit commun (le HTML de la page) | j'ai renommé la variable du gabarit en `$corpsPage` (`View::rendre` et `layout.php`) ; la page affiche bien les 4 jeux | corrigée |
| ANO-02 | les boutons des formulaires prennent toute la largeur du bloc | `/compte` | REC-01 | mineure | le formulaire est en flexbox colonne, donc ses éléments s'étirent par défaut | ajout de `align-self: flex-start` sur les boutons ; c'est conforme à la maquette | corrigée |
| ANO-03 | les cartes « Promotions en cours » de l'accueil sont bien plus hautes que sur la maquette | `/` | REC-08 | mineure | le même format d'image (4/5) était utilisé pour la grille de 4 et celle de 5 colonnes | format 4/3 sur l'accueil et 3/4 dans la boutique ; c'est conforme à la maquette | corrigée |
| ANO-04 | les blocs de clé ne sont pas alignés d'une ligne à l'autre dans la bibliothèque | `/bibliotheque` | REC-14 | mineure | la largeur de la colonne changeait selon la taille du bouton | colonne de largeur fixe (340 px) ; les blocs sont alignés | corrigée |
| ANO-05 | le message « Connecte-toi pour accéder à cette page. » apparaît deux fois | `/connexion?retour=...` | REC-07 | mineure | quand on essaie deux fois d'ouvrir une page protégée, le même message était ajouté deux fois en session | un message identique n'est plus ajouté une deuxième fois (`Session::flash`) ; un seul message après deux essais | corrigée |

## Erreurs dans les tests eux-mêmes

Au premier lancement, deux tests automatiques ont échoué, mais l'erreur venait du test et pas de l'application :

- `BoutiqueTest::testFiltrePromotionEtPrixRemise` : le test attendait Abyssal Tides en premier, alors que le tri par défaut se fait par nouveautés et qu'Atelier d'hiver est plus récent. J'ai corrigé l'ordre attendu.
- `CompteTest::testInscriptionRefuseDoublonsEtMotDePasseFaible` : le test supposait que les champs en erreur arrivaient dans un ordre précis. Je compare maintenant sans tenir compte de l'ordre.

## Bilan

J'ai trouvé 5 anomalies (1 bloquante et 4 mineures). Elles sont toutes corrigées et retestées, il n'en reste aucune ouverte. Après la dernière correction, j'ai relancé tous les tests pour vérifier que rien d'autre n'était cassé (voir le [rapport d'exécution](rapport_execution.md)).
