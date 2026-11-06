# Base de données du portail joueurs

| Fichier | Contenu |
|---|---|
| [`01_creation.sql`](01_creation.sql) | création de la base `pixelwave` : 20 tables, contraintes, vue `v_produit_prix` |
| [`02_jeu_de_donnees.sql`](02_jeu_de_donnees.sql) | jeu de données de test (fictif) |
| [`03_requetes.sql`](03_requetes.sql) | 26 requêtes commentées (lecture, jointures, agrégats, modifications annulées par `ROLLBACK`) |
| [`schema.dbml`](schema.dbml) | schéma à coller sur [dbdiagram.io](https://dbdiagram.io) |
| [`schema.md`](schema.md) | schéma Mermaid affiché par GitHub |

## Installation

```bash
mariadb -u root -p < 01_creation.sql
mariadb -u root -p < 02_jeu_de_donnees.sql
```

Compte applicatif à créer (droits minimaux) :

```sql
CREATE USER 'pixelwave_app'@'localhost' IDENTIFIED BY '<mot de passe fort>';
GRANT SELECT, INSERT, UPDATE, DELETE ON pixelwave.* TO 'pixelwave_app'@'localhost';
```

Sur le serveur, `infra/srv-portail/install.sh` fait tout cela automatiquement avec un mot de passe aléatoire.

## Comptes de démonstration (jeu de données de test uniquement)

Mot de passe commun à tous les comptes de démonstration : **`Pixelwave!2026`**. Ces comptes n'existent que dans le jeu de données fictif : ne jamais charger `02_jeu_de_donnees.sql` sur un serveur ouvert au public sans changer ces mots de passe.

| Compte | E-mail | Profil | Particularité |
|---|---|---|---|
| `LeaPixel` | lea.martin@exemple.test | joueur | 4 jeux, une clé non révélée, tickets, jeton API |
| `DriftKing` | tom.durand@exemple.test | joueur | une commande remboursée, jeton API expiré |
| `ForgeMaster` | ines.bernard@exemple.test | joueur | ticket en attente de sa réponse |
| `AbyssDiver` | hugo.petit@exemple.test | joueur | ticket non qualifié |
| `NoobSaibot` | yanis.leroy@exemple.test | joueur | aucun achat |
| `OldTimer` | paul.roux@exemple.test | joueur | **compte désactivé** |
| `karim.support` | karim@pixelwave.test | support | traite les tickets |
| `julie.qa` | julie@pixelwave.test | support | |
| `nadia.admin` | nadia@pixelwave.test | admin | auteur des actualités |

Jeton API de démonstration (LeaPixel, valable jusqu'au 30/06/2027) : `pw_demo_LeaPixel_2026_9f8e7d6c5b4a`.
