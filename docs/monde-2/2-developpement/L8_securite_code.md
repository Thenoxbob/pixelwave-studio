# Livrable 8 : sécurité du code

J'ai repris le code du front et du back en me basant sur le Top 10 de l'OWASP, pour vérifier chaque risque et noter ce qui le protège.

## Les mesures en place

| Risque | Ce que j'ai fait | Où |
|---|---|---|
| injection SQL | requêtes préparées avec PDO partout (`ATTR_EMULATE_PREPARES = false`), aucune requête construite avec une saisie ; dans la recherche, les caractères `%` et `_` sont neutralisés | `Repository/*` |
| XSS | tout ce qui est affiché passe par `e()` (`htmlspecialchars`) ; en-tête Content-Security-Policy `script-src 'self'`, donc aucun script en ligne n'est exécuté | `helpers.php`, `index.php` |
| CSRF | jeton aléatoire par session vérifié sur tous les formulaires, en-tête `X-CSRF-Token` pour les appels à l'API faits avec le cookie, cookie `SameSite=Lax` | `Core/Csrf.php` |
| validation des données | toutes les données reçues sont vérifiées côté serveur (`Validator`), même si le JavaScript les a déjà contrôlées | `Core/Validator.php`, services |
| accès aux données des autres | chaque requête filtre sur l'utilisateur connecté (`WHERE id_utilisateur = ?`) ; la ressource d'un autre donne 404 ; les rôles sont vérifiés côté serveur | services, repositories |
| mots de passe et force brute | bcrypt, règles de complexité, blocage après 5 échecs (voir le [livrable 7](L7_authentification.md)) | `CompteService` |
| session | cookie `HttpOnly`, `Secure`, `SameSite`, mode strict, nouvel identifiant à la connexion | `Core/Session.php` |
| fuite d'informations | réponses de l'API construites champ par champ ; en production, message d'erreur neutre et détail seulement dans le journal | `ApiController`, `index.php` |
| redirection vers un autre site | les redirections n'acceptent que des chemins internes | `Core/View.php` |
| clickjacking | `X-Frame-Options: DENY` et `frame-ancestors 'none'` : le portail refuse d'être affiché dans une iframe | `index.php` |
| autres en-têtes | `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy` | `index.php` |
| moindre privilège | le compte MariaDB de l'appli n'a que `SELECT`, `INSERT`, `UPDATE` et `DELETE` ; la base n'écoute qu'en local | `infra/srv-portail/install.sh` |
| secrets | aucun mot de passe ni clé dans le code ou sur GitHub (voir plus bas) | `.gitignore` |
| dépendances vulnérables | aucune dépendance externe : pas de bibliothèque à surveiller | |

## Les secrets et le dépôt Git

- La connexion à la base et l'URL du webhook Discord sont dans `portail/config/config.php`, qui est dans le `.gitignore`. Sur GitHub il n'y a que `config.example.php`, avec `A_REMPLACER` à la place du mot de passe.
- Les configurations de test (`tests/config.test.php`, `tests/config.recette.php`) sont aussi ignorées.
- Sur le serveur, le script d'installation génère un mot de passe aléatoire pour la base et l'écrit directement dans `config.php` (droits 640), sans l'afficher.
- Les seuls mots de passe présents sur GitHub sont ceux des comptes de démonstration du jeu de données fictif, et c'est indiqué clairement dans `database/README.md`.

## Comment j'ai vérifié

- `php -l` sur tous les fichiers PHP pour repérer les erreurs de syntaxe.
- Une relecture des requêtes SQL pour vérifier qu'aucune n'est construite avec une saisie (la seule concaténation est le `ORDER BY`, choisi dans une liste fixe avec `match`, et le `LIMIT`, qui est un entier contrôlé).
- 11 tests de sécurité automatiques qui attaquent le portail en HTTP : formulaire sans jeton CSRF, appel API sans en-tête CSRF, script injecté dans un ticket, injection SQL dans la recherche, force brute, cookie de session, en-têtes, jeton révoqué. Les 11 passent (voir la [mission Tests](../3-tests/README.md)).
- Une recherche dans l'historique Git pour vérifier qu'aucun `config.php` n'a été commité.

## Ce qui a été corrigé pendant cette vérification

- Une clé non affichée ne sort plus jamais du serveur, même dans les données envoyées à la page : le masquage (`•••••-•••••-K79ZN`) est fait directement en SQL.
- Le script de capture d'écran que j'ai utilisé pendant les tests a été supprimé du dossier `public/`, pour qu'aucun outil de développement ne reste accessible sur le serveur.
