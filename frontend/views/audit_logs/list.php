<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/audit_log_controller.php';
    handleAuditLogRequest();
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireRole(['EXPERT']);

$pages = max(1, (int) ceil(($total ?? 0) / ($perPage ?? 20)));
$queryBase = $filters ?? [];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-column flex-lg-row">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Journal d'audit</h1>
                <div class="text-muted"><?php echo e((string) ($total ?? 0)); ?> ligne(s)</div>
            </div>
        </div>

        <form class="row g-2 mb-3" method="get">
            <div class="col-12 col-md-3">
                <select class="form-select" name="user_id">
                    <option value="">Tous utilisateurs</option>
                    <?php foreach (($users ?? []) as $user): ?>
                        <option value="<?php echo e((string) $user['id']); ?>" <?php echo (($filters['user_id'] ?? '') == $user['id']) ? 'selected' : ''; ?>>
                            <?php echo e($user['full_name'] . ' - ' . $user['role']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <select class="form-select" name="action">
                    <option value="">Toutes actions</option>
                    <?php foreach (($actions ?? []) as $action): ?>
                        <option value="<?php echo e($action['action']); ?>" <?php echo (($filters['action'] ?? '') === $action['action']) ? 'selected' : ''; ?>><?php echo e($action['action']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2"><input class="form-control" type="date" name="date_from" value="<?php echo e($filters['date_from'] ?? ''); ?>"></div>
            <div class="col-6 col-md-2"><input class="form-control" type="date" name="date_to" value="<?php echo e($filters['date_to'] ?? ''); ?>"></div>
            <div class="col-12 col-md-2"><input class="form-control" type="search" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Description ou IP"></div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button class="btn btn-outline-secondary" type="submit">Filtrer</button>
                <a class="btn btn-link" href="/MNS_CORPORATE/frontend/views/audit_logs/list.php">Reinitialiser</a>
            </div>
        </form>

        <div class="table-responsive bg-white border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Utilisateur</th><th>Role</th><th>Action</th><th>Description</th><th>IP</th></tr>
                </thead>
                <tbody>
                <?php foreach (($logs ?? []) as $log): ?>
                    <tr>
                        <td><?php echo e((string) $log['created_at']); ?></td>
                        <td><?php echo e((string) ($log['full_name'] ?? 'Systeme')); ?></td>
                        <td><?php echo e((string) ($log['role'] ?? '')); ?></td>
                        <td><span class="badge text-bg-secondary"><?php echo e((string) $log['action']); ?></span></td>
                        <td><?php echo e((string) $log['description']); ?></td>
                        <td><?php echo e((string) $log['ip_address']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (($logs ?? []) === []): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun log.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <nav class="mt-3">
            <ul class="pagination flex-wrap">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php $queryBase['page'] = $i; ?>
                    <li class="page-item <?php echo $i === ($page ?? 1) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo e(http_build_query($queryBase)); ?>"><?php echo e((string) $i); ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
