<?php
declare(strict_types=1);

/**
 * Recette automatisée de l'API REST et de la sécurité HTTP (tests fonctionnels « boîte noire »).
 * Envoie de vraies requêtes HTTP au portail démarré et vérifie codes de statut et contenus.
 *
 *   php tests/api/recette_api.php [url] [--reinitialiser]
 *
 * --reinitialiser recrée la base avant les tests (nécessite db_admin dans la configuration
 * pointée par PIXELWAVE_CONFIG). Les résultats sont aussi écrits dans tests/api/resultats.md.
 */

$url = 'http://127.0.0.1:8080';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, 'http')) {
        $url = rtrim($arg, '/');
    }
}

if (in_array('--reinitialiser', $argv, true)) {
    require dirname(__DIR__) . '/bootstrap.php';
    Pixelwave\Tests\BaseDeTest::reinitialiser();
    echo "Base réinitialisée.\n";
}

const JETON_LEA = 'pw_demo_LeaPixel_2026_9f8e7d6c5b4a';
const JETON_EXPIRE = 'pw_demo_DriftKing_expire_0000';
const MDP_DEMO = 'Pixelwave!2026';

// ---------------------------------------------------------------------------
// Petit client HTTP (sans extension curl)
// ---------------------------------------------------------------------------
function http(string $methode, string $chemin, array $options = []): array
{
    global $url;
    $entetes = ['Accept: application/json'];
    $corps = null;
    if (array_key_exists('json', $options)) {
        $corps = is_string($options['json']) ? $options['json'] : json_encode($options['json'], JSON_UNESCAPED_UNICODE);
        $entetes[] = 'Content-Type: application/json';
    } elseif (isset($options['form'])) {
        $corps = http_build_query($options['form']);
        $entetes[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    if (isset($options['jeton'])) {
        $entetes[] = 'Authorization: Bearer ' . $options['jeton'];
    }
    if (isset($options['cookie'])) {
        $entetes[] = 'Cookie: ' . $options['cookie'];
    }
    foreach ($options['entetes'] ?? [] as $e) {
        $entetes[] = $e;
    }
    $contexte = stream_context_create(['http' => [
        'method' => $methode, 'header' => implode("\r\n", $entetes), 'content' => $corps ?? '',
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10,
    ]]);
    $reponse = @file_get_contents($url . $chemin, false, $contexte);
    $brut = function_exists('http_get_last_response_headers') ? (http_get_last_response_headers() ?? []) : ($http_response_header ?? []);
    if ($reponse === false && !$brut) {
        fwrite(STDERR, "Serveur injoignable : $url\n");
        exit(2);
    }
    preg_match('/\s(\d{3})/', $brut[0] ?? '', $m);
    $h = [];
    $cookies = [];
    foreach (array_slice($brut, 1) as $ligne) {
        [$nom, $valeur] = array_map('trim', explode(':', $ligne, 2)) + [1 => ''];
        $h[strtolower($nom)] = $valeur;
        if (strtolower($nom) === 'set-cookie') {
            $cookies[] = explode(';', $valeur)[0];
        }
    }
    return ['statut' => (int) ($m[1] ?? 0), 'entetes' => $h, 'cookies' => $cookies, 'corps' => (string) $reponse, 'json' => json_decode((string) $reponse, true)];
}

$resultats = [];
function cas(string $id, string $libelle, int $statutAttendu, array $rep, ?callable $verif = null): void
{
    global $resultats;
    $ok = $rep['statut'] === $statutAttendu;
    $detail = "HTTP {$rep['statut']}";
    if ($ok && $verif) {
        try {
            $retour = $verif($rep);
            if ($retour !== true) {
                $ok = false;
                $detail .= ' · ' . (is_string($retour) ? $retour : 'contenu inattendu');
            }
        } catch (Throwable $e) {
            $ok = false;
            $detail .= ' · ' . $e->getMessage();
        }
    } elseif (!$ok) {
        $detail .= " (attendu $statutAttendu)" . (isset($rep['json']['erreur']['code']) ? ' · ' . $rep['json']['erreur']['code'] : '');
    }
    $resultats[] = [$id, $libelle, $statutAttendu, $ok, $detail];
    printf("  %-7s %-6s %s — %s\n", $id, $ok ? 'OK' : 'ÉCHEC', $libelle, $detail);
}

function erreur(array $rep, string $code): bool|string
{
    return ($rep['json']['erreur']['code'] ?? null) === $code ?: 'code d\'erreur ' . ($rep['json']['erreur']['code'] ?? 'absent');
}

echo "Recette API sur $url\n\n";

// --- Ressources publiques -------------------------------------------------
cas('API-01', 'Santé du service', 200, http('GET', '/api/v1/sante'), fn($r) => $r['json']['base_de_donnees'] === 'ok');
cas('API-02', 'Catalogue complet (produits en vente)', 200, http('GET', '/api/v1/produits'), fn($r) => $r['json']['total'] === 8 ?: 'total ' . $r['json']['total']);
cas('API-03', 'Filtre promotions', 200, http('GET', '/api/v1/produits?type=promo'), fn($r) => $r['json']['total'] === 2);
cas('API-04', 'Filtre invalide refusé', 400, http('GET', '/api/v1/produits?type=xyz'), fn($r) => erreur($r, 'donnees_invalides'));
cas('API-05', 'Détail produit avec prix remisé et offres', 200, http('GET', '/api/v1/produits/3'),
    fn($r) => $r['json']['prix']['final'] === 17.49 && $r['json']['offres'][0]['disponible'] === true);
cas('API-06', 'Produit inexistant', 404, http('GET', '/api/v1/produits/999'), fn($r) => erreur($r, 'produit_introuvable'));
cas('API-07', 'Produit retiré de la vente invisible', 404, http('GET', '/api/v1/produits/9'));
cas('API-08', 'Actualités publiées uniquement (brouillon exclu)', 200, http('GET', '/api/v1/actualites'), fn($r) => $r['json']['total'] === 4);

// --- Authentification ------------------------------------------------------
cas('API-09', 'Route protégée sans jeton', 401, http('GET', '/api/v1/moi'), fn($r) => erreur($r, 'non_authentifie'));
cas('API-10', 'Jeton inventé', 401, http('GET', '/api/v1/moi', ['jeton' => 'pw_' . str_repeat('0', 48)]), fn($r) => erreur($r, 'jeton_invalide'));
cas('API-11', 'Jeton expiré', 401, http('GET', '/api/v1/moi', ['jeton' => JETON_EXPIRE]), fn($r) => erreur($r, 'jeton_invalide'));
cas('API-12', 'Création de jeton, mauvais mot de passe', 401,
    http('POST', '/api/v1/auth/jetons', ['json' => ['email' => 'hugo.petit@exemple.test', 'mot_de_passe' => 'faux']]), fn($r) => erreur($r, 'identifiants_invalides'));
$rep = http('POST', '/api/v1/auth/jetons', ['json' => ['email' => 'hugo.petit@exemple.test', 'mot_de_passe' => MDP_DEMO, 'libelle' => 'Recette']]);
cas('API-13', 'Création de jeton valide', 201, $rep, fn($r) => preg_match('/^pw_[a-f0-9]{48}$/', $r['json']['jeton']) === 1 && !isset($r['json']['utilisateur']['mot_de_passe_hash']));
$jetonHugo = $rep['json']['jeton'] ?? '';
cas('API-14', 'Profil avec le nouveau jeton', 200, http('GET', '/api/v1/moi', ['jeton' => $jetonHugo]), fn($r) => $r['json']['pseudo'] === 'AbyssDiver');
cas('API-15', 'JSON mal formé', 400, http('POST', '/api/v1/auth/jetons', ['json' => '{"email": ']), fn($r) => erreur($r, 'json_invalide'));

// --- Bibliothèque et clés ----------------------------------------------------
cas('API-16', 'Bibliothèque : clé non révélée masquée', 200, http('GET', '/api/v1/moi/bibliotheque', ['jeton' => JETON_LEA]),
    function ($r) {
        $nocturne = array_values(array_filter($r['json']['donnees'], fn($l) => $l['id_cle'] === 5))[0];
        return $nocturne['revelee'] === false && str_starts_with($nocturne['cle'], '•');
    });
cas('API-17', "Révéler la clé d'un autre joueur", 404, http('POST', '/api/v1/moi/cles/3/revelation', ['jeton' => JETON_LEA]));
cas('API-18', 'Révéler sa propre clé', 200, http('POST', '/api/v1/moi/cles/5/revelation', ['jeton' => JETON_LEA]), fn($r) => $r['json']['cle'] === '3V835-NUTAC-K79ZN');

// --- Panier et commande ------------------------------------------------------
cas('API-19', 'Ajout panier : rupture de stock', 409, http('POST', '/api/v1/moi/panier', ['jeton' => JETON_LEA, 'json' => ['id_produit' => 4, 'plateforme' => 'SWITCH']]), fn($r) => erreur($r, 'stock_epuise'));
cas('API-20', 'Ajout panier : plateforme non proposée', 400, http('POST', '/api/v1/moi/panier', ['jeton' => JETON_LEA, 'json' => ['id_produit' => 3, 'plateforme' => 'PS5']]));
cas('API-21', 'Ajout panier : produit déjà possédé', 409, http('POST', '/api/v1/moi/panier', ['jeton' => JETON_LEA, 'json' => ['id_produit' => 1, 'plateforme' => 'PC']]), fn($r) => erreur($r, 'deja_possede'));
cas('API-22', 'Ajout panier : précommande Pocket Galaxy', 201, http('POST', '/api/v1/moi/panier', ['jeton' => JETON_LEA, 'json' => ['id_produit' => 5, 'plateforme' => 'PC']]));
cas('API-23', 'Validation de commande', 201, http('POST', '/api/v1/moi/commandes', ['jeton' => JETON_LEA]),
    fn($r) => count($r['json']['lignes']) === 2 && abs($r['json']['montant_total'] - 42.98) < 0.001 ?: 'montant ' . $r['json']['montant_total']);
cas('API-24', 'Nouvelle validation avec panier vide', 400, http('POST', '/api/v1/moi/commandes', ['jeton' => JETON_LEA]), fn($r) => erreur($r, 'panier_vide'));

// --- Tickets -------------------------------------------------------------------
cas('API-25', 'Signalement incomplet', 400, http('POST', '/api/v1/tickets', ['jeton' => JETON_LEA, 'json' => ['titre' => 'Bug']]),
    fn($r) => isset($r['json']['erreur']['details']['titre'], $r['json']['erreur']['details']['description']));
$rep = http('POST', '/api/v1/tickets', ['jeton' => JETON_LEA, 'json' => [
    'id_produit' => 6, 'plateforme' => 'PC', 'version_jeu' => '1.4.0', 'frequence' => 'toujours',
    'titre' => '<script>alert("xss")</script> Écran noir', 'description' => 'Écran noir au départ de la course sur le circuit Quai 9 en mode nuit.',
]]);
cas('API-26', 'Signalement valide', 201, $rep, fn($r) => $r['json']['statut']['code'] === 'nouveau' && $r['json']['priorite'] === null);
$refLea = $rep['json']['reference'] ?? 'BUG-0000-0000';
cas('API-27', "Lire le ticket d'un autre joueur", 404, http('GET', '/api/v1/tickets/BUG-2026-0001', ['jeton' => JETON_LEA]));
cas('API-28', 'Un joueur ne peut pas qualifier un ticket', 403, http('PATCH', "/api/v1/tickets/$refLea", ['jeton' => JETON_LEA, 'json' => ['priorite' => 'critique']]), fn($r) => erreur($r, 'acces_refuse'));
$jetonKarim = http('POST', '/api/v1/auth/jetons', ['json' => ['email' => 'karim@pixelwave.test', 'mot_de_passe' => MDP_DEMO]])['json']['jeton'] ?? '';
cas('API-29', 'Le support qualifie et affecte le ticket', 200, http('PATCH', "/api/v1/tickets/$refLea", ['jeton' => $jetonKarim, 'json' => ['statut' => 'en_cours', 'priorite' => 'haute', 'id_technicien' => 2]]),
    fn($r) => $r['json']['statut']['code'] === 'en_cours' && $r['json']['technicien'] === 'karim.support');
cas('API-30', 'Le support voit la file complète', 200, http('GET', '/api/v1/tickets', ['jeton' => $jetonKarim]), fn($r) => $r['json']['total'] === 7);
cas('API-31', 'Le joueur répond sur son ticket', 201, http('POST', "/api/v1/tickets/$refLea/messages", ['jeton' => JETON_LEA, 'json' => ['contenu' => 'Merci, je reste dispo.']]));
cas('API-32', 'Méthode non autorisée', 405, http('DELETE', '/api/v1/produits'), fn($r) => isset($r['entetes']['allow']));

// --- Sécurité web (session + CSRF + XSS + en-têtes) ---------------------------------
$page = http('GET', '/connexion');
$cookie = $page['cookies'][0] ?? '';
preg_match('/name="_csrf" value="([a-f0-9]+)"/', $page['corps'], $m);
$connexion = http('POST', '/connexion', ['cookie' => $cookie, 'form' => ['_csrf' => $m[1] ?? '', 'email' => 'lea.martin@exemple.test', 'mot_de_passe' => MDP_DEMO, 'retour' => '/compte']]);
$cookie = $connexion['cookies'][0] ?? $cookie;      // nouvel identifiant après connexion
cas('SEC-01', 'Connexion par formulaire (redirection)', 303, $connexion, fn($r) => ($r['entetes']['location'] ?? '') === '/compte');
cas('SEC-02', 'Formulaire sans jeton CSRF refusé', 403, http('POST', '/panier/valider', ['cookie' => $cookie, 'form' => []]));
cas('SEC-03', 'Appel API par cookie sans en-tête CSRF refusé', 403, http('POST', '/api/v1/moi/cles/1/revelation', ['cookie' => $cookie]), fn($r) => erreur($r, 'csrf_invalide'));
cas('SEC-04', 'Contenu saisi échappé à l\'affichage (XSS)', 200, http('GET', "/support/$refLea", ['cookie' => $cookie]),
    fn($r) => str_contains($r['corps'], '&lt;script&gt;alert') && !str_contains($r['corps'], '<script>alert'));
cas('SEC-05', 'En-têtes de sécurité présents', 200, http('GET', '/'),
    fn($r) => isset($r['entetes']['content-security-policy'], $r['entetes']['x-frame-options'], $r['entetes']['x-content-type-options']));
cas('SEC-06', 'Cookie de session HttpOnly et SameSite', 200, $page,
    function ($r) {
        $c = strtolower($r['entetes']['set-cookie'] ?? '');
        return (str_contains($c, 'httponly') && str_contains($c, 'samesite=lax')) ?: "attributs absents : $c";
    });
cas('SEC-07', 'Page protégée sans connexion : redirection', 303, http('GET', '/bibliotheque'), fn($r) => str_starts_with($r['entetes']['location'] ?? '', '/connexion'));
cas('SEC-08', 'Injection SQL dans la recherche sans effet', 200, http('GET', "/api/v1/produits?q=" . rawurlencode("' OR 1=1 -- ")), fn($r) => $r['json']['total'] === 0);
for ($i = 0; $i < 5; $i++) {
    http('POST', '/api/v1/auth/jetons', ['json' => ['email' => 'julie@pixelwave.test', 'mot_de_passe' => "essai$i"]]);
}
cas('SEC-09', 'Blocage après 5 échecs de connexion', 429, http('POST', '/api/v1/auth/jetons', ['json' => ['email' => 'julie@pixelwave.test', 'mot_de_passe' => MDP_DEMO]]), fn($r) => erreur($r, 'trop_de_tentatives'));
cas('SEC-10', 'Révocation du jeton courant', 204, http('DELETE', '/api/v1/auth/jetons/courant', ['jeton' => $jetonHugo]));
cas('SEC-11', 'Jeton révoqué inutilisable', 401, http('GET', '/api/v1/moi', ['jeton' => $jetonHugo]));

// --- Bilan -------------------------------------------------------------------------
$ok = count(array_filter($resultats, fn($r) => $r[3]));
$total = count($resultats);
printf("\n%d/%d cas conformes\n", $ok, $total);

$md = "# Résultats de la recette automatisée de l'API\n\n"
    . 'Exécution du ' . date('d/m/Y à H:i') . " sur `$url` — **$ok/$total cas conformes**.\n\n"
    . "| Cas | Description | Statut attendu | Résultat | Détail |\n|---|---|---|---|---|\n";
foreach ($resultats as [$id, $lib, $attendu, $r, $detail]) {
    $md .= "| $id | " . str_replace('|', '\|', $lib) . " | $attendu | " . ($r ? '✅ Conforme' : '❌ Non conforme') . ' | ' . str_replace('|', '\|', $detail) . " |\n";
}
file_put_contents(__DIR__ . '/resultats.md', $md);
exit($ok === $total ? 0 : 1);
