# Livrable 1 · Quelles données stocker pour le portail joueurs ?

> Mission « Conception Backend » · Monde 2 · Compétence C12
> Texte prêt à déposer dans « Rédiger ma réponse » sur Rynapse (sections 1 à 4).

## 1. Démarche

Je suis parti des fonctionnalités du portail joueurs décrites dans le cahier des charges (§5 « Portail joueurs » et « Gestion des tickets ») et de la maquette Figma validée. Pour chaque action (créer un compte, acheter un DLC, récupérer une clé, signaler un bug...), j'ai listé les informations **créées, lues, modifiées ou supprimées**. Une donnée n'est retenue que si elle sert au moins une fonctionnalité (principe de minimisation du RGPD).

| Fonctionnalité du cahier des charges | Données nécessaires |
|---|---|
| Créer et gérer un compte joueur | compte (pseudo, e-mail, mot de passe), profil d'accès, consentement newsletter |
| Authentifier de façon sécurisée | empreinte du mot de passe, journal des tentatives de connexion, jetons d'API |
| Consulter les jeux possédés et les clés | commandes, lignes de commande, clés d'activation, plateformes |
| Accéder à la boutique (jeux et DLC) | produits, genres, plateformes disponibles, promotions, panier |
| Consulter les actualités | actualités, catégories |
| Signaler un bug et suivre son traitement (ITIL) | tickets, statuts, priorités, messages, historique |

## 2. Inventaire des données

**O** = obligatoire, **F** = facultative. **Sensibilité** : 🔴 forte (protection renforcée), 🟠 personnelle, ⚪ non sensible.

### Comptes et sécurité

| Donnée | Utilité | Type | O/F | Lien avec | Sensibilité |
|---|---|---|---|---|---|
| Pseudo | Nom affiché sur le portail et dans les tickets | texte (30) | O, unique | compte | 🟠 |
| Adresse e-mail | Identifiant de connexion, contact sur un ticket | texte (255) | O, unique | compte | 🟠 |
| Mot de passe | Authentification | texte (255) : **empreinte bcrypt**, jamais en clair | O | compte | 🔴 |
| Profil (joueur, support, admin) | Droits d'accès aux pages et à l'API | texte court | O | compte | ⚪ |
| Consentement newsletter | Envoi des actualités uniquement si accepté | booléen | O (faux par défaut) | compte | 🟠 |
| Compte actif | Désactiver un compte sans perdre l'historique | booléen | O | compte | ⚪ |
| Date d'inscription, dernière connexion | Suivi, purge des comptes inactifs | date et heure | O / F | compte | 🟠 |
| Tentative de connexion (e-mail, IP, date, réussite) | Bloquer la force brute | texte, texte (45), date, booléen | O | — | 🟠 (IP), conservée 30 jours |
| Jeton d'API (empreinte, nom, expiration, révoqué) | Accès d'une application (launcher, bot) à l'API | texte (64), texte, date, booléen | O | compte | 🔴 (empreinte SHA-256 seulement) |

### Catalogue et boutique

| Donnée | Utilité | Type | O/F | Lien avec | Sensibilité |
|---|---|---|---|---|---|
| Type (jeu ou DLC) | Filtre « Jeux / DLC » de la boutique | liste | O | produit | ⚪ |
| Titre, slug (URL), accroche, description | Fiche produit, recherche | texte | O (description F) | produit | ⚪ |
| Prix de base | Calcul du prix affiché | décimal (6,2) | O | produit | ⚪ |
| Date de sortie | Tri « Nouveautés », badge « Précommande » | date | F (non annoncée) | produit | ⚪ |
| Genre | Sous-titre des cartes (« Course arcade ») | texte | F (vide pour un DLC) | produit | ⚪ |
| Jeu parent | Rattacher un DLC à son jeu | lien vers produit | F (DLC uniquement) | produit | ⚪ |
| Plateformes (PC, PS5, Switch...) | Choix de la plateforme à l'achat | texte | O (au moins une) | produit ↔ plateforme | ⚪ |
| Promotion (pourcentage, début, fin) | Badge « -30 % » et prix remisé | entier, dates | F | produit | ⚪ |
| En vente / retiré | Retirer un produit sans casser les anciennes commandes | booléen | O | produit | ⚪ |
| Panier (produit, plateforme, date d'ajout) | Préparer une commande | liens, date | F | compte, produit | ⚪ |

### Achats et clés

| Donnée | Utilité | Type | O/F | Lien avec | Sensibilité |
|---|---|---|---|---|---|
| Commande (référence, date, statut) | Historique d'achat, remboursement | texte, date, liste | O | compte | 🟠 |
| Ligne de commande (produit, plateforme, **prix payé**) | Le prix payé reste exact même si le prix change ensuite | liens, décimal | O | commande | 🟠 |
| Clé d'activation (code) | Activer le jeu sur sa plateforme | texte (29) | O, unique | produit + plateforme, ligne | 🔴 (valeur marchande : masquée jusqu'à la révélation) |
| Dates d'import, d'attribution, de révélation | Gérer le stock, tracer l'affichage de la clé (remboursement impossible après révélation) | date et heure | O / F / F | clé | ⚪ |

> **Données bancaires : aucune.** Le paiement est confié à un prestataire certifié PCI-DSS ; le portail ne reçoit ni ne stocke jamais de numéro de carte. C'est la mesure de protection la plus efficace : ne pas détenir la donnée.

### Support et actualités

| Donnée | Utilité | Type | O/F | Lien avec | Sensibilité |
|---|---|---|---|---|---|
| Ticket : référence, titre, description | Comprendre et reproduire le bug | texte | O | compte (auteur) | 🟠 (texte libre) |
| Jeu, plateforme, version, fréquence | Qualifier le bug pour la QA | liens, texte, liste | F | produit, plateforme | ⚪ |
| Statut (nouveau → clos) | Suivi ITIL | liste ordonnée | O | ticket | ⚪ |
| Priorité (basse → critique) | Traiter d'abord l'urgent | liste | F (fixée par le support) | ticket | ⚪ |
| Technicien affecté | Savoir qui traite le ticket | lien vers compte | F | ticket | ⚪ |
| Messages (contenu, date, note interne) | Échanges joueur ↔ support | texte, date, booléen | O | ticket, compte | 🟠 |
| Historique des statuts (statut, auteur, date, commentaire) | Traçabilité du traitement | liens, date, texte | O | ticket | ⚪ |
| Actualité (titre, chapô, contenu, catégorie, date, publiée) | Page d'accueil et page Actualités | texte, liste, date, booléen | O (date F = brouillon) | compte (auteur), produit (F) | ⚪ |

## 3. Données volontairement **non** collectées

| Donnée écartée | Pourquoi |
|---|---|
| Nom, prénom, adresse postale, téléphone | Inutiles : les produits sont dématérialisés (clés). |
| Date de naissance | Le contrôle d'âge PEGI est fait par les plateformes (Steam, PSN, eShop). |
| Numéro de carte bancaire | Géré par le prestataire de paiement. |
| Captures d'écran jointes aux tickets | Reportées : risque de données personnelles visibles à l'écran. Évolution possible avec floutage. |

## 4. Protection des données sensibles

- **Mots de passe** : hachés avec `password_hash()` (bcrypt, coût adaptatif), vérifiés avec `password_verify()`. Politique : 10 caractères minimum, avec lettre, chiffre et caractère spécial.
- **Jetons d'API** : seule leur empreinte SHA-256 est stockée ; le jeton en clair n'est affiché qu'une fois.
- **Clés d'activation** : masquées (`•••••-•••••-K79ZN`) tant que le joueur ne les révèle pas ; un joueur ne peut révéler que ses propres clés.
- **Journal des connexions** : adresses IP purgées au bout de 30 jours.
- **Accès à la base** : compte applicatif limité à `SELECT, INSERT, UPDATE, DELETE`, base joignable uniquement en local sur le serveur de la DMZ.
- **Droits des personnes (RGPD)** : page « Confidentialité » qui liste chaque donnée, sa finalité et sa durée de conservation.

Ce recensement sert de base au modèle de données ([livrable 2](L2_representation_bdd.md)).
