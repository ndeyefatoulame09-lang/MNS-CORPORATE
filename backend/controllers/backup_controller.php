<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/backup_service.php';
require_once __DIR__ . '/../models/AuditLog.php';

function handleBackupRequest(): void
{
    startSecureSession();
    requireRole(['EXPERT']);

    if (isPostRequest()) {
        downloadSqlBackup();
        return;
    }

    renderBackupView('backup.php');
}

function downloadSqlBackup(): void
{
    $pdo = getDatabaseConnection();
    (new AuditLog($pdo))->log([
        'user_id' => currentUserId(),
        'action' => 'EXPORT_SAUVEGARDE_SQL',
        'description' => 'Telechargement sauvegarde SQL',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    streamSqlBackup($pdo, 'mns_corporate_db');
    exit;
}

function renderBackupView(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    if (!defined('MNS_CONTROLLER_RENDER')) {
        define('MNS_CONTROLLER_RENDER', true);
    }
    require __DIR__ . '/../../frontend/views/exports/' . $view;
}
