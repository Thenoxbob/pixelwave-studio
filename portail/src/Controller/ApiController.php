<?php
declare(strict_types=1);

namespace Pixelwave\Controller;

use Pixelwave\Core\Auth;
use Pixelwave\Core\Config;
use Pixelwave\Core\Csrf;
use Pixelwave\Core\Database;
use Pixelwave\Core\HttpException;
use Pixelwave\Core\Request;
use Pixelwave\Core\Validator;
use Pixelwave\Core\View;
use Pixelwave\Repository\ActualiteRepository;
use Pixelwave\Repository\BibliothequeRepository;
use Pixelwave\Repository\CommandeRepository;
use Pixelwave\Repository\JetonRepository;
use Pixelwave\Repository\PanierRepository;
use Pixelwave\Repository\ProduitRepository;
use Pixelwave\Repository\TicketRepository;
use Pixelwave\Service\BoutiqueService;
use Pixelwave\Service\CompteService;
use Pixelwave\Service\TicketService;

/**
 * API REST v1 du portail (JSON). Documentation : docs/monde-2/1-conception-backend/L8_documentation_api.md
 *
 * Authentification :
 *  - « Authorization: Bearer <jeton> » (launcher, bot Discord, Postman...)
 *  - ou cookie de session du portail + en-tête X-CSRF-Token pour les requêtes qui modifient
 */
final class ApiController
{
    private ProduitRepository $produits;

    public function __construct()
    {
        $this->produits = new ProduitRepository();
    }

    // ------------------------------------------------------------------
    // Authentification
    // ------------------------------------------------------------------

    private function authentifier(Request $r, array $roles = []): array
    {
        $entete = (string) $r->entete('Authorization');
        if ($entete !== '') {
            if (!preg_match('/^Bearer\s+(pw_[a-f0-9]{48}|pw_demo_[A-Za-z0-9_]+)$/', $entete, $m)) {
                throw new HttpException(401, 'jeton_invalide', 'En-tête Authorization mal formé. Attendu : Bearer <jeton>.');
            }
            $u = (new JetonRepository())->utilisateurPourJeton($m[1]);
            if ($u === null) {
                throw new HttpException(401, 'jeton_invalide', 'Jeton invalide, expiré ou révoqué.');
            }
            Auth::definir($u);
        } elseif (Auth::utilisateur() !== null && $r->methode !== 'GET') {
            // appel depuis le portail avec le cookie de session : protection CSRF
            Csrf::verifier($r->entete('X-CSRF-Token'));
        }
        return Auth::exiger($roles);
    }

    /** POST /api/v1/auth/jetons : échange e-mail + mot de passe contre un jeton. */
    public function creerJeton(Request $r): string
    {
        $d = $r->json();
        $v = new Validator($d);
        $v->requis('email')->email('email')->requis('mot_de_passe')->longueur('libelle', 1, 50);
        $v->verifier();
        $u = (new CompteService())->authentifier((string) $d['email'], (string) $d['mot_de_passe'], $r->ip);
        [$jeton, $expiration] = (new JetonRepository())->creer((int) $u['id_utilisateur'], (string) ($d['libelle'] ?? 'API'), (int) Config::get('jeton_duree_jours', 90));
        return View::json([
            'jeton'      => $jeton,
            'type'       => 'Bearer',
            'expiration' => $expiration,
            'utilisateur' => $this->formaterUtilisateur($u),
        ], 201);
    }

    /** DELETE /api/v1/auth/jetons/courant : révoque le jeton utilisé pour l'appel. */
    public function revoquerJeton(Request $r): string
    {
        $u = $this->authentifier($r);
        if (empty($u['id_jeton'])) {
            throw new HttpException(400, 'pas_de_jeton', 'Cet appel doit être authentifié par un jeton.');
        }
        (new JetonRepository())->revoquer((int) $u['id_jeton'], (int) $u['id_utilisateur']);
        return View::json(null, 204);
    }

    // ------------------------------------------------------------------
    // Ressources publiques
    // ------------------------------------------------------------------

    /** GET /api/v1/sante : état du service, utilisé par la supervision. */
    public function sante(Request $r): string
    {
        try {
            Database::pdo()->query('SELECT 1');
            return View::json(['statut' => 'ok', 'base_de_donnees' => 'ok', 'date' => date(DATE_ATOM)]);
        } catch (\Throwable $e) {
            error_log('[sante] ' . $e->getMessage());
            return View::json(['statut' => 'degrade', 'base_de_donnees' => 'injoignable', 'date' => date(DATE_ATOM)], 503);
        }
    }

    public function plateformes(Request $r): string
    {
        return View::json(['donnees' => $this->produits->plateformes()]);
    }

    /** GET /api/v1/produits?type=&plateforme=&q=&tri= */
    public function produits(Request $r): string
    {
        $v = new Validator($r->query);
        $v->dansListe('type', ['jeu', 'dlc', 'promo'])
          ->dansListe('tri', ProduitRepository::TRIS)
          ->dansListe('plateforme', array_column($this->produits->plateformes(), 'code'))
          ->longueur('q', 1, 100);
        $v->verifier();
        $liste = array_map([$this, 'formaterProduit'], $this->produits->lister($r->query));
        return View::json(['donnees' => $liste, 'total' => count($liste)]);
    }

    public function produit(Request $r, array $p): string
    {
        $produit = ctype_digit($p['id']) ? $this->produits->trouverParId((int) $p['id']) : null;
        if ($produit === null) {
            throw new HttpException(404, 'produit_introuvable', 'Produit introuvable.');
        }
        $detail = $this->formaterProduit($produit) + ['description' => $produit['description']];
        $detail['offres'] = array_map(fn($o) => [
            'plateforme' => $o['code'],
            'nom'        => $o['nom'],
            'disponible' => (int) $o['stock'] > 0,
        ], $this->produits->offres((int) $produit['id_produit']));
        $detail['dlc'] = array_map([$this, 'formaterProduit'], $produit['type'] === 'jeu' ? $this->produits->dlcs((int) $produit['id_produit']) : []);
        return View::json($detail);
    }

    public function actualites(Request $r): string
    {
        $limite = filter_var($r->param('limite', 20), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
        if ($limite === false) {
            throw new HttpException(400, 'donnees_invalides', 'Le paramètre limite doit être compris entre 1 et 50.', ['limite' => 'Entre 1 et 50.']);
        }
        $liste = array_map([$this, 'formaterActualite'], (new ActualiteRepository())->lister($limite));
        return View::json(['donnees' => $liste, 'total' => count($liste)]);
    }

    public function actualite(Request $r, array $p): string
    {
        $a = ctype_digit($p['id']) ? (new ActualiteRepository())->trouverParId((int) $p['id']) : null;
        if ($a === null) {
            throw new HttpException(404, 'actualite_introuvable', 'Actualité introuvable.');
        }
        return View::json($this->formaterActualite($a) + ['contenu' => $a['contenu']]);
    }

    // ------------------------------------------------------------------
    // Espace joueur
    // ------------------------------------------------------------------

    public function moi(Request $r): string
    {
        return View::json($this->formaterUtilisateur($this->authentifier($r)));
    }

    public function bibliotheque(Request $r): string
    {
        $u = $this->authentifier($r);
        $liste = array_map(fn($l) => [
            'id_cle'      => (int) $l['id_cle'],
            'produit'     => ['id' => (int) $l['id_produit'], 'titre' => $l['titre'], 'type' => $l['type']],
            'plateforme'  => $l['plateforme_code'],
            'commande'    => $l['commande'],
            'date_achat'  => $l['date_commande'],
            'revelee'     => $l['date_revelation'] !== null,
            'cle'         => $l['code'] ?? $l['code_masque'],
        ], (new BibliothequeRepository())->contenu((int) $u['id_utilisateur']));
        return View::json(['donnees' => $liste, 'total' => count($liste)]);
    }

    /** POST /api/v1/moi/cles/{id}/revelation : affiche le code complet (action tracée). */
    public function revelerCle(Request $r, array $p): string
    {
        $u = $this->authentifier($r);
        $cle = ctype_digit($p['id']) ? (new BibliothequeRepository())->reveler((int) $p['id'], (int) $u['id_utilisateur']) : null;
        if ($cle === null) {
            throw new HttpException(404, 'cle_introuvable', 'Clé introuvable.');
        }
        return View::json([
            'id_cle'          => (int) $cle['id_cle'],
            'produit'         => $cle['titre'],
            'plateforme'      => $cle['plateforme'],
            'cle'             => $cle['code'],
            'date_revelation' => $cle['date_revelation'],
        ]);
    }

    public function panier(Request $r): string
    {
        $u = $this->authentifier($r);
        $lignes = (new PanierRepository())->lignes((int) $u['id_utilisateur']);
        return View::json([
            'lignes' => array_map(fn($l) => [
                'id_ligne'   => (int) $l['id_panier_ligne'],
                'produit'    => ['id' => (int) $l['id_produit'], 'titre' => $l['titre'], 'type' => $l['type']],
                'plateforme' => $l['plateforme_code'],
                'prix'       => (float) $l['prix_final'],
            ], $lignes),
            'total' => round(array_sum(array_column($lignes, 'prix_final')), 2),
        ]);
    }

    public function ajouterPanier(Request $r): string
    {
        $u = $this->authentifier($r);
        $d = $r->json();
        $v = new Validator($d);
        $v->requis('id_produit')->entierPositif('id_produit')->requis('plateforme');
        $v->verifier();
        $id = (new BoutiqueService())->ajouterAuPanier((int) $u['id_utilisateur'], (int) $d['id_produit'], (string) $d['plateforme']);
        return View::json(['id_ligne' => $id], 201);
    }

    public function retirerPanier(Request $r, array $p): string
    {
        $u = $this->authentifier($r);
        if (!ctype_digit($p['id']) || !(new PanierRepository())->retirer((int) $u['id_utilisateur'], (int) $p['id'])) {
            throw new HttpException(404, 'ligne_introuvable', 'Cet article n\'est pas dans ton panier.');
        }
        return View::json(null, 204);
    }

    public function commandes(Request $r): string
    {
        $u = $this->authentifier($r);
        $liste = array_map(fn($c) => [
            'reference'     => $c['reference'],
            'date'          => $c['date_commande'],
            'statut'        => $c['statut'],
            'nb_articles'   => (int) $c['nb_articles'],
            'montant_total' => (float) $c['montant_total'],
        ], (new CommandeRepository())->lister((int) $u['id_utilisateur']));
        return View::json(['donnees' => $liste, 'total' => count($liste)]);
    }

    /** POST /api/v1/moi/commandes : valide le panier (paiement simulé) et attribue les clés. */
    public function creerCommande(Request $r): string
    {
        $u = $this->authentifier($r);
        $reference = (new BoutiqueService())->validerCommande((int) $u['id_utilisateur']);
        $c = (new CommandeRepository())->trouver($reference, (int) $u['id_utilisateur']);
        return View::json([
            'reference'     => $c['reference'],
            'date'          => $c['date_commande'],
            'statut'        => $c['statut'],
            'montant_total' => (float) $c['montant_total'],
            'lignes'        => array_map(fn($l) => [
                'produit'    => $l['titre'],
                'plateforme' => $l['plateforme'],
                'prix'       => (float) $l['prix_unitaire'],
                'id_cle'     => (int) $l['id_cle'],
            ], $c['lignes']),
        ], 201);
    }

    // ------------------------------------------------------------------
    // Tickets
    // ------------------------------------------------------------------

    /** GET /api/v1/tickets : ses tickets pour un joueur, toute la file pour le support (?statut=). */
    public function tickets(Request $r): string
    {
        $u = $this->authentifier($r);
        $repo = new TicketRepository();
        if (Auth::estStaff($u)) {
            $v = new Validator($r->query);
            $v->dansListe('statut', array_column($repo->statuts(), 'code'), 'Statut inconnu.');
            $v->verifier();
            $liste = $repo->listerTous(['statut' => $r->param('statut', '')]);
        } else {
            $liste = $repo->listerPourJoueur((int) $u['id_utilisateur']);
        }
        return View::json(['donnees' => array_map([$this, 'formaterTicket'], $liste), 'total' => count($liste)]);
    }

    public function creerTicket(Request $r): string
    {
        $u = $this->authentifier($r);
        $reference = (new TicketService())->signaler($u, $r->json());
        return View::json($this->formaterTicket((new TicketRepository())->trouverParReference($reference)), 201);
    }

    public function ticket(Request $r, array $p): string
    {
        $u = $this->authentifier($r);
        $t = (new TicketService())->consulter($p['reference'], $u);
        $repo = new TicketRepository();
        $staff = Auth::estStaff($u);
        return View::json($this->formaterTicket($t) + [
            'messages' => array_map(fn($m) => [
                'id'      => (int) $m['id_message'],
                'auteur'  => $m['auteur'],
                'support' => $m['role'] !== 'joueur',
                'contenu' => $m['contenu'],
                'date'    => $m['date_envoi'],
            ] + ($staff ? ['interne' => (bool) $m['interne']] : []), $repo->messages((int) $t['id_ticket'], $staff)),
            'historique' => $repo->historique((int) $t['id_ticket']),
        ]);
    }

    public function ajouterMessage(Request $r, array $p): string
    {
        $u = $this->authentifier($r);
        $service = new TicketService();
        $t = $service->consulter($p['reference'], $u);
        $id = $service->repondre($t, $u, $r->json());
        return View::json(['id' => $id], 201);
    }

    /** PATCH /api/v1/tickets/{reference} : qualification par le support. */
    public function traiterTicket(Request $r, array $p): string
    {
        $u = $this->authentifier($r, ['support', 'admin']);
        $service = new TicketService();
        $t = $service->consulter($p['reference'], $u);
        $donnees = array_intersect_key($r->json(), array_flip(['statut', 'priorite', 'id_technicien', 'commentaire']));
        $service->traiter($t, $u, $donnees);
        return View::json($this->formaterTicket((new TicketRepository())->trouverParReference($t['reference'])));
    }

    // ------------------------------------------------------------------
    // Mise en forme des réponses (aucun champ sensible ne sort de l'API)
    // ------------------------------------------------------------------

    private function formaterUtilisateur(array $u): array
    {
        return [
            'id'               => (int) $u['id_utilisateur'],
            'pseudo'           => $u['pseudo'],
            'email'            => $u['email'],
            'role'             => $u['role'],
            'newsletter'       => (bool) $u['newsletter'],
            'date_inscription' => $u['date_inscription'],
        ];
    }

    private function formaterProduit(array $p): array
    {
        return [
            'id'          => (int) $p['id_produit'],
            'type'        => $p['type'],
            'titre'       => $p['titre'],
            'slug'        => $p['slug'],
            'accroche'    => $p['accroche'],
            'genre'       => $p['genre'],
            'jeu_parent'  => $p['jeu_parent'],
            'date_sortie' => $p['date_sortie'],
            'prix'        => [
                'base'       => (float) $p['prix_base'],
                'remise_pct' => (int) $p['remise_pct'],
                'final'      => (float) $p['prix_final'],
            ],
            'plateformes' => $p['plateformes'] ? explode(',', $p['plateformes']) : [],
        ];
    }

    private function formaterActualite(array $a): array
    {
        return [
            'id'               => (int) $a['id_actualite'],
            'titre'            => $a['titre'],
            'categorie'        => $a['categorie'],
            'chapo'            => $a['chapo'],
            'jeu'              => $a['produit'],
            'date_publication' => $a['date_publication'],
        ];
    }

    private function formaterTicket(array $t): array
    {
        return [
            'reference'     => $t['reference'],
            'titre'         => $t['titre'],
            'description'   => $t['description'],
            'statut'        => ['code' => $t['statut'], 'libelle' => $t['statut_libelle']],
            'priorite'      => $t['priorite'] ? ['code' => $t['priorite'], 'libelle' => $t['priorite_libelle']] : null,
            'jeu'           => $t['produit'],
            'plateforme'    => $t['plateforme'],
            'version_jeu'   => $t['version_jeu'],
            'frequence'     => $t['frequence'],
            'auteur'        => $t['auteur'],
            'technicien'    => $t['technicien'],
            'date_creation' => $t['date_creation'],
            'date_maj'      => $t['date_maj'],
        ];
    }
}
