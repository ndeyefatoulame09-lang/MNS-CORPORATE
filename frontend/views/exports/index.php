<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/export_controller.php';
    handleExportRequest();
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireRole(['EXPERT']);

$clients = $clients ?? [];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0">Exports</h1>
            <a class="btn btn-outline-secondary" href="/MNS_CORPORATE/frontend/views/exports/backup.php">Sauvegarde SQL</a>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Clients</h2>
                        <p class="text-muted">Export CSV complet des clients.</p>
                        <a class="btn btn-primary" href="/MNS_CORPORATE/frontend/views/exports/index.php?type=clients">Exporter clients</a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Factures</h2>
                        <form method="get" action="/MNS_CORPORATE/frontend/views/exports/index.php" class="vstack gap-2">
                            <input type="hidden" name="type" value="invoices">
                            <select class="form-select" name="client_id">
                                <option value="">Tous les clients</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo e((string) $client['id']); ?>"><?php echo e((string) $client['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select" name="status">
                                <option value="">Tous les statuts</option>
                                <?php foreach (['BROUILLON','ENVOYEE','PARTIELLEMENT_PAYEE','PAYEE','EN_RETARD','ANNULEE'] as $status): ?>
                                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="row g-2">
                                <div class="col"><input class="form-control" type="date" name="issue_from"></div>
                                <div class="col"><input class="form-control" type="date" name="issue_to"></div>
                            </div>
                            <button class="btn btn-primary" type="submit">Exporter factures</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Paiements</h2>
                        <p class="text-muted">Export CSV des paiements encaisses.</p>
                        <a class="btn btn-primary" href="/MNS_CORPORATE/frontend/views/exports/index.php?type=payments">Exporter paiements</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
