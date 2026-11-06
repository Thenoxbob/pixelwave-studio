# Livrable 1 : WordPress ou code sur mesure ?

## Ce que le portail doit faire

D'après le cahier des charges, le portail joueurs doit permettre de créer un compte, de voir ses jeux et ses clés d'activation, d'acheter des jeux et des DLC, de lire les actualités et de signaler un bug puis de suivre son traitement. Il doit être relié à une base de données, communiquer avec au moins un service externe et rester sécurisé, puisqu'il est exposé sur Internet.

## Comparaison des deux solutions

| Critère | WordPress | Code sur mesure (PHP + MariaDB) |
|---|---|---|
| Fonctionnalités | les actualités et les comptes existent de base ; pour la boutique il faut WooCommerce ; pour les clés d'activation et les tickets, il faut des extensions en plus ou du développement | tout est fait pour le besoin : clés attribuées à la commande, une clé par plateforme, tickets avec les étapes ITIL |
| Personnalisation | il faut adapter un thème à la maquette Figma, et le modèle de données de WordPress (articles et métadonnées) colle mal à nos tables | la maquette est reprise exactement, et la base est celle que j'ai conçue dans la mission Conception Backend |
| Délais | rapide pour un site vitrine, plus long dès qu'on sort de ce que proposent les extensions | plus long au début, mais pas de temps perdu à contourner des extensions |
| Sécurité | WordPress est très visé par les attaques, et chaque extension ajoute un risque ; il faut tout mettre à jour souvent | moins de code, que je connais entièrement ; je contrôle chaque requête SQL et chaque droit d'accès |
| Maintenance | mises à jour du cœur, du thème et des extensions, avec un risque d'incompatibilité | pas de dépendance à mettre à jour, mais tout le code est à notre charge |
| Compétences | prise en main rapide, mais il faut du PHP dès qu'on personnalise vraiment | PHP, SQL, HTML, CSS et JavaScript, ce qu'on a vu en cours |
| API pour le launcher et Discord | API REST de WordPress, mais il faudrait créer nos propres routes pour les clés et les tickets | API conçue dès le départ pour nos besoins (livrables 7 et 8 de la mission précédente) |

## Mon choix

J'ai choisi de **développer le portail sur mesure**, pour trois raisons :

1. les fonctions les plus importantes (les clés d'activation et le suivi des bugs) n'existent pas dans WordPress, il aurait fallu les développer quand même, en plus de gérer plusieurs extensions ;
2. le portail est exposé sur Internet et manipule des clés qui ont une valeur marchande, donc je préfère limiter le nombre de composants à surveiller ;
3. le modèle de données et l'API étaient déjà conçus dans la mission précédente, je n'avais qu'à les mettre en œuvre.

J'ai évité d'ajouter des dépendances : le portail n'utilise aucun framework ni aucune bibliothèque externe, juste PHP, PDO et la base MariaDB.

WordPress reste un bon choix pour le **site vitrine** du studio (présentation, jeux, actualités, contact), qui n'a pas besoin de fonctions particulières. Ce sera à voir dans une prochaine mission.

## Vérification

La solution répond aux besoins : les trois parcours de la maquette marchent de bout en bout (acheter un DLC, récupérer sa clé, signaler un bug et le suivre), et c'est vérifié par le cahier de recette de la [mission Tests](../3-tests/cahier_de_recette.md).
