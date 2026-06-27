<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/client_controller.php';
    handleClientsRequest();
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireRole(['EXPERT']);

$flash = getFlashMessage();
$pages = max(1, (int) ceil(($total ?? 0) / ($perPage ?? 20)));
$queryBase = $filters ?? [];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container-fluid py-4">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>"><?php echo e($flash['message']); ?></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Clients</h1>
            <div class="text-muted"><?php echo e((string) ($total ?? 0)); ?> resultat(s)</div>
        </div>
        <a class="btn btn-primary" href="/MNS_CORPORATE/frontend/views/clients/create.php">Nouveau client</a>
    </div>

    <form method="get" action="/MNS_CORPORATE/frontend/views/clients/list.php" class="row g-2 mb-3">
        <div class="col-md-4"><input class="form-control" type="search" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Entreprise, contact, email, NINEA, RCCM"></div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">Tous statuts</option>
                <option value="ACTIF" <?php echo (($filters['status'] ?? '') === 'ACTIF') ? 'selected' : ''; ?>>ACTIF</option>
                <option value="INACTIF" <?php echo (($filters['status'] ?? '') === 'INACTIF') ? 'selected' : ''; ?>>INACTIF</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="tax_regime">
                <option value="">Tous regimes fiscaux</option>
                <?php foreach (($taxRegimes ?? []) as $tax): ?>
                    <option value="<?php echo e($tax['tax_regime']); ?>" <?php echo (($filters['tax_regime'] ?? '') === $tax['tax_regime']) ? 'selected' : ''; ?>><?php echo e($tax['tax_regime']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-outline-secondary" type="submit">Filtrer</button>
            <a class="btn btn-link" href="/MNS_CORPORATE/frontend/views/clients/list.php">Reinitialiser</a>
        </div>
    </form>

    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Entreprise</th><th>Contact</th><th>Email</th><th>NINEA / RCCM</th><th>Regime</th><th>Statut</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach (($clients ?? []) as $client): ?>
                    <tr>
                        <td><?php echo e($client['company_name']); ?></td>
                        <td><?php echo e($client['contact_name']); ?></td>
                        <td><?php echo e($client['email']); ?></td>
                        <td><?php echo e($client['ninea']); ?> / <?php echo e($client['rccm']); ?></td>
                        <td><?php echo e($client['tax_regime']); ?></td>
                        <td><span class="badge text-bg-<?php echo $client['status'] === 'ACTIF' ? 'success' : 'secondary'; ?>"><?php echo e($client['status']); ?></span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="/MNS_CORPORATE/frontend/views/clients/show.php?id=<?php echo e((string) $client['id']); ?>">Voir</a>
                            <a class="btn btn-sm btn-outline-secondary" href="/MNS_CORPORATE/frontend/views/clients/edit.php?id=<?php echo e((string) $client['id']); ?>">Modifier</a>
                            <form method="post" action="/MNS_CORPORATE/frontend/views/clients/list.php?action=toggle" class="d-inline" onsubmit="return confirm('Confirmer cette action ?');">
                                <input type="hidden" name="id" value="<?php echo e((string) $client['id']); ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit"><?php echo $client['status'] === 'ACTIF' ? 'Desactiver' : 'Reactiver'; ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($clients ?? []) === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun client trouve.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php $queryBase['page'] = $i; ?>
                <li class="page-item <?php echo $i === ($page ?? 1) ? 'active' : ''; ?>">
                    <a class="page-link" href="/MNS_CORPORATE/frontend/views/clients/list.php?<?php echo e(http_build_query($queryBase)); ?>"><?php echo e((string) $i); ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
