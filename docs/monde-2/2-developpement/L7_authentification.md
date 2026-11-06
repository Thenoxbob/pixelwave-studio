# Livrable 7 : authentification

## Inscription, connexion, déconnexion

| Action | Page | Route API | Code |
|---|---|---|---|
| inscription | `/inscription` | | `CompteService::inscrire()` |
| connexion | `/connexion` | `POST /api/v1/auth/jetons` | `CompteService::authentifier()` |
| déconnexion | bouton « Se déconnecter » (`POST /deconnexion`) | `DELETE /api/v1/auth/jetons/courant` | `Auth::deconnecter()`, `JetonRepository::revoquer()` |

À l'inscription, le pseudo doit faire entre 3 et 30 caractères (lettres, chiffres, point, tiret et tiret bas), l'e-mail doit être valide et pas encore utilisé, la confirmation du mot de passe doit être identique et les conditions d'utilisation doivent être acceptées. L'e-mail est enregistré en minuscules.

## Les mots de passe

- Ils sont hachés avec `password_hash($mdp, PASSWORD_DEFAULT)`, ce qui donne du bcrypt avec un sel aléatoire. Ils ne sont jamais enregistrés en clair.
- À la connexion, je les vérifie avec `password_verify()`. Si PHP recommande un algorithme plus récent, l'empreinte est refaite automatiquement (`password_needs_rehash`).
- Règle : au moins 10 caractères, avec une lettre, un chiffre et un caractère spécial.
- Le message d'erreur est le même que le compte existe ou non, et `password_verify()` est appelé dans les deux cas. Comme ça, on ne peut pas deviner quels e-mails sont inscrits, ni en lisant le message ni en mesurant le temps de réponse.
- Après 5 échecs sur un e-mail, ou 20 depuis une même adresse IP, en 15 minutes, les connexions sont bloquées (erreur 429). Chaque tentative est enregistrée dans `tentative_connexion`.

## Garder l'utilisateur connecté

Le portail utilise deux mécanismes, selon le client :

- **Sur le site** : une session PHP. Le cookie `PWSESSID` est `HttpOnly` (le JavaScript ne peut pas le lire), `Secure` en HTTPS et `SameSite=Lax`. L'identifiant de session est changé à la connexion, pour éviter la fixation de session. À la déconnexion, la session et le cookie sont supprimés.
- **Pour l'API** (launcher, bot Discord, Postman) : un jeton `Bearer` aléatoire (24 octets de `random_bytes`), valable 90 jours. La base ne garde que son empreinte SHA-256. Le joueur peut voir ses jetons et les révoquer depuis « Mon compte ».

## Routes protégées

- Les pages privées (bibliothèque, panier, compte, support) appellent `Auth::exiger()`. Sans connexion, on est renvoyé vers `/connexion?retour=...`, puis ramené sur la page demandée après s'être connecté.
- Les routes de l'API privées répondent 401 sans jeton valide.
- Les actions réservées au support (qualifier un ticket, voir toute la file) vérifient le rôle : 403 pour un joueur.
- Si un compte est désactivé, sa session et ses jetons ne marchent plus immédiatement.
- Toutes les données reçues sont vérifiées côté serveur par `Validator`, même si le JavaScript les a déjà vérifiées.

## Tests

| Scénario | Résultat attendu | Résultat | Test |
|---|---|---|---|
| connexion avec le bon mot de passe | connecté, redirigé vers « Mon compte » | OK | SEC-01, `testConnexionReussieEtJournalisee` |
| mauvais mot de passe | refusé, message neutre | OK | API-12, `testMauvaisMotDePasseEtCompteInconnuOntLeMemeMessage` |
| e-mail inconnu | même message que ci-dessus | OK | même test |
| compte désactivé | refusé | OK | `testCompteDesactiveNePeutPasSeConnecter` |
| 6e essai après 5 échecs | bloqué (429) | OK | SEC-09, `testBlocageApresCinqEchecs` |
| page privée sans connexion | redirection vers la connexion | OK | SEC-07 |
| API privée sans jeton | 401 `non_authentifie` | OK | API-09 |
| jeton inventé ou expiré | 401 `jeton_invalide` | OK | API-10, API-11 |
| déconnexion de l'API (révocation) | 204, puis le jeton ne marche plus | OK | SEC-10, SEC-11 |
| le mot de passe n'apparaît nulle part | aucune empreinte dans les réponses de l'API | OK | API-13 |

## Aucun secret exposé

Aucun mot de passe ni secret n'est dans le code ou sur GitHub. La connexion à la base est dans `config/config.php`, qui n'est pas versionné, et le serveur génère son propre mot de passe à l'installation. Les réponses de l'API sont construites champ par champ, sans jamais renvoyer `mot_de_passe_hash` ni `jeton_hash`.
