<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/export_service.php';
require_once __DIR__ . '/../models/AuditLog.php';

function handleExportRequest(): void
{
    startSecureSession();
    requireRole(['EXPERT']);

    $type = $_GET['type'] ?? '';
    if ($type === 'clients') {
        exportClientsCsv();
    } elseif ($type === 'invoices') {
        exportInvoicesCsv();
    } elseif ($type === 'payments') {
        exportPaymentsCsv();
    } else {
        showExportIndex();
    }
}

function showExportIndex(): void
{
    $pdo = getDatabaseConnection();
    $clients = exportClientsOptions($pdo);
    renderExportView('index.php', compact('clients'));
}

function exportClientsCsv(): void
{
    $pdo = getDatabaseConnection();
    $rows = exportRows($pdo, 'SELECT company_name, legal_form, contact_name, email, phone, ninea, rccm, tax_regime, accounting_year_start, accounting_year_end, status, created_at FROM clients ORDER BY company_name');
    $data = array_map(static fn(array $row): array => [
        $row['company_name'], $row['legal_form'], $row['contact_name'], $row['email'], $row['phone'],
        $row['ninea'], $row['rccm'], $row['tax_regime'], trim((string) $row['accounting_year_start'] . ' - ' . (string) $row['accounting_year_end']),
        $row['status'], $row['created_at'],
    ], $rows);
    exportAudit($pdo, 'EXPORT_CLIENTS_CSV', 'Export CSV clients');
    streamCsv('clients_' . date('Ymd_His') . '.csv', ['Raison sociale', 'Forme juridique', 'Contact', 'Email', 'Telephone', 'NINEA', 'RCCM', 'Regime fiscal', 'Exercice', 'Statut', 'Date creation'], $data);
    exit;
}

function exportInvoicesCsv(): void
{
    $pdo = getDatabaseConnection();
    $params = [];
    $where = [];
    if (($_GET['client_id'] ?? '') !== '') {
        $where[] = 'i.client_id = :client_id';
        $params['client_id'] = $_GET['client_id'];
    }
    if (($_GET['status'] ?? '') !== '') {
        $where[] = 'i.status = :status';
        $params['status'] = $_GET['status'];
    }
    if (($_GET['issue_from'] ?? '') !== '') {
        $where[] = 'i.issue_date >= :issue_from';
        $params['issue_from'] = $_GET['issue_from'];
    }
    if (($_GET['issue_to'] ?? '') !== '') {
        $where[] = 'i.issue_date <= :issue_to';
        $params['issue_to'] = $_GET['issue_to'];
    }
    $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
    $rows = exportRows($pdo, "SELECT i.id, i.invoice_number, c.company_name, m.title AS mission_title, i.issue_date, i.due_date, i.subtotal, i.tax_amount, i.total_amount, COALESCE(SUM(p.amount), 0) AS paid_amount, GREATEST(i.total_amount - COALESCE(SUM(p.amount), 0), 0) AS balance_due, i.status FROM invoices i INNER JOIN clients c ON c.id = i.client_id LEFT JOIN missions m ON m.id = i.mission_id LEFT JOIN payments p ON p.invoice_id = i.id{$whereSql} GROUP BY i.id, i.invoice_number, c.company_name, m.title, i.issue_date, i.due_date, i.subtotal, i.tax_amount, i.total_amount, i.status ORDER BY i.issue_date DESC", $params);
    $data = array_map(static fn(array $row): array => [$row['invoice_number'], $row['company_name'], $row['mission_title'], $row['issue_date'], $row['due_date'], $row['subtotal'], $row['tax_amount'], $row['total_amount'], $row['paid_amount'], $row['balance_due'], $row['status']], $rows);
    exportAudit($pdo, 'EXPORT_FACTURES_CSV', 'Export CSV factures');
    streamCsv('factures_' . date('Ymd_His') . '.csv', ['Numero facture', 'Client', 'Mission', 'Date emission', 'Echeance', 'Montant HT', 'TVA', 'TTC', 'Montant paye', 'Solde', 'Statut'], $data);
    exit;
}

function exportPaymentsCsv(): void
{
    $pdo = getDatabaseConnection();
    $rows = exportRows($pdo, 'SELECT i.invoice_number, c.company_name, p.payment_date, p.amount, p.payment_method, p.reference_number, u.full_name AS receiver_name FROM payments p INNER JOIN invoices i ON i.id = p.invoice_id INNER JOIN clients c ON c.id = i.client_id INNER JOIN users u ON u.id = p.received_by ORDER BY p.payment_date DESC');
    $data = array_map(static fn(array $row): array => [$row['invoice_number'], $row['company_name'], $row['payment_date'], $row['amount'], $row['payment_method'], $row['reference_number'], $row['receiver_name']], $rows);
    exportAudit($pdo, 'EXPORT_PAIEMENTS_CSV', 'Export CSV paiements');
    streamCsv('paiements_' . date('Ymd_His') . '.csv', ['Facture', 'Client', 'Date paiement', 'Montant', 'Moyen paiement', 'Reference', 'Recu par'], $data);
    exit;
}

function exportRows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportClientsOptions(PDO $pdo): array
{
    return exportRows($pdo, 'SELECT id, company_name FROM clients ORDER BY company_name');
}

function exportAudit(PDO $pdo, string $action, string $description): void
{
    (new AuditLog($pdo))->log([
        'user_id' => currentUserId(),
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function renderExportView(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    if (!defined('MNS_CONTROLLER_RENDER')) {
        define('MNS_CONTROLLER_RENDER', true);
    }
    require __DIR__ . '/../../frontend/views/exports/' . $view;
}
