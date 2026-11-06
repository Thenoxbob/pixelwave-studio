# Livrable 9 : historique Git

Dépôt du projet : https://github.com/Thenoxbob/pixelwave-studio

## L'historique

| Commit | Date | Message |
|---|---|---|
| badb006 | 28/09/2026 | Initial commit |
| 1c40f54 | 28/09/2026 | Add files via upload (cahier des charges) |
| a07fee6 | 15/10/2026 | Add files via upload (architecture, schéma réseau, planning) |
| 778c2b0 | 20/10/2026 | Ajout de la section maquettage au README et du modèle de compte rendu du test utilisateur |
| d28366c | 20/10/2026 | Ajout des liens du fichier Figma et du prototype |
| f9ced5e | 06/11/2026 | Monde 2 · Conception Backend : modèle de données, SQL et API REST |
| 9a0da84 | 06/11/2026 | Monde 2 · Développement : portail joueurs PHP / MariaDB |
| 06550b2 | 06/11/2026 | Monde 2 · Mise en place des tests : stratégie, recette et PV |
| 013bc9c | 06/11/2026 | Monde 2 · Mise en place de l'infrastructure : Debian, Apache, BIND9, Kea |
| d0bd9c2 | 06/11/2026 | Docs Monde 2 : réécriture plus simple et plus lisible |
| fc6d7a7 | 06/11/2026 | Validation des formulaires en JavaScript |
| 9089c03 | 06/11/2026 | Capture de la validation JS du formulaire d'inscription |
| b23c913 | 06/11/2026 | Fusion de la branche feature/validation-js |

On peut retrouver les grandes étapes du projet : les documents du Monde 1, puis une étape par mission du Monde 2, puis les améliorations. Chaque commit a un message qui dit ce qu'il contient, et les commits du Monde 2 ont une description plus détaillée (`git log` pour la voir).

## Les branches

Pour la validation des formulaires en JavaScript, j'ai travaillé sur une branche à part, `feature/validation-js`. Je l'ai fusionnée dans `main` seulement après avoir testé dans le navigateur et relancé les tests automatiques :

```
*   b23c913 Fusion de la branche feature/validation-js
|\
| * 9089c03 Capture de la validation JS du formulaire d'inscription
| * fc6d7a7 Validation des formulaires en JavaScript
|/
* d0bd9c2 Docs Monde 2 : réécriture plus simple et plus lisible
```

J'ai utilisé `git merge --no-ff` pour garder la trace de la branche dans l'historique, puis je l'ai supprimée une fois fusionnée.

## Ce qui n'est pas versionné

Le fichier [`.gitignore`](../../../.gitignore) exclut :

- `portail/config/config.php` et les configurations de test, qui contiennent les mots de passe de la base ;
- les fichiers générés (résultats de la recette, cache de PHPUnit, dossier `vendor/` de Composer) ;
- les fichiers système et d'éditeur (`.DS_Store`, `Thumbs.db`, `.vscode/`...).

J'ai vérifié dans tout l'historique qu'aucun de ces fichiers n'a été commité.

Le fichier [`.gitattributes`](../../../.gitattributes) force des fins de ligne Unix (LF). C'est important parce que je travaille sous Windows et que les scripts d'installation tournent sur Debian : un script avec des fins de ligne Windows ne s'exécute pas sous Linux.

## Ce que je dois améliorer

Mes commits du Monde 2 sont trop gros : chaque mission tient en un seul commit, ce qui rend plus difficile de retrouver une modification précise. À partir de maintenant, je fais un commit par fonctionnalité ou par correction, et une branche pour chaque nouvelle fonctionnalité, comme je l'ai fait pour la validation JavaScript.
