<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../models/AuditLog.php';

function handleAuditLogRequest(): void
{
    startSecureSession();
    requireRole(['EXPERT']);

    $pdo = getDatabaseConnection();
    $model = new AuditLog($pdo);
    $filters = auditLogFilters();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $logs = $model->findAll($filters, $perPage, ($page - 1) * $perPage);
    $total = $model->countAll($filters);
    $users = $model->findUsers();
    $actions = $model->findActions();

    renderAuditLogView('list.php', compact('logs', 'total', 'filters', 'page', 'perPage', 'users', 'actions'));
}

function auditLogFilters(): array
{
    $filters = [];
    foreach (['user_id', 'action', 'date_from', 'date_to', 'q'] as $field) {
        $value = trim((string) ($_GET[$field] ?? ''));
        if ($value !== '') {
            $filters[$field] = $value;
        }
    }

    return $filters;
}

function renderAuditLogView(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    if (!defined('MNS_CONTROLLER_RENDER')) {
        define('MNS_CONTROLLER_RENDER', true);
    }
    require __DIR__ . '/../../frontend/views/audit_logs/' . $view;
}
