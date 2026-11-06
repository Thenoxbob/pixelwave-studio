<?php
declare(strict_types=1);

namespace Pixelwave\Controller;

use Pixelwave\Core\Auth;
use Pixelwave\Core\Config;
use Pixelwave\Core\Csrf;
use Pixelwave\Core\HttpException;
use Pixelwave\Core\Request;
use Pixelwave\Core\Session;
use Pixelwave\Core\View;
use Pixelwave\Repository\ActualiteRepository;
use Pixelwave\Repository\BibliothequeRepository;
use Pixelwave\Repository\CommandeRepository;
use Pixelwave\Repository\JetonRepository;
use Pixelwave\Repository\PanierRepository;
use Pixelwave\Repository\ProduitRepository;
use Pixelwave\Repository\TicketRepository;
use Pixelwave\Repository\UtilisateurRepository;
use Pixelwave\Service\BoutiqueService;
use Pixelwave\Service\CompteService;
use Pixelwave\Service\TicketService;

/** Pages HTML du portail joueurs. */
final class SiteController
{
    private ProduitRepository $produits;

    public function __construct()
    {
        $this->produits = new ProduitRepository();
    }

    // ------------------------------------------------------------------
    // Pages publiques
    // ------------------------------------------------------------------

    public function accueil(Request $r): string
    {
        return View::rendre('accueil', [
            'titre'      => 'Accueil',
            'page'       => 'accueil',
            'une'        => $this->produits->trouverParSlug('neon-drift-circuit-nocturne') ?? $this->produits->aLaUne(),
            'promos'     => $this->accueilPromos(),
            'actualites' => (new ActualiteRepository())->lister(3),
        ]);
    }

    /** Promotions en cours, complétées par les nouveautés pour remplir 4 cartes. */
    private function accueilPromos(): array
    {
        $cartes = $this->produits->enPromotion(4);
        foreach ($this->produits->lister(['tri' => 'nouveautes']) as $p) {
            if (count($cartes) >= 4) {
                break;
            }
            if (!in_array($p['id_produit'], array_column($cartes, 'id_produit'), true) && $p['date_sortie'] <= date('Y-m-d')) {
                $cartes[] = $p;
            }
        }
        return $cartes;
    }

    public function boutique(Request $r): string
    {
        $filtres = [
            'type'       => in_array($r->param('type'), ['jeu', 'dlc', 'promo'], true) ? $r->param('type') : '',
            'plateforme' => (string) $r->param('plateforme', ''),
            'tri'        => in_array($r->param('tri'), ProduitRepository::TRIS, true) ? $r->param('tri') : 'nouveautes',
            'q'          => substr((string) $r->param('q', ''), 0, 100),
        ];
        return View::rendre('boutique', [
            'titre'       => 'Boutique',
            'page'        => 'boutique',
            'filtres'     => $filtres,
            'produits'    => $this->produits->lister($filtres),
            'plateformes' => $this->produits->plateformes(),
        ]);
    }

    public function produit(Request $r, array $p): string
    {
        $produit = $this->produits->trouverParSlug($p['slug']) ?? throw new HttpException(404, 'introuvable', 'Ce jeu est introuvable.');
        $u = Auth::utilisateur();
        $offres = $this->produits->offres((int) $produit['id_produit']);
        if ($u) {
            $biblio = new BibliothequeRepository();
            foreach ($offres as &$o) {
                $o['possede'] = $biblio->possede((int) $u['id_utilisateur'], (int) $produit['id_produit'], (int) $o['id_plateforme']);
            }
            unset($o);
        }
        return View::rendre('produit', [
            'titre'   => $produit['titre'],
            'page'    => 'boutique',
            'produit' => $produit,
            'offres'  => $offres,
            'dlcs'    => $produit['type'] === 'jeu' ? $this->produits->dlcs((int) $produit['id_produit']) : [],
        ]);
    }

    public function actualites(Request $r): string
    {
        return View::rendre('actualites', [
            'titre'      => 'Actualités',
            'page'       => 'actualites',
            'actualites' => (new ActualiteRepository())->lister(),
        ]);
    }

    public function actualite(Request $r, array $p): string
    {
        $a = (new ActualiteRepository())->trouverParSlug($p['slug']) ?? throw new HttpException(404, 'introuvable', 'Actualité introuvable.');
        return View::rendre('actualite', ['titre' => $a['titre'], 'page' => 'actualites', 'actualite' => $a]);
    }

    public function aide(Request $r): string
    {
        return View::rendre('aide', ['titre' => 'Aide', 'page' => 'support']);
    }

    public function confidentialite(Request $r): string
    {
        return View::rendre('confidentialite', ['titre' => 'Confidentialité', 'page' => '']);
    }

    // ------------------------------------------------------------------
    // Compte : connexion, inscription, profil
    // ------------------------------------------------------------------

    public function connexion(Request $r): string
    {
        if (Auth::utilisateur()) {
            return View::rediriger('/compte');
        }
        $erreur = null;
        $email = '';
        $retour = $this->retourValide((string) ($r->param('retour') ?? $r->champ('retour', '/compte')));
        if ($r->methode === 'POST') {
            Csrf::verifier($r->champ('_csrf'));
            $email = (string) $r->champ('email', '');
            try {
                $u = (new CompteService())->authentifier($email, (string) ($r->post['mot_de_passe'] ?? ''), $r->ip);
                Auth::connecter($u);
                Session::flash('succes', 'Bon retour, ' . $u['pseudo'] . ' !');
                return View::rediriger($retour);
            } catch (HttpException $e) {
                $erreur = $e->getMessage();
            }
        }
        return View::rendre('connexion', ['titre' => 'Connexion', 'page' => 'compte', 'erreur' => $erreur, 'email' => $email, 'retour' => $retour], $erreur ? 401 : 200);
    }

    public function inscription(Request $r): string
    {
        if (Auth::utilisateur()) {
            return View::rediriger('/compte');
        }
        $erreurs = [];
        if ($r->methode === 'POST') {
            Csrf::verifier($r->champ('_csrf'));
            try {
                $id = (new CompteService())->inscrire($r->post);
                Auth::connecter((new UtilisateurRepository())->trouverParId($id));
                Session::flash('succes', 'Bienvenue sur le portail PixelWave !');
                return View::rediriger('/compte');
            } catch (HttpException $e) {
                $erreurs = $e->details ?: ['general' => $e->getMessage()];
            }
        }
        return View::rendre('inscription', ['titre' => 'Créer un compte', 'page' => 'compte', 'erreurs' => $erreurs, 'saisie' => $r->post], $erreurs ? 400 : 200);
    }

    public function deconnexion(Request $r): string
    {
        Csrf::verifier($r->champ('_csrf'));
        Auth::deconnecter();
        Session::demarrer();
        Session::flash('info', 'Tu es déconnecté. À bientôt !');
        return View::rediriger('/');
    }

    public function compte(Request $r): string
    {
        $u = Auth::exiger();
        $nouveauJeton = $this->lireUneFois('nouveau_jeton');
        $erreurs = $this->lireUneFois('erreurs_mdp') ?? [];
        return View::rendre('compte', [
            'titre'     => 'Mon compte',
            'page'      => 'compte',
            'u'         => $u,
            'commandes' => (new CommandeRepository())->lister((int) $u['id_utilisateur']),
            'jetons'    => (new JetonRepository())->lister((int) $u['id_utilisateur']),
            'nouveauJeton' => $nouveauJeton,
            'erreurs'   => $erreurs,
        ]);
    }

    /** Valeur de session affichée une seule fois (jeton créé, erreurs de formulaire). */
    private function lireUneFois(string $cle): mixed
    {
        $valeur = $_SESSION[$cle] ?? null;
        unset($_SESSION[$cle]);
        return $valeur;
    }

    public function changerMotDePasse(Request $r): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        try {
            (new CompteService())->changerMotDePasse($u, $r->post);
            Session::flash('succes', 'Mot de passe modifié.');
        } catch (HttpException $e) {
            $_SESSION['erreurs_mdp'] = $e->details;
            Session::flash('erreur', 'Le mot de passe n\'a pas été modifié.');
        }
        return View::rediriger('/compte#securite');
    }

    public function preferences(Request $r): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        (new UtilisateurRepository())->majNewsletter((int) $u['id_utilisateur'], $r->champ('newsletter') === '1');
        Session::flash('succes', 'Préférences enregistrées.');
        return View::rediriger('/compte');
    }

    public function creerJeton(Request $r): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        $libelle = substr((string) $r->champ('libelle', ''), 0, 50) ?: 'Jeton personnel';
        [$jeton] = (new JetonRepository())->creer((int) $u['id_utilisateur'], $libelle, (int) Config::get('jeton_duree_jours', 90));
        $_SESSION['nouveau_jeton'] = $jeton;   // affiché une seule fois
        return View::rediriger('/compte#api');
    }

    public function revoquerJeton(Request $r, array $p): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        (new JetonRepository())->revoquer((int) $p['id'], (int) $u['id_utilisateur']);
        Session::flash('info', 'Jeton révoqué.');
        return View::rediriger('/compte#api');
    }

    // ------------------------------------------------------------------
    // Panier et bibliothèque
    // ------------------------------------------------------------------

    public function panier(Request $r): string
    {
        $u = Auth::exiger();
        $lignes = (new PanierRepository())->lignes((int) $u['id_utilisateur']);
        return View::rendre('panier', [
            'titre'  => 'Panier',
            'page'   => 'panier',
            'lignes' => $lignes,
            'total'  => array_sum(array_column($lignes, 'prix_final')),
        ]);
    }

    public function ajouterPanier(Request $r): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        $slug = (string) $r->champ('slug', '');
        try {
            (new BoutiqueService())->ajouterAuPanier((int) $u['id_utilisateur'], (int) $r->champ('id_produit'), (string) $r->champ('plateforme'));
            Session::flash('succes', 'Ajouté au panier.');
            return View::rediriger('/panier');
        } catch (HttpException $e) {
            Session::flash('erreur', $e->getMessage());
            return View::rediriger($slug !== '' ? '/boutique/' . rawurlencode($slug) : '/boutique');
        }
    }

    public function retirerPanier(Request $r): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        (new PanierRepository())->retirer((int) $u['id_utilisateur'], (int) $r->champ('id_ligne'));
        Session::flash('info', 'Article retiré du panier.');
        return View::rediriger('/panier');
    }

    public function validerPanier(Request $r): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        try {
            $reference = (new BoutiqueService())->validerCommande((int) $u['id_utilisateur']);
            Session::flash('succes', "Commande $reference validée ! Tes clés t'attendent dans ta bibliothèque.");
            return View::rediriger('/bibliotheque');
        } catch (HttpException $e) {
            Session::flash('erreur', $e->getMessage());
            return View::rediriger('/panier');
        }
    }

    public function bibliotheque(Request $r): string
    {
        $u = Auth::exiger();
        return View::rendre('bibliotheque', [
            'titre'   => 'Ma bibliothèque',
            'page'    => 'bibliotheque',
            'contenu' => (new BibliothequeRepository())->contenu((int) $u['id_utilisateur']),
        ]);
    }

    /** Révélation sans JavaScript (le JS passe par l'API). */
    public function revelerCle(Request $r, array $p): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        if ((new BibliothequeRepository())->reveler((int) $p['id'], (int) $u['id_utilisateur']) === null) {
            throw new HttpException(404, 'introuvable', 'Clé introuvable.');
        }
        return View::rediriger('/bibliotheque#cle-' . (int) $p['id']);
    }

    // ------------------------------------------------------------------
    // Support : signalement et suivi des bugs
    // ------------------------------------------------------------------

    public function support(Request $r): string
    {
        $u = Auth::exiger();
        $repo = new TicketRepository();
        $staff = Auth::estStaff($u);
        $statut = (string) $r->param('statut', '');
        return View::rendre('support', [
            'titre'   => $staff ? 'File des tickets' : 'Mes signalements',
            'page'    => 'support',
            'staff'   => $staff,
            'statut'  => $statut,
            'statuts' => $repo->statuts(),
            'tickets' => $staff ? $repo->listerTous(['statut' => $statut, 'ouverts' => $statut === '']) : $repo->listerPourJoueur((int) $u['id_utilisateur']),
        ]);
    }

    public function nouveauTicket(Request $r): string
    {
        $u = Auth::exiger();
        $erreurs = [];
        if ($r->methode === 'POST') {
            Csrf::verifier($r->champ('_csrf'));
            try {
                $reference = (new TicketService())->signaler($u, $r->post);
                Session::flash('succes', "Merci ! Ton signalement $reference a bien été envoyé à l'équipe QA.");
                return View::rediriger('/support/' . $reference);
            } catch (HttpException $e) {
                $erreurs = $e->details ?: ['general' => $e->getMessage()];
            }
        }
        return View::rendre('ticket_nouveau', [
            'titre'       => 'Signaler un bug',
            'page'        => 'support',
            'erreurs'     => $erreurs,
            'saisie'      => $r->post + ['id_produit' => $r->param('produit', '')],
            'produits'    => $this->produits->jeuxEtDlc(),
            'plateformes' => $this->produits->plateformes(),
        ], $erreurs ? 400 : 200);
    }

    public function ticket(Request $r, array $p): string
    {
        $u = Auth::exiger();
        $service = new TicketService();
        $ticket = $service->consulter($p['reference'], $u);
        $repo = new TicketRepository();
        $staff = Auth::estStaff($u);
        $erreurs = $this->lireUneFois('erreurs_ticket') ?? [];
        return View::rendre('ticket', [
            'titre'      => $ticket['reference'],
            'page'       => 'support',
            'ticket'     => $ticket,
            'staff'      => $staff,
            'messages'   => $repo->messages((int) $ticket['id_ticket'], $staff),
            'historique' => $repo->historique((int) $ticket['id_ticket']),
            'statuts'    => $repo->statuts(),
            'priorites'  => $repo->priorites(),
            'equipe'     => $staff ? (new UtilisateurRepository())->listerStaff() : [],
            'erreurs'    => $erreurs,
        ]);
    }

    public function repondreTicket(Request $r, array $p): string
    {
        $u = Auth::exiger();
        Csrf::verifier($r->champ('_csrf'));
        $service = new TicketService();
        $ticket = $service->consulter($p['reference'], $u);
        try {
            $service->repondre($ticket, $u, $r->post);
            Session::flash('succes', 'Message envoyé.');
        } catch (HttpException $e) {
            $_SESSION['erreurs_ticket'] = $e->details ?: ['contenu' => $e->getMessage()];
        }
        return View::rediriger('/support/' . $ticket['reference'] . '#messages');
    }

    public function traiterTicket(Request $r, array $p): string
    {
        $u = Auth::exiger(['support', 'admin']);
        Csrf::verifier($r->champ('_csrf'));
        $service = new TicketService();
        $ticket = $service->consulter($p['reference'], $u);
        $donnees = array_intersect_key($r->post, array_flip(['statut', 'priorite', 'id_technicien', 'commentaire']));
        try {
            $service->traiter($ticket, $u, $donnees);
            Session::flash('succes', 'Ticket mis à jour.');
        } catch (HttpException $e) {
            $_SESSION['erreurs_ticket'] = $e->details ?: ['statut' => $e->getMessage()];
        }
        return View::rediriger('/support/' . $ticket['reference']);
    }

    private function retourValide(string $url): string
    {
        return (str_starts_with($url, '/') && !str_starts_with($url, '//')) ? $url : '/compte';
    }
}
