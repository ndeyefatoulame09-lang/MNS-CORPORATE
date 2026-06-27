<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/backup_controller.php';
    handleBackupRequest();
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireRole(['EXPERT']);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container py-4">
        <h1 class="h4 mb-3">Sauvegarde base de donnees</h1>
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="text-muted">La sauvegarde SQL est generee a la demande via PDO et telechargee directement.</p>
                <form method="post" action="/MNS_CORPORATE/frontend/views/exports/backup.php">
                    <button class="btn btn-danger" type="submit">Telecharger la sauvegarde SQL</button>
                    <a class="btn btn-outline-secondary" href="/MNS_CORPORATE/frontend/views/exports/index.php">Retour exports</a>
                </form>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
