# Cahier de recette du portail joueurs

| Projet | Version testée | Environnement | Données | Date |
|---|---|---|---|---|
| portail joueurs (Monde 2) | 1.0, branche `main` | PHP 8.4, MariaDB 11.4, navigateur Edge, PC Windows | `database/02_jeu_de_donnees.sql`, base remise à zéro avant la campagne | 06/11/2026 |

Les comptes utilisés sont listés dans [`database/README.md`](../../../database/README.md) : le joueur `LeaPixel`, le joueur sans achat `NoobSaibot`, le membre du support `karim.support` et le compte désactivé `OldTimer`.

Dans la colonne « Comment », « main » veut dire que j'ai fait le test moi-même dans le navigateur, et « auto » qu'il est vérifié par un test automatique (le nom du test est indiqué). Quand les deux sont indiqués, j'ai fait les deux.

## 1. Comptes et connexion

| ID | Besoin du cahier des charges | Avant le test | Étapes | Résultat attendu | Résultat obtenu | Comment | Statut |
|---|---|---|---|---|---|---|---|
| REC-01 | créer un compte joueur | | aller sur `/inscription`, pseudo `NouvelleRecrue`, un e-mail valide, mot de passe `Un-Mot2Passe!`, cocher les CGU, valider | le compte est créé, le joueur est connecté et arrive sur « Mon compte » ; le mot de passe est enregistré haché | OK ; empreinte bcrypt en base, e-mail enregistré en minuscules | auto (`CompteTest::testInscriptionHacheLeMotDePasse`) | OK |
| REC-02 | vérifier les saisies à l'inscription | le compte `LeaPixel` existe | s'inscrire avec le pseudo `LeaPixel`, l'e-mail de Léa et le mot de passe `azerty` | 3 erreurs sous les champs (pseudo déjà pris, e-mail déjà utilisé, mot de passe trop faible), aucun compte créé | OK | auto (`testInscriptionRefuseDoublonsEtMotDePasseFaible`) | OK |
| REC-03 | se connecter de façon sécurisée | | `/connexion` avec `lea.martin@exemple.test` et le bon mot de passe | message « Bon retour, LeaPixel ! » puis page « Mon compte » | OK | main + auto (SEC-01) | OK |
| REC-04 | ne pas révéler si un compte existe | | se connecter avec un mauvais mot de passe, puis avec un e-mail qui n'existe pas | le même message « E-mail ou mot de passe incorrect. » dans les deux cas | OK | auto (`testMauvaisMotDePasseEtCompteInconnuOntLeMemeMessage`) | OK |
| REC-05 | bloquer la force brute | | 5 mauvais mots de passe sur le même compte, puis le bon | la 6e tentative est refusée : « Trop de tentatives de connexion. Réessaie dans 15 minutes. » | OK (erreur 429) | auto (`testBlocageApresCinqEchecs`, SEC-09) | OK |
| REC-06 | pouvoir désactiver un compte | `OldTimer` est désactivé | se connecter avec `paul.roux@exemple.test` | connexion refusée | OK | auto (`testCompteDesactiveNePeutPasSeConnecter`) | OK |
| REC-07 | protéger les pages privées | être déconnecté | ouvrir `/bibliotheque` | renvoi vers `/connexion?retour=/bibliotheque`, puis retour sur la bibliothèque après la connexion | OK (code 303) ; à la main, le message « Connecte-toi pour accéder à cette page. » s'affiche puis on revient bien sur la bibliothèque (voir ANO-05) | main + auto (SEC-07) | OK |

## 2. Boutique et commande (parcours Figma n°1 : acheter le DLC Circuit Nocturne)

| ID | Besoin | Avant le test | Étapes | Résultat attendu | Résultat obtenu | Comment | Statut |
|---|---|---|---|---|---|---|---|
| REC-08 | voir la boutique | | ouvrir `/boutique` | 8 produits en vente, le produit retiré n'apparaît pas ; badges « -30 % », « Nouveau » et « Précommande » | OK | main + auto (`testCatalogueNAfficheQueLesProduitsEnVente`, API-02) | OK |
| REC-09 | filtrer et rechercher | | cliquer sur « Promotions », puis chercher « neon » | 2 produits en promo (5,59 € et 17,49 €), puis 3 résultats « Neon Drift » | OK | main + auto (API-03, `testRechercheNeTraitePasLesJokersSql`) | OK |
| REC-10 | acheter un produit | connecté avec `NoobSaibot` | fiche Abyssal Tides, choisir PC, « Ajouter au panier », puis « Payer et recevoir mes clés » | une commande `CMD-2026-...` est créée au prix promo de 17,49 €, le panier est vidé, le jeu apparaît dans la bibliothèque et il y a une clé de moins en stock | OK ; à la main, LeaPixel achète Starlit Courier PC : commande CMD-2026-000011, clé attribuée, panier vidé | main + auto (`testAjoutAuPanierPuisCommandeAttribueUneCle`) | OK |
| REC-11 | bloquer une plateforme en rupture | connecté | ouvrir la fiche de Starlit Courier | Switch est grisé avec « Rupture » ; par l'API, erreur 409 `stock_epuise` | OK | main + auto (`testRuptureDeStockEstSignalee`, API-19) | OK |
| REC-12 | ne pas vendre deux fois le même produit | `LeaPixel` a déjà Neon Drift sur PC | ouvrir la fiche de Neon Drift | PC est grisé avec « Déjà possédé » ; par l'API, erreur 409 `deja_possede` | OK | main + auto (API-21) | OK |
| REC-13 | une commande se fait en entier ou pas du tout | panier de 2 articles, la dernière clé du 2e a été vendue entre-temps | valider le panier | message « Certains articles ne sont plus disponibles », aucune commande créée, aucune clé utilisée, le panier ne change pas | OK | auto (`testCommandeAnnuleeEntierementSiUneCleManque`) | OK |

## 3. Bibliothèque et clés (parcours Figma n°2 : récupérer et copier la clé)

| ID | Besoin | Avant le test | Étapes | Résultat attendu | Résultat obtenu | Comment | Statut |
|---|---|---|---|---|---|---|---|
| REC-14 | voir ses jeux et ses clés | connecté avec `LeaPixel` | ouvrir `/bibliotheque` | 4 produits ; la clé du Circuit Nocturne est masquée (`•••••-•••••-K79ZN`), les autres sont visibles | OK | main + auto (API-16) | OK |
| REC-15 | afficher et copier une clé | pareil | cliquer sur « Afficher la clé » puis sur « Copier » | la clé `3V835-NUTAC-K79ZN` s'affiche sans recharger la page (appel à l'API), puis le bouton « Copier » passe à « Copiée ! » | OK (la requête `POST /api/v1/moi/cles/5/revelation` renvoie 200) | main + auto (API-18) | OK |
| REC-16 | empêcher d'accéder aux clés d'un autre | connecté avec `LeaPixel` | demander l'affichage de la clé n°3, qui appartient à DriftKing | erreur 404, aucune information donnée | OK | auto (`testUnJoueurNePeutPasReleverLaCleDUnAutre`, API-17) | OK |
| REC-17 | une commande remboursée retire le jeu | `DriftKing` a une commande remboursée | ouvrir `/bibliotheque` | seul Neon Drift apparaît, et il peut racheter Tiny Forge sur PC | OK | auto (`testCommandeRembourseeNeDonnePlusAccesAuJeu`) | OK |

## 4. Support (parcours Figma n°3 : signaler un bug et suivre le ticket)

| ID | Besoin | Avant le test | Étapes | Résultat attendu | Résultat obtenu | Comment | Statut |
|---|---|---|---|---|---|---|---|
| REC-18 | signaler un bug | connecté avec `LeaPixel` | `/support/nouveau`, jeu Circuit Nocturne, PC, version 1.4.0, un titre et une description, envoyer | une référence `BUG-2026-xxxx` s'affiche, statut « Nouveau », priorité « À qualifier », l'historique est créé | OK ; à la main, BUG-2026-0008 « Les phares ne s'allument pas sous la pluie » est créé au statut Nouveau | main + auto (`testSignalementCreeUnTicketNouveauAvecHistorique`, API-26) | OK |
| REC-19 | vérifier le formulaire de signalement | pareil | titre « Bug », description « ça plante », une plateforme qui n'existe pas | des erreurs sous les champs, aucun ticket créé | OK | auto (`testSignalementIncompletEstRefuse`, API-25) | OK |
| REC-20 | qualifier, prioriser et affecter un ticket (ITIL) | connecté avec `karim.support` | sur un ticket nouveau : statut « En cours », priorité « Basse », technicien `julie.qa` | le ticket est mis à jour et une ligne est ajoutée à l'historique | OK ; à la main sur BUG-2026-0008 : En cours, Moyenne, julie.qa, et le commentaire apparaît dans l'historique | main + auto (`testQualificationParLeSupportEstTracee`, API-29) | OK |
| REC-21 | échanger entre le joueur et le support | ticket « En attente du joueur » | le joueur répond dans le ticket | le message est ajouté et le ticket repasse « En cours » | OK | auto (`testReponseDuJoueurRelanceUnTicketEnAttente`) | OK |
| REC-22 | séparer les tickets des joueurs et cacher les notes internes | `LeaPixel`, ticket de DriftKing | ouvrir `/support/BUG-2026-0001`, puis le même ticket avec un compte support | pour le joueur, erreur 404 ; pour le support, 4 messages dont la note interne (sur fond hachuré) | OK | main + auto (`testUnJoueurNeVoitPasLeTicketDUnAutre`, `testLeSupportVoitTousLesTicketsEtLesNotesInternes`) | OK |
| REC-23 | un joueur ne peut pas modifier un ticket | `LeaPixel` | envoyer `PATCH /api/v1/tickets/{sa référence}` avec une priorité | erreur 403 `acces_refuse` | OK | auto (API-28) | OK |

## 5. Sécurité générale

| ID | Besoin | Étapes | Résultat attendu | Résultat obtenu | Comment | Statut |
|---|---|---|---|---|---|---|
| REC-24 | sécuriser les services accessibles depuis Internet | envoyer un formulaire sans jeton CSRF ; appeler l'API avec le cookie mais sans l'en-tête CSRF ; mettre `<script>alert("xss")</script>` dans un titre de ticket ; chercher `' OR 1=1 -- ` ; regarder les en-têtes | erreur 403 ; erreur 403 ; le script s'affiche comme du texte ; aucun résultat ; CSP, X-Frame-Options, nosniff et cookie HttpOnly + SameSite présents | OK sur les 6 points | auto (SEC-02 à SEC-08) | OK |

## Bilan

Sur les 24 scénarios, les 24 sont conformes. Les anomalies trouvées pendant la campagne ont été corrigées puis retestées, voir [anomalies.md](anomalies.md).
