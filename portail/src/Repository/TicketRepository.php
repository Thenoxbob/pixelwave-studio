<?php
declare(strict_types=1);

namespace Pixelwave\Repository;

final class TicketRepository extends Repository
{
    private const SELECT = "
        SELECT t.id_ticket, t.reference, t.titre, t.description, t.version_jeu, t.frequence,
               t.date_creation, t.date_maj, t.id_auteur, t.id_produit, t.id_plateforme, t.id_technicien,
               s.code AS statut, s.libelle AS statut_libelle, s.est_final,
               pr.code AS priorite, pr.libelle AS priorite_libelle,
               auteur.pseudo AS auteur, tech.pseudo AS technicien,
               p.titre AS produit, pf.code AS plateforme
        FROM ticket t
        JOIN statut_ticket s       ON s.id_statut = t.id_statut
        JOIN utilisateur auteur    ON auteur.id_utilisateur = t.id_auteur
        LEFT JOIN priorite pr      ON pr.id_priorite = t.id_priorite
        LEFT JOIN utilisateur tech ON tech.id_utilisateur = t.id_technicien
        LEFT JOIN produit p        ON p.id_produit = t.id_produit
        LEFT JOIN plateforme pf    ON pf.id_plateforme = t.id_plateforme";

    public const FREQUENCES = ['toujours' => 'À chaque fois', 'parfois' => 'Parfois', 'une_fois' => 'Une seule fois'];

    public function listerPourJoueur(int $idUtilisateur): array
    {
        return $this->tous(self::SELECT . ' WHERE t.id_auteur = ? ORDER BY t.date_maj DESC', [$idUtilisateur]);
    }

    /** File du support : filtres statut (code) et « ouverts » (non clos). */
    public function listerTous(array $filtres = []): array
    {
        $where = [];
        $params = [];
        if (!empty($filtres['statut'])) {
            $where[] = 's.code = ?';
            $params[] = $filtres['statut'];
        } elseif (!empty($filtres['ouverts'])) {
            $where[] = 's.est_final = FALSE';
        }
        $sql = self::SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . ' ORDER BY (t.id_priorite IS NULL) DESC, pr.niveau DESC, t.date_creation ASC LIMIT 200';
        return $this->tous($sql, $params);
    }

    public function trouverParReference(string $reference): ?array
    {
        return $this->un(self::SELECT . ' WHERE t.reference = ?', [$reference]);
    }

    public function messages(int $idTicket, bool $inclureInternes): array
    {
        return $this->tous(
            'SELECT m.id_message, m.contenu, m.interne, m.date_envoi, u.pseudo AS auteur, r.code AS role
             FROM message_ticket m
             JOIN utilisateur u ON u.id_utilisateur = m.id_auteur
             JOIN role r        ON r.id_role = u.id_role
             WHERE m.id_ticket = ?' . ($inclureInternes ? '' : ' AND m.interne = FALSE') . '
             ORDER BY m.date_envoi, m.id_message',
            [$idTicket]
        );
    }

    public function historique(int $idTicket): array
    {
        return $this->tous(
            'SELECT h.date_changement, s.libelle AS statut, u.pseudo AS auteur, h.commentaire
             FROM historique_ticket h
             JOIN statut_ticket s ON s.id_statut = h.id_statut
             JOIN utilisateur u   ON u.id_utilisateur = h.id_auteur
             WHERE h.id_ticket = ?
             ORDER BY h.date_changement, h.id_historique',
            [$idTicket]
        );
    }

    /** Crée un ticket au statut « nouveau » et retourne sa référence (BUG-2026-0007). */
    public function creer(int $idAuteur, array $d): string
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $this->executer(
                "INSERT INTO ticket (reference, id_auteur, id_produit, id_plateforme, titre, description, version_jeu, frequence, id_statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, (SELECT id_statut FROM statut_ticket WHERE code = 'nouveau'))",
                [
                    'TMP-' . bin2hex(random_bytes(8)), $idAuteur,
                    $d['id_produit'] ?: null, $d['id_plateforme'] ?: null,
                    $d['titre'], $d['description'], $d['version_jeu'] ?: null, $d['frequence'] ?: null,
                ]
            );
            $id = (int) $pdo->lastInsertId();
            $reference = sprintf('BUG-%s-%04d', date('Y'), $id);
            $this->executer('UPDATE ticket SET reference = ? WHERE id_ticket = ?', [$reference, $id]);
            $this->executer(
                "INSERT INTO historique_ticket (id_ticket, id_statut, id_auteur, commentaire)
                 VALUES (?, (SELECT id_statut FROM statut_ticket WHERE code = 'nouveau'), ?, 'Création du ticket')",
                [$id, $idAuteur]
            );
            $pdo->commit();
            return $reference;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function ajouterMessage(int $idTicket, int $idAuteur, string $contenu, bool $interne): int
    {
        $this->executer('INSERT INTO message_ticket (id_ticket, id_auteur, contenu, interne) VALUES (?, ?, ?, ?)',
            [$idTicket, $idAuteur, $contenu, (int) $interne]);
        $this->executer('UPDATE ticket SET date_maj = NOW() WHERE id_ticket = ?', [$idTicket]);
        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Traitement par le support : statut, priorité, technicien.
     * Chaque changement de statut est tracé dans l'historique.
     */
    public function mettreAJour(array $ticket, int $idAuteur, ?string $statut, ?string $priorite, ?int $idTechnicien, bool $changerTechnicien, ?string $commentaire): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            if ($priorite !== null) {
                $this->executer('UPDATE ticket SET id_priorite = (SELECT id_priorite FROM priorite WHERE code = ?) WHERE id_ticket = ?',
                    [$priorite, $ticket['id_ticket']]);
            }
            if ($changerTechnicien) {
                $this->executer('UPDATE ticket SET id_technicien = ? WHERE id_ticket = ?', [$idTechnicien, $ticket['id_ticket']]);
            }
            if ($statut !== null && $statut !== $ticket['statut']) {
                $this->executer('UPDATE ticket SET id_statut = (SELECT id_statut FROM statut_ticket WHERE code = ?) WHERE id_ticket = ?',
                    [$statut, $ticket['id_ticket']]);
                $this->executer(
                    'INSERT INTO historique_ticket (id_ticket, id_statut, id_auteur, commentaire)
                     VALUES (?, (SELECT id_statut FROM statut_ticket WHERE code = ?), ?, ?)',
                    [$ticket['id_ticket'], $statut, $idAuteur, $commentaire ?: null]
                );
            }
            $this->executer('UPDATE ticket SET date_maj = NOW() WHERE id_ticket = ?', [$ticket['id_ticket']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function statuts(): array
    {
        return $this->tous('SELECT code, libelle, est_final FROM statut_ticket ORDER BY ordre');
    }

    public function priorites(): array
    {
        return $this->tous('SELECT code, libelle, niveau FROM priorite ORDER BY niveau');
    }
}
