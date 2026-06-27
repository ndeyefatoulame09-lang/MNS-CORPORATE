<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/accounting_rules.php';
require_once __DIR__ . '/../includes/role_check.php';

function handleDashboardRequest(): void
{
    startSecureSession();
    requireAuth();

    $pdo = getDatabaseConnection();
    $user = currentUser();
    $role = $user['role'] ?? '';

    if ($role === 'EXPERT') {
        $dashboard = buildExpertDashboard($pdo);
    } elseif (in_array($role, ['COLLABORATEUR', 'STAGIAIRE'], true)) {
        $dashboard = buildWorkerDashboard($pdo, (int) $user['id']);
    } elseif ($role === 'CLIENT') {
        $dashboard = buildClientDashboard($pdo, (int) $user['id']);
    } else {
        $dashboard = [];
    }

    renderDashboardView('dashboard_view.php', compact('dashboard', 'user', 'role'));
}

function buildExpertDashboard(PDO $pdo): array
{
    $cards = [
        'active_clients' => dashboardScalar($pdo, "SELECT COUNT(*) FROM clients WHERE status = 'ACTIF'"),
        'active_missions' => dashboardScalar($pdo, "SELECT COUNT(*) FROM missions WHERE status = 'EN_COURS'"),
        'overdue_deadlines' => dashboardScalar($pdo, "SELECT COUNT(*) FROM fiscal_deadlines WHERE status = 'EN_RETARD'"),
        'unpaid_invoices' => dashboardFloat($pdo, "SELECT COALESCE(SUM(GREATEST(i.total_amount - COALESCE(p.paid, 0), 0)), 0) FROM invoices i LEFT JOIN (SELECT invoice_id, SUM(amount) AS paid FROM payments GROUP BY invoice_id) p ON p.invoice_id = i.id WHERE i.status <> 'ANNULEE' AND GREATEST(i.total_amount - COALESCE(p.paid, 0), 0) > 0"),
        'monthly_revenue' => dashboardFloat($pdo, 'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_date >= :start AND payment_date <= :end', [
            'start' => date('Y-m-01'),
            'end' => date('Y-m-t'),
        ]),
        'new_documents' => dashboardScalar($pdo, "SELECT COUNT(*) FROM documents WHERE status = 'NOUVEAU'"),
        'pending_timesheets' => dashboardScalar($pdo, "SELECT COUNT(*) FROM timesheets WHERE status = 'SAISI'"),
    ];

    return [
        'cards' => $cards,
        'monthlyRevenue' => dashboardMonthlyRevenue($pdo),
        'missionStatuses' => dashboardMissionStatuses($pdo),
        'overdueDeadlines' => dashboardRows($pdo, "SELECT fd.*, c.company_name FROM fiscal_deadlines fd INNER JOIN clients c ON c.id = fd.client_id WHERE fd.status = 'EN_RETARD' ORDER BY fd.deadline_date ASC LIMIT 5"),
        'topClients' => dashboardRows($pdo, 'SELECT c.company_name, COALESCE(SUM(p.amount), 0) AS total_paid FROM clients c INNER JOIN invoices i ON i.client_id = c.id INNER JOIN payments p ON p.invoice_id = i.id GROUP BY c.id, c.company_name ORDER BY total_paid DESC LIMIT 5'),
        'occupation' => dashboardOccupation($pdo),
        'unpaidAlerts' => dashboardRows($pdo, "SELECT i.id, i.invoice_number, i.due_date, i.status, i.total_amount, c.company_name, GREATEST(i.total_amount - COALESCE(SUM(p.amount), 0), 0) AS balance_due FROM invoices i INNER JOIN clients c ON c.id = i.client_id LEFT JOIN payments p ON p.invoice_id = i.id WHERE i.status <> 'ANNULEE' GROUP BY i.id, i.invoice_number, i.due_date, i.status, i.total_amount, c.company_name HAVING balance_due > 0 ORDER BY i.due_date ASC LIMIT 5"),
        'newDocuments' => dashboardRows($pdo, "SELECT d.title, d.uploaded_at, c.company_name FROM documents d INNER JOIN clients c ON c.id = d.client_id WHERE d.status = 'NOUVEAU' ORDER BY d.uploaded_at DESC LIMIT 5"),
        'pendingTimesheets' => dashboardRows($pdo, "SELECT t.work_date, t.hours_worked, u.full_name FROM timesheets t INNER JOIN users u ON u.id = t.user_id WHERE t.status = 'SAISI' ORDER BY t.work_date DESC LIMIT 5"),
    ];
}

function buildWorkerDashboard(PDO $pdo, int $userId): array
{
    return [
        'myMissions' => dashboardRows($pdo, "SELECT m.title, m.status, m.planned_end_date FROM missions m INNER JOIN mission_assignments ma ON ma.mission_id = m.id WHERE ma.user_id = :user_id AND m.status = 'EN_COURS' ORDER BY m.planned_end_date ASC LIMIT 10", ['user_id' => $userId]),
        'timesheets' => [
            'SAISI' => dashboardScalar($pdo, "SELECT COUNT(*) FROM timesheets WHERE user_id = :user_id AND status = 'SAISI'", ['user_id' => $userId]),
            'VALIDE' => dashboardScalar($pdo, "SELECT COUNT(*) FROM timesheets WHERE user_id = :user_id AND status = 'VALIDE'", ['user_id' => $userId]),
            'REFUSE' => dashboardScalar($pdo, "SELECT COUNT(*) FROM timesheets WHERE user_id = :user_id AND status = 'REFUSE'", ['user_id' => $userId]),
        ],
        'unreadNotifications' => dashboardScalar($pdo, "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND status <> 'LUE'", ['user_id' => $userId]),
        'deadlines' => dashboardRows($pdo, 'SELECT fd.title, fd.deadline_date, fd.status FROM fiscal_deadlines fd INNER JOIN missions m ON m.id = fd.mission_id INNER JOIN mission_assignments ma ON ma.mission_id = m.id WHERE ma.user_id = :user_id ORDER BY fd.deadline_date ASC LIMIT 10', ['user_id' => $userId]),
    ];
}

function buildClientDashboard(PDO $pdo, int $userId): array
{
    $client = dashboardRow($pdo, 'SELECT id FROM clients WHERE user_id = :user_id', ['user_id' => $userId]);
    $clientId = (int) ($client['id'] ?? 0);

    return [
        'clientId' => $clientId,
        'missions' => dashboardRows($pdo, 'SELECT title, status, planned_end_date FROM missions WHERE client_id = :client_id ORDER BY planned_end_date ASC LIMIT 10', ['client_id' => $clientId]),
        'documents' => dashboardRows($pdo, 'SELECT title, status, uploaded_at FROM documents WHERE client_id = :client_id ORDER BY uploaded_at DESC LIMIT 10', ['client_id' => $clientId]),
        'deadlines' => dashboardRows($pdo, 'SELECT title, deadline_date, status FROM fiscal_deadlines WHERE client_id = :client_id ORDER BY deadline_date ASC LIMIT 10', ['client_id' => $clientId]),
        'unpaidInvoices' => dashboardRows($pdo, "SELECT i.id, i.invoice_number, i.due_date, i.status, i.total_amount, GREATEST(i.total_amount - COALESCE(SUM(p.amount), 0), 0) AS balance_due FROM invoices i LEFT JOIN payments p ON p.invoice_id = i.id WHERE i.client_id = :client_id AND i.status <> 'ANNULEE' GROUP BY i.id, i.invoice_number, i.due_date, i.status, i.total_amount HAVING balance_due > 0 ORDER BY i.due_date ASC LIMIT 10", ['client_id' => $clientId]),
        'letters' => dashboardRows($pdo, 'SELECT title, status, sent_at, signed_at FROM engagement_letters WHERE client_id = :client_id ORDER BY created_at DESC LIMIT 10', ['client_id' => $clientId]),
        'unreadNotifications' => dashboardScalar($pdo, "SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND status <> 'LUE'", ['user_id' => $userId]),
    ];
}

function dashboardMonthlyRevenue(PDO $pdo): array
{
    $months = [];
    for ($i = 5; $i >= 0; $i--) {
        $key = date('Y-m', strtotime("-{$i} months"));
        $months[$key] = ['label' => date('m/Y', strtotime($key . '-01')), 'amount' => 0.0];
    }

    $rows = dashboardRows($pdo, "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month_key, COALESCE(SUM(amount), 0) AS amount FROM payments WHERE payment_date >= :start GROUP BY month_key ORDER BY month_key ASC", ['start' => array_key_first($months) . '-01']);
    foreach ($rows as $row) {
        if (isset($months[$row['month_key']])) {
            $months[$row['month_key']]['amount'] = (float) $row['amount'];
        }
    }

    return array_values($months);
}

function dashboardMissionStatuses(PDO $pdo): array
{
    $statuses = ['A_FAIRE' => 0, 'EN_COURS' => 0, 'TERMINEE' => 0, 'EN_RETARD' => 0, 'ANNULEE' => 0];
    $rows = dashboardRows($pdo, 'SELECT status, COUNT(*) AS total FROM missions GROUP BY status');
    foreach ($rows as $row) {
        if (array_key_exists($row['status'], $statuses)) {
            $statuses[$row['status']] = (int) $row['total'];
        }
    }
    return $statuses;
}

function dashboardOccupation(PDO $pdo): array
{
    $capacity = max(1, (float) MONTHLY_WORK_CAPACITY_HOURS);
    $rows = dashboardRows($pdo, "SELECT u.full_name, COALESCE(SUM(t.hours_worked), 0) AS hours_validated FROM users u LEFT JOIN timesheets t ON t.user_id = u.id AND t.status = 'VALIDE' AND t.work_date >= :start AND t.work_date <= :end WHERE u.is_active = 1 AND u.role IN ('COLLABORATEUR','STAGIAIRE') GROUP BY u.id, u.full_name ORDER BY hours_validated DESC LIMIT 5", [
        'start' => date('Y-m-01'),
        'end' => date('Y-m-t'),
    ]);
    foreach ($rows as &$row) {
        $row['occupation_rate'] = round(((float) $row['hours_validated'] / $capacity) * 100, 2);
    }
    unset($row);
    return $rows;
}

function dashboardScalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function dashboardFloat(PDO $pdo, string $sql, array $params = []): float
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (float) $stmt->fetchColumn();
}

function dashboardRow(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function dashboardRows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function renderDashboardView(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    if (!defined('MNS_CONTROLLER_RENDER')) {
        define('MNS_CONTROLLER_RENDER', true);
    }
    require __DIR__ . '/../../frontend/views/dashboard/' . $view;
}
