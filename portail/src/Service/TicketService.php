<?php
declare(strict_types=1);

namespace Pixelwave\Service;

use Pixelwave\Core\Auth;
use Pixelwave\Core\HttpException;
use Pixelwave\Core\Validator;
use Pixelwave\Repository\ProduitRepository;
use Pixelwave\Repository\TicketRepository;
use Pixelwave\Repository\UtilisateurRepository;

/** Signalement de bugs et traitement des tickets par le support. */
final class TicketService
{
    public function __construct(
        private readonly TicketRepository $tickets = new TicketRepository(),
        private readonly ProduitRepository $produits = new ProduitRepository(),
        private readonly ?DiscordNotifier $discord = null
    ) {
    }

    /** Crée un signalement et prévient l'équipe QA sur Discord. Retourne la référence. */
    public function signaler(array $auteur, array $donnees): string
    {
        $v = new Validator($donnees);
        $v->requis('titre')->longueur('titre', 5, 150)
          ->requis('description')->longueur('description', 20, 5000)
          ->entierPositif('id_produit')
          ->longueur('version_jeu', 1, 20)
          ->motif('version_jeu', '/^[0-9A-Za-z.\-]+$/', 'Format attendu : 1.4.0')
          ->dansListe('frequence', array_keys(TicketRepository::FREQUENCES));

        $idProduit = (int) ($donnees['id_produit'] ?? 0);
        $idPlateforme = null;
        if ($idProduit && $this->produits->trouverParId($idProduit) === null) {
            $v->ajouter('id_produit', 'Jeu inconnu.');
        }
        if (!empty($donnees['plateforme'])) {
            $pf = $this->produits->plateformeParCode((string) $donnees['plateforme']);
            $pf ? $idPlateforme = (int) $pf['id_plateforme'] : $v->ajouter('plateforme', 'Plateforme inconnue.');
        }
        $v->verifier();

        $reference = $this->tickets->creer((int) $auteur['id_utilisateur'], [
            'id_produit'    => $idProduit ?: null,
            'id_plateforme' => $idPlateforme,
            'titre'         => (string) $v->valeur('titre'),
            'description'   => (string) $v->valeur('description'),
            'version_jeu'   => $v->valeur('version_jeu'),
            'frequence'     => $v->valeur('frequence'),
        ]);

        ($this->discord ?? new DiscordNotifier())->nouveauTicket($this->tickets->trouverParReference($reference));
        return $reference;
    }

    /** Ticket visible par l'utilisateur : son auteur ou un membre du support. 404 sinon (on ne révèle pas son existence). */
    public function consulter(string $reference, array $utilisateur): array
    {
        $ticket = $this->tickets->trouverParReference($reference);
        if ($ticket === null || (!Auth::estStaff($utilisateur) && (int) $ticket['id_auteur'] !== (int) $utilisateur['id_utilisateur'])) {
            throw new HttpException(404, 'ticket_introuvable', 'Ticket introuvable.');
        }
        return $ticket;
    }

    public function repondre(array $ticket, array $auteur, array $donnees): int
    {
        $v = new Validator($donnees);
        $v->requis('contenu')->longueur('contenu', 2, 5000);
        $v->verifier();
        if ($ticket['statut'] === 'clos') {
            throw new HttpException(409, 'ticket_clos', 'Ce ticket est clos. Ouvre un nouveau signalement si le problème revient.');
        }
        $staff = Auth::estStaff($auteur);
        $interne = $staff && !empty($donnees['interne']);
        $id = $this->tickets->ajouterMessage((int) $ticket['id_ticket'], (int) $auteur['id_utilisateur'], (string) $v->valeur('contenu'), $interne);

        // Le joueur répond à une demande d'information : le ticket repasse « en cours »
        if (!$staff && $ticket['statut'] === 'attente_joueur') {
            $this->tickets->mettreAJour($ticket, (int) $auteur['id_utilisateur'], 'en_cours', null, null, false, 'Réponse du joueur');
        }
        return $id;
    }

    /** Qualification / affectation / changement de statut (support uniquement). */
    public function traiter(array $ticket, array $agent, array $donnees): void
    {
        $statuts = array_column($this->tickets->statuts(), 'code');
        $priorites = array_column($this->tickets->priorites(), 'code');
        $staffIds = array_map('strval', array_column((new UtilisateurRepository())->listerStaff(), 'id_utilisateur'));

        $v = new Validator($donnees);
        $v->dansListe('statut', $statuts, 'Statut inconnu.')
          ->dansListe('priorite', $priorites, 'Priorité inconnue.')
          ->longueur('commentaire', 0, 255);
        $changerTechnicien = array_key_exists('id_technicien', $donnees);
        $idTech = $changerTechnicien && $donnees['id_technicien'] !== null && $donnees['id_technicien'] !== '' ? (string) $donnees['id_technicien'] : null;
        if ($idTech !== null && !in_array($idTech, $staffIds, true)) {
            $v->ajouter('id_technicien', 'Ce compte ne fait pas partie du support.');
        }
        if (!array_intersect(['statut', 'priorite', 'id_technicien'], array_keys($donnees))) {
            $v->ajouter('statut', 'Aucune modification demandée.');
        }
        $v->verifier();

        $this->tickets->mettreAJour(
            $ticket,
            (int) $agent['id_utilisateur'],
            ($donnees['statut'] ?? '') ?: null,
            ($donnees['priorite'] ?? '') ?: null,
            $idTech !== null ? (int) $idTech : null,
            $changerTechnicien,
            $v->valeur('commentaire')
        );
    }
}
