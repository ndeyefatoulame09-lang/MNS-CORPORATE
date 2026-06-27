# MNS CORPORATE

Plateforme web de gestion d'un cabinet d'expertise comptable avec espace client securise.

## Fonctionnalites principales

- Authentification multi-roles : EXPERT, COLLABORATEUR, STAGIAIRE, CLIENT.
- Gestion des clients et dossiers.
- Gestion des missions et catalogue des types de mission.
- Suivi des echeances fiscales.
- Depot, consultation, commentaires et validation des documents.
- Notifications internes avec e-mail optionnel.
- Timesheets, validation et synthese temps.
- Facturation, paiements et balance agee.
- Lettres de mission avec signature electronique simple.
- Dashboard par role avec graphiques Chart.js.
- Exports CSV et sauvegarde SQL via l'interface.
- Journal d'audit des actions sensibles.

## Technologies

- PHP 8+
- MySQL / MariaDB
- XAMPP
- PDO
- HTML5
- CSS3
- JavaScript
- Bootstrap
- Chart.js
- Git / GitHub

## Architecture du projet

- `backend/config/` : configuration base de donnees, regles comptables et e-mail.
- `backend/controllers/` : logique HTTP, validations, redirections et droits.
- `backend/models/` : acces aux tables MySQL via PDO.
- `backend/includes/` : sessions, helpers, controle roles, services d'upload, export, PDF et sauvegarde.
- `frontend/views/` : vues PHP Bootstrap par module.
- `frontend/assets/` : fichiers statiques et uploads de demonstration.
- `database/` : script SQL source de verite.
- `exports/` : dossier reserve aux exports si un stockage temporaire est necessaire.
- `pdf/` : dossier reserve aux sorties PDF si une bibliotheque est ajoutee plus tard.

## Installation locale

1. Installer XAMPP.
2. Demarrer Apache et MySQL.
3. Placer le projet dans :
   `C:\xampp\htdocs\MNS_CORPORATE`
4. Ouvrir phpMyAdmin.
5. Creer ou selectionner la base `mns_corporate_db`.
6. Importer :
   `database/cabinet_comptable.sql`
7. Ouvrir :
   `http://localhost/MNS_CORPORATE/`

## Configuration

- `backend/config/database.php` contient les parametres PDO locaux XAMPP.
- `backend/config/accounting_rules.php` contient les taux et constantes comptables de demonstration, dont la TVA et la capacite mensuelle de travail.
- `backend/config/mail_config.php` permet d'activer ou non l'envoi d'e-mails.

Les taux fiscaux et sociaux sont fournis pour le MVP et doivent etre valides par un professionnel avant tout usage reel.

## Comptes de demonstration

Tous les comptes utilisent le mot de passe de test : `password`.

| Role | E-mail | Mot de passe |
| --- | --- | --- |
| EXPERT | expert@mns-corporate.sn | password |
| COLLABORATEUR | collaborateur@mns-corporate.sn | password |
| COLLABORATEUR | collaborateur2@mns-corporate.sn | password |
| STAGIAIRE | stagiaire@mns-corporate.sn | password |
| CLIENT | client@mns-corporate.sn | password |
| CLIENT | client2@mns-corporate.sn | password |
| CLIENT | client3@mns-corporate.sn | password |

## Droits par role

- EXPERT : acces complet aux modules de gestion, exports, sauvegarde SQL et journal d'audit.
- COLLABORATEUR : missions affectees, echeances liees, documents lies, notifications et timesheets personnels.
- STAGIAIRE : acces limite aux missions affectees, documents lies, notifications et timesheets personnels.
- CLIENT : espace entreprise avec missions, echeances, documents, factures, paiements, lettres de mission et notifications.

## Securite

- Mots de passe stockes avec `password_hash` et verifies avec `password_verify`.
- Requetes preparees PDO.
- Sorties utilisateur echappees avec `e()` / `htmlspecialchars`.
- Controle d'acces avec `requireAuth()` et `requireRole()`.
- Sessions centralisees et regeneration de session apres connexion.
- Uploads controles par type MIME, taille et extension.
- Journal d'audit pour les actions sensibles.
- Les clients ne consultent que leurs propres donnees.
- Les collaborateurs et stagiaires ne consultent que les missions auxquelles ils sont affectes.

## Exports et sauvegarde

- Export CSV clients, factures et paiements pour EXPERT.
- CSV UTF-8 avec BOM et separateur `;` compatible Excel francais.
- Facture imprimable depuis la fiche facture. En absence de bibliotheque PDF locale, le navigateur permet d'imprimer ou sauvegarder en PDF.
- Sauvegarde SQL generee via PDO, sans `mysqldump`, `exec`, `shell_exec`, `system` ou `passthru`.

## Limites MVP

- E-mail local facultatif et desactive par defaut.
- SMS non implemente.
- Signature de lettre simple, non cryptographique.
- Regles fiscales et sociales parametrees pour demonstration et a valider avant production.
- La generation PDF native dependra d'une bibliotheque PDF locale si elle est ajoutee plus tard.

## Auteur / groupe

A completer :

- Nom(s) :
- Classe / formation :
- Encadrant :

## Depot GitHub

A completer :

- URL :
