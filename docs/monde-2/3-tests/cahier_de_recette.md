# Cahier de recette · Portail joueurs PixelWave

| Projet | Version testée | Environnement | Jeu de données | Date de la campagne |
|---|---|---|---|---|
| Portail joueurs (Monde 2) | 1.0 (branche `main`) | PHP 8.4, MariaDB 11.4, Edge, poste Windows | `database/02_jeu_de_donnees.sql` (base réinitialisée avant la campagne) | 06/11/2026 |

**Comptes de test** : voir [`database/README.md`](../../../database/README.md) (joueur `LeaPixel`, joueur sans achat `NoobSaibot`, support `karim.support`, compte désactivé `OldTimer`).

**Mode d'exécution** : **M** = exécuté à la main dans le navigateur · **A** = vérifié par un test automatisé (référence indiquée) · **M+A** = les deux.

**Statut** : ✅ conforme · ❌ non conforme · ⏳ non exécuté.

## 1. Comptes et authentification

| ID | Exigence (cahier des charges) | Préconditions | Étapes | Résultat attendu | Résultat obtenu | Mode | Statut |
|---|---|---|---|---|---|---|---|
| REC-01 | Créer un compte joueur | — | `/inscription`, pseudo `NouvelleRecrue`, e-mail valide, mot de passe `Un-Mot2Passe!`, cocher les CGU, valider | compte créé, joueur connecté, redirigé vers « Mon compte » ; mot de passe stocké haché | conforme ; empreinte bcrypt en base, e-mail normalisé en minuscules | A (`CompteTest::testInscriptionHacheLeMotDePasse`) | ✅ |
| REC-02 | Contrôler les saisies à l'inscription | compte `LeaPixel` existant | s'inscrire avec le pseudo `LeaPixel`, l'e-mail de Léa et le mot de passe `azerty` | 3 erreurs affichées sous les champs (pseudo pris, e-mail déjà utilisé, mot de passe trop faible), aucun compte créé | conforme | A (`testInscriptionRefuseDoublonsEtMotDePasseFaible`) | ✅ |
| REC-03 | Authentifier de manière sécurisée | — | `/connexion` avec `lea.martin@exemple.test` et le bon mot de passe | message « Bon retour, LeaPixel ! », page « Mon compte » | conforme | M + A (SEC-01) | ✅ |
| REC-04 | Ne pas révéler l'existence d'un compte | — | se connecter avec un mauvais mot de passe, puis avec un e-mail inconnu | même message « E-mail ou mot de passe incorrect. » dans les deux cas | conforme | A (`testMauvaisMotDePasseEtCompteInconnuOntLeMemeMessage`) | ✅ |
| REC-05 | Bloquer la force brute | — | 5 mots de passe faux sur le même compte, puis le bon | 6e tentative refusée : « Trop de tentatives... Réessaie dans 15 minutes. » | conforme (HTTP 429) | A (`testBlocageApresCinqEchecs`, SEC-09) | ✅ |
| REC-06 | Désactiver un compte | `OldTimer` désactivé | se connecter avec `paul.roux@exemple.test` | connexion refusée | conforme | A (`testCompteDesactiveNePeutPasSeConnecter`) | ✅ |
| REC-07 | Protéger les pages privées | déconnecté | ouvrir `/bibliotheque` | redirection vers `/connexion?retour=/bibliotheque`, puis retour à la bibliothèque après connexion | conforme (HTTP 303) ; à la main : message « Connecte-toi pour accéder à cette page. » puis retour sur la bibliothèque (voir ANO-05) | M + A (SEC-07) | ✅ |

## 2. Boutique et commande (parcours Figma n°1 : acheter le DLC Circuit Nocturne)

| ID | Exigence | Préconditions | Étapes | Résultat attendu | Résultat obtenu | Mode | Statut |
|---|---|---|---|---|---|---|---|
| REC-08 | Consulter la boutique | — | `/boutique` | 8 produits en vente, le produit retiré n'apparaît pas ; badges « -30 % », « Nouveau », « Précommande » | conforme | M + A (`testCatalogueNAfficheQueLesProduitsEnVente`, API-02) | ✅ |
| REC-09 | Filtrer et rechercher | — | clic sur « Promotions », puis recherche « neon » | 2 produits remisés (5,59 € et 17,49 €) ; 3 résultats « Neon Drift » | conforme | M + A (API-03, `testRechercheNeTraitePasLesJokersSql`) | ✅ |
| REC-10 | Acheter un produit | connecté `NoobSaibot` | fiche Abyssal Tides → PC → « Ajouter au panier » → « Payer et recevoir mes clés » | commande `CMD-2026-...` créée au prix remisé 17,49 €, panier vidé, jeu visible dans la bibliothèque, stock diminué d'une clé | conforme ; à la main : LeaPixel achète Starlit Courier PC → CMD-2026-000011, clé attribuée, panier vidé | M + A (`testAjoutAuPanierPuisCommandeAttribueUneCle`) | ✅ |
| REC-11 | Refuser une plateforme en rupture | connecté | fiche Starlit Courier | Switch grisé « Rupture » ; via l'API : erreur 409 `stock_epuise` | conforme | M + A (`testRuptureDeStockEstSignalee`, API-19) | ✅ |
| REC-12 | Ne pas vendre deux fois le même produit | `LeaPixel` possède Neon Drift PC | fiche Neon Drift | PC grisé « Déjà possédé » ; via l'API : 409 `deja_possede` | conforme | M + A (API-21) | ✅ |
| REC-13 | Commande tout ou rien | panier de 2 articles, la dernière clé du 2e vendue entre-temps | valider le panier | message « Certains articles ne sont plus disponibles », aucune commande créée, aucune clé consommée, panier intact | conforme | A (`testCommandeAnnuleeEntierementSiUneCleManque`) | ✅ |

## 3. Bibliothèque et clés (parcours Figma n°2 : récupérer et copier la clé)

| ID | Exigence | Préconditions | Étapes | Résultat attendu | Résultat obtenu | Mode | Statut |
|---|---|---|---|---|---|---|---|
| REC-14 | Consulter ses jeux et clés | connecté `LeaPixel` | `/bibliotheque` | 4 produits ; clé du Circuit Nocturne masquée `•••••-•••••-K79ZN`, les autres affichées | conforme | M + A (API-16) | ✅ |
| REC-15 | Révéler et copier une clé | idem | « Afficher la clé » puis « Copier » | la clé `3V835-NUTAC-K79ZN` s'affiche sans rechargement (appel API), le bouton devient « Copier » puis « Copiée ! » | conforme (requête `POST /api/v1/moi/cles/5/revelation` → 200) | M + A (API-18) | ✅ |
| REC-16 | Interdire l'accès aux clés d'un autre | connecté `LeaPixel` | appeler la révélation de la clé n°3 (appartenant à DriftKing) | 404, aucune information divulguée | conforme | A (`testUnJoueurNePeutPasReleverLaCleDUnAutre`, API-17) | ✅ |
| REC-17 | Une commande remboursée retire l'accès | `DriftKing` a une commande remboursée | `/bibliotheque` | seul Neon Drift apparaît ; Tiny Forge PC peut être racheté | conforme | A (`testCommandeRembourseeNeDonnePlusAccesAuJeu`) | ✅ |

## 4. Support (parcours Figma n°3 : signaler un bug et suivre le ticket)

| ID | Exigence | Préconditions | Étapes | Résultat attendu | Résultat obtenu | Mode | Statut |
|---|---|---|---|---|---|---|---|
| REC-18 | Signaler un bug | connecté `LeaPixel` | `/support/nouveau`, jeu Circuit Nocturne, PC, 1.4.0, titre et description, envoyer | référence `BUG-2026-xxxx` affichée, statut « Nouveau », priorité « À qualifier », historique créé | conforme ; à la main : BUG-2026-0008 « Les phares ne s'allument pas sous la pluie » créé, statut Nouveau | M + A (`testSignalementCreeUnTicketNouveauAvecHistorique`, API-26) | ✅ |
| REC-19 | Contrôler le formulaire de signalement | idem | titre « Bug », description « ça plante », plateforme inconnue | erreurs sous les champs, aucun ticket créé | conforme | A (`testSignalementIncompletEstRefuse`, API-25) | ✅ |
| REC-20 | Qualifier, prioriser, affecter (ITIL) | connecté `karim.support` | ticket BUG-2026-0004 → statut « En cours », priorité « Basse », technicien `julie.qa` | ticket mis à jour, ligne ajoutée à l'historique | conforme ; à la main sur BUG-2026-0008 : En cours, Moyenne, julie.qa, commentaire visible dans l'historique | M + A (`testQualificationParLeSupportEstTracee`, API-29) | ✅ |
| REC-21 | Échanger joueur ↔ support | ticket « En attente du joueur » | le joueur répond dans le ticket | message ajouté, le ticket repasse « En cours » | conforme | A (`testReponseDuJoueurRelanceUnTicketEnAttente`) | ✅ |
| REC-22 | Cloisonner les tickets et les notes internes | `LeaPixel`, ticket de DriftKing | ouvrir `/support/BUG-2026-0001` ; puis le même ticket en tant que support | joueur : 404 ; support : 4 messages dont la note interne (hachurée) | conforme | M + A (`testUnJoueurNeVoitPasLeTicketDUnAutre`, `testLeSupportVoitTousLesTicketsEtLesNotesInternes`) | ✅ |
| REC-23 | Un joueur ne peut pas modifier un ticket | `LeaPixel` | `PATCH /api/v1/tickets/{sa référence}` avec une priorité | 403 `acces_refuse` | conforme | A (API-28) | ✅ |

## 5. Sécurité transverse

| ID | Exigence | Étapes | Résultat attendu | Résultat obtenu | Mode | Statut |
|---|---|---|---|---|---|---|
| REC-24 | Sécuriser les services exposés sur Internet | formulaire sans jeton CSRF ; appel API par cookie sans en-tête CSRF ; titre de ticket `<script>alert("xss")</script>` ; recherche `' OR 1=1 -- ` ; lecture des en-têtes | 403 ; 403 ; script affiché comme du texte ; 0 résultat ; CSP, X-Frame-Options, nosniff, cookie HttpOnly + SameSite présents | conforme sur les 6 points | A (SEC-02 à SEC-08) | ✅ |

## Bilan

**24 scénarios, 24 conformes.** Les anomalies rencontrées pendant la campagne ont été corrigées puis re-testées : voir [anomalies.md](anomalies.md).
