# Livrable 6 : communication avec l'API

## Où le front appelle l'API

La plupart des pages du portail sont construites par le serveur (PHP), donc elles n'ont pas besoin d'appeler l'API. J'utilise `fetch()` là où ça améliore vraiment l'usage : l'**affichage d'une clé d'activation** dans « Ma bibliothèque ». Le joueur clique sur « Afficher la clé », la clé apparaît sans recharger la page, et il peut la copier tout de suite.

L'appel :

```js
const rep = await fetch(`/api/v1/moi/cles/${bloc.dataset.cle}/revelation`, {
  method: 'POST',
  headers: { 'X-CSRF-Token': csrf, Accept: 'application/json' },
  credentials: 'same-origin',
});
```

- La route est la même que celle utilisée par le launcher (`POST /api/v1/moi/cles/{id}/revelation`).
- L'authentification se fait avec le cookie de session du portail, et l'en-tête `X-CSRF-Token` est obligatoire, sinon l'API répond 403. Le jeton CSRF est lu dans la balise `<meta name="csrf-token">` de la page.
- La réponse JSON contient le code de la clé, qui remplace le code masqué dans la page.

## États et erreurs

Le code est dans la fonction `revelerCle()` de [`app.js`](../../../portail/public/assets/js/app.js).

| Situation | Ce que voit le joueur |
|---|---|
| pendant l'appel | le bouton passe à « Affichage… » et se désactive, le bloc est grisé (`aria-busy`) |
| succès | la clé s'affiche et le bouton devient « Copier » |
| 401 (session expirée) | « Ta session a expiré. Recharge la page et reconnecte-toi. » |
| 403 (jeton CSRF refusé) | « Session expirée ou action refusée. Recharge la page puis réessaie. » |
| 404 (clé introuvable ou à quelqu'un d'autre) | « Cette clé est introuvable dans ta bibliothèque. » |
| autre erreur de l'API | le message renvoyé par l'API, ou « Erreur inattendue du serveur (code …) » |
| réponse qui n'est pas du JSON (page d'erreur HTML) | traitée comme une erreur, sans faire planter le script |
| réseau coupé | « Connexion impossible. Vérifie ta connexion internet puis réessaie. » |

Après une erreur, le bouton redevient actif pour pouvoir réessayer. Le message est dans une zone `role="alert"`, donc il est aussi lu par les lecteurs d'écran.

Sans JavaScript, le bouton envoie un formulaire classique vers `/bibliotheque/cles/{id}/reveler`, qui fait la même chose côté serveur.

## Tests

Dans le navigateur, connecté avec le compte de test LeaPixel :

1. j'ai changé l'identifiant de la clé dans la page pour viser la clé d'un autre joueur, puis cliqué sur « Afficher la clé » : le message « Cette clé est introuvable dans ta bibliothèque. » s'est affiché et le bouton est redevenu actif ;
2. j'ai remis le bon identifiant et recliqué : la clé `3V835-NUTAC-K79ZN` s'est affichée, le message d'erreur a disparu et le bouton est devenu « Copier » ;
3. j'ai vérifié en base que `date_revelation` était bien remplie pour cette clé et qu'elle correspondait à ce qui était affiché.

La sécurité de ces échanges est aussi vérifiée automatiquement : un appel par cookie sans l'en-tête CSRF est refusé (test SEC-03), et un joueur ne peut pas afficher la clé d'un autre (API-17).
