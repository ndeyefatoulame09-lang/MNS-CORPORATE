-- =========================================================
-- BASE DE DONNEES : MNS_CORPORATE
-- Plateforme de gestion d'un cabinet d'expertise comptable
-- =========================================================

CREATE DATABASE IF NOT EXISTS mns_corporate_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE mns_corporate_db;

-- =========================================================
-- 1. UTILISATEURS ET ROLES
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('EXPERT', 'COLLABORATEUR', 'STAGIAIRE', 'CLIENT') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 2. CLIENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL UNIQUE,
    company_name VARCHAR(180) NOT NULL,
    legal_form VARCHAR(100) NULL,
    contact_name VARCHAR(150) NULL,
    email VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    ninea VARCHAR(50) NULL UNIQUE,
    rccm VARCHAR(80) NULL UNIQUE,
    tax_regime VARCHAR(100) NULL,
    accounting_year_start DATE NULL,
    accounting_year_end DATE NULL,
    status ENUM('ACTIF', 'INACTIF') NOT NULL DEFAULT 'ACTIF',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 3. CATALOGUE DES TYPES DE MISSIONS
-- =========================================================

CREATE TABLE IF NOT EXISTS mission_catalog (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    default_duration_days INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 4. MISSIONS
-- =========================================================

CREATE TABLE IF NOT EXISTS missions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    mission_catalog_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    start_date DATE NOT NULL,
    planned_end_date DATE NULL,
    actual_end_date DATE NULL,
    status ENUM('A_FAIRE', 'EN_COURS', 'TERMINEE', 'EN_RETARD', 'ANNULEE')
        NOT NULL DEFAULT 'A_FAIRE',
    priority ENUM('BASSE', 'MOYENNE', 'HAUTE') NOT NULL DEFAULT 'MOYENNE',
    estimated_hours DECIMAL(8,2) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_missions_client
        FOREIGN KEY (client_id) REFERENCES clients(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_missions_catalog
        FOREIGN KEY (mission_catalog_id) REFERENCES mission_catalog(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_missions_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 5. AFFECTATION DES COLLABORATEURS AUX MISSIONS
-- =========================================================

CREATE TABLE IF NOT EXISTS mission_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    assigned_by INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    planned_start_date DATE NULL,
    planned_end_date DATE NULL,
    assignment_role VARCHAR(100) NULL,
    status ENUM('ASSIGNEE', 'EN_COURS', 'TERMINEE') NOT NULL DEFAULT 'ASSIGNEE',
    CONSTRAINT uq_assignment UNIQUE (mission_id, user_id),
    CONSTRAINT fk_assignments_mission
        FOREIGN KEY (mission_id) REFERENCES missions(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_assignments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_assignments_assigned_by
        FOREIGN KEY (assigned_by) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 6. DOCUMENTS DEPOSES DANS L'ESPACE CLIENT
-- =========================================================

CREATE TABLE IF NOT EXISTS documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    mission_id INT UNSIGNED NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_size INT UNSIGNED NULL,
    document_category ENUM('FACTURE', 'RELEVE_BANCAIRE', 'CONTRAT', 'DECLARATION', 'AUTRE')
        NOT NULL DEFAULT 'AUTRE',
    status ENUM('NOUVEAU', 'CONSULTE', 'VALIDE', 'REJETE')
        NOT NULL DEFAULT 'NOUVEAU',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documents_client
        FOREIGN KEY (client_id) REFERENCES clients(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_documents_mission
        FOREIGN KEY (mission_id) REFERENCES missions(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_documents_uploaded_by
        FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 7. COMMENTAIRES SUR LES DOCUMENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_document
        FOREIGN KEY (document_id) REFERENCES documents(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 8. ECHEANCES FISCALES
-- =========================================================

CREATE TABLE IF NOT EXISTS fiscal_deadlines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    mission_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    deadline_date DATE NOT NULL,
    status ENUM('A_VENIR', 'TERMINEE', 'EN_RETARD') NOT NULL DEFAULT 'A_VENIR',
    completed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_deadlines_client
        FOREIGN KEY (client_id) REFERENCES clients(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_deadlines_mission
        FOREIGN KEY (mission_id) REFERENCES missions(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 9. SUIVI DU TEMPS PASSE
-- =========================================================

CREATE TABLE IF NOT EXISTS timesheets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mission_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    work_date DATE NOT NULL,
    hours_worked DECIMAL(5,2) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('SAISI', 'VALIDE', 'REFUSE') NOT NULL DEFAULT 'SAISI',
    validated_by INT UNSIGNED NULL,
    validated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_timesheets_mission
        FOREIGN KEY (mission_id) REFERENCES missions(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_timesheets_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_timesheets_validated_by
        FOREIGN KEY (validated_by) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 10. FACTURES
-- =========================================================

CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    mission_id INT UNSIGNED NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('BROUILLON', 'ENVOYEE', 'PARTIELLEMENT_PAYEE', 'PAYEE', 'EN_RETARD', 'ANNULEE')
        NOT NULL DEFAULT 'BROUILLON',
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_invoices_client
        FOREIGN KEY (client_id) REFERENCES clients(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_mission
        FOREIGN KEY (mission_id) REFERENCES missions(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_invoices_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 11. PAIEMENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('ESPECES', 'VIREMENT', 'CHEQUE', 'WAVE', 'ORANGE_MONEY', 'AUTRE')
        NOT NULL DEFAULT 'VIREMENT',
    reference_number VARCHAR(100) NULL,
    notes TEXT NULL,
    received_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_invoice
        FOREIGN KEY (invoice_id) REFERENCES invoices(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_payments_received_by
        FOREIGN KEY (received_by) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 12. LETTRES DE MISSION ET SIGNATURE SIMPLE
-- =========================================================

CREATE TABLE IF NOT EXISTS engagement_letters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    mission_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    file_path VARCHAR(255) NULL,
    status ENUM('BROUILLON', 'ENVOYEE', 'SIGNEE') NOT NULL DEFAULT 'BROUILLON',
    sent_at DATETIME NULL,
    signed_at DATETIME NULL,
    signed_by_name VARCHAR(150) NULL,
    signature_text VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_letters_client
        FOREIGN KEY (client_id) REFERENCES clients(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_letters_mission
        FOREIGN KEY (mission_id) REFERENCES missions(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_letters_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 13. NOTIFICATIONS
-- =========================================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    channel ENUM('EMAIL', 'SMS', 'INTERNE') NOT NULL DEFAULT 'INTERNE',
    status ENUM('A_ENVOYER', 'ENVOYEE', 'LUE', 'ECHEC') NOT NULL DEFAULT 'A_ENVOYER',
    related_type VARCHAR(100) NULL,
    related_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 14. JOURNAL D'AUDIT
-- =========================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- INDEX UTILES
-- =========================================================

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_clients_company_name ON clients(company_name);
CREATE INDEX idx_missions_client_status ON missions(client_id, status);
CREATE INDEX idx_documents_client ON documents(client_id);
CREATE INDEX idx_deadlines_date_status ON fiscal_deadlines(deadline_date, status);
CREATE INDEX idx_invoices_client_status ON invoices(client_id, status);
CREATE INDEX idx_payments_invoice ON payments(invoice_id);

-- =========================================================
-- DONNEES DE DEMONSTRATION
-- Mot de passe des quatre comptes : password
-- =========================================================

INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
(1, 'Awa Ndiaye', 'expert@mns-corporate.sn', '770000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'EXPERT'),
(2, 'Mamadou Diallo', 'collaborateur@mns-corporate.sn', '770000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'COLLABORATEUR'),
(3, 'Fatou Sow', 'stagiaire@mns-corporate.sn', '770000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'STAGIAIRE'),
(4, 'Ibrahima Ba', 'client@mns-corporate.sn', '770000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'CLIENT');

INSERT INTO clients (
    id, user_id, company_name, legal_form, contact_name, email, phone,
    address, ninea, rccm, tax_regime, accounting_year_start, accounting_year_end
) VALUES (
    1, 4, 'Ba Services SARL', 'SARL', 'Ibrahima Ba', 'client@mns-corporate.sn',
    '770000004', 'Dakar, Sénégal', '123456789', 'SN-DKR-2026-B-1234',
    'Reel normal', '2026-01-01', '2026-12-31'
);

INSERT INTO mission_catalog (id, name, description, default_duration_days) VALUES
(1, 'Tenue comptable', 'Saisie et suivi des operations comptables.', 30),
(2, 'Declaration TVA', 'Preparation et declaration mensuelle de TVA.', 15),
(3, 'Impot sur les societes', 'Preparation de la declaration annuelle IS.', 30),
(4, 'Revision comptable', 'Verification et revision des comptes.', 20),
(5, 'Paie', 'Gestion des salaires et declarations sociales.', 30),
(6, 'Conseil fiscal', 'Accompagnement fiscal et administratif.', 10);

INSERT INTO missions (
    id, client_id, mission_catalog_id, title, description, start_date,
    planned_end_date, status, priority, estimated_hours, created_by
) VALUES
(1, 1, 1, 'Tenue comptable - Juin 2026',
    'Saisie des factures et rapprochement bancaire du mois de juin.',
    '2026-06-01', '2026-06-30', 'EN_COURS', 'HAUTE', 30.00, 1),
(2, 1, 2, 'Declaration TVA - Juin 2026',
    'Preparation de la declaration mensuelle de TVA.',
    '2026-06-10', '2026-06-15', 'A_FAIRE', 'HAUTE', 8.00, 1);

INSERT INTO mission_assignments (
    mission_id, user_id, assigned_by, planned_start_date, planned_end_date, assignment_role
) VALUES
(1, 2, 1, '2026-06-01', '2026-06-30', 'Responsable mission'),
(1, 3, 1, '2026-06-05', '2026-06-25', 'Assistant'),
(2, 2, 1, '2026-06-10', '2026-06-15', 'Responsable mission');

INSERT INTO fiscal_deadlines (
    client_id, mission_id, title, description, deadline_date, status
) VALUES
(1, 2, 'Declaration TVA Juin 2026',
    'Echeance mensuelle de declaration TVA.', '2026-06-15', 'A_VENIR'),
(1, NULL, 'Declaration IS 2026',
    'Echeance annuelle de l impot sur les societes.', '2026-04-30', 'EN_RETARD');

INSERT INTO timesheets (
    mission_id, user_id, work_date, hours_worked, description, status
) VALUES
(1, 2, '2026-06-05', 4.00, 'Saisie des factures fournisseurs.', 'VALIDE'),
(1, 3, '2026-06-06', 3.50, 'Classement des pieces comptables.', 'SAISI');

INSERT INTO invoices (
    id, client_id, mission_id, invoice_number, issue_date, due_date,
    subtotal, tax_rate, tax_amount, total_amount, status, notes, created_by
) VALUES
(1, 1, 1, 'FAC-2026-0001', '2026-06-01', '2026-06-15',
    150000.00, 18.00, 27000.00, 177000.00,
    'PARTIELLEMENT_PAYEE', 'Honoraires de tenue comptable.', 1),
(2, 1, 2, 'FAC-2026-0002', '2026-06-10', '2026-06-20',
    75000.00, 18.00, 13500.00, 88500.00,
    'ENVOYEE', 'Honoraires de declaration TVA.', 1);

INSERT INTO payments (
    invoice_id, payment_date, amount, payment_method, reference_number, received_by
) VALUES
(1, '2026-06-10', 100000.00, 'VIREMENT', 'VIR-2026-0001', 1);

INSERT INTO engagement_letters (
    client_id, mission_id, title, status, created_by
) VALUES
(1, 1, 'Lettre de mission - Tenue comptable 2026', 'ENVOYEE', 1);

INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES
(1, 'CREATION_BASE', 'Initialisation des donnees de demonstration.', '127.0.0.1');