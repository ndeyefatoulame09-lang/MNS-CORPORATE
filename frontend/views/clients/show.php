<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/client_controller.php';
    showClient((int) ($_GET['id'] ?? 0));
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1"><?php echo e($client['company_name']); ?></h1>
            <span class="badge text-bg-<?php echo $client['status'] === 'ACTIF' ? 'success' : 'secondary'; ?>"><?php echo e($client['status']); ?></span>
        </div>
        <a class="btn btn-secondary" href="/MNS_CORPORATE/frontend/views/clients/edit.php?id=<?php echo e((string) $client['id']); ?>">Modifier</a>
    </div>
    <div class="bg-white border rounded p-3">
        <dl class="row mb-0">
            <?php foreach (['legal_form' => 'Forme juridique', 'contact_name' => 'Contact', 'email' => 'Email', 'phone' => 'Telephone', 'address' => 'Adresse', 'ninea' => 'NINEA', 'rccm' => 'RCCM', 'tax_regime' => 'Regime fiscal', 'accounting_year_start' => 'Debut exercice', 'accounting_year_end' => 'Fin exercice', 'created_at' => 'Creation', 'updated_at' => 'Modification'] as $field => $label): ?>
                <dt class="col-sm-3 text-muted"><?php echo e($label); ?></dt>
                <dd class="col-sm-9"><?php echo e((string) ($client[$field] ?? '')); ?></dd>
            <?php endforeach; ?>
        </dl>
    </div>
    <a class="btn btn-link mt-3" href="/MNS_CORPORATE/frontend/views/clients/list.php">Retour a la liste</a>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
