<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/mission_controller.php';
    handleMissionsRequest();
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireAuth();
$user = currentUser();
$isExpert = ($user['role'] ?? '') === 'EXPERT';
$flash = getFlashMessage();
$pages = max(1, (int) ceil(($total ?? 0) / ($perPage ?? 20)));
$queryBase = $filters ?? [];
unset($queryBase['assigned_user_id'], $queryBase['visible_client_id']);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container-fluid py-4">
    <?php if ($flash !== null): ?><div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h1 class="h4 mb-1"><?php echo $isExpert ? 'Missions' : 'Mes missions'; ?></h1><div class="text-muted"><?php echo e((string) ($total ?? 0)); ?> resultat(s)</div></div>
        <?php if ($isExpert): ?>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="/MNS_CORPORATE/frontend/views/missions/catalog_list.php">Catalogue</a>
            <a class="btn btn-primary" href="/MNS_CORPORATE/frontend/views/missions/create.php">Nouvelle mission</a>
        </div>
        <?php endif; ?>
    </div>
    <form class="row g-2 mb-3" method="get" action="/MNS_CORPORATE/frontend/views/missions/list.php">
        <div class="col-md-3"><input class="form-control" type="search" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Titre ou description"></div>
        <?php if ($isExpert): ?><div class="col-md-2"><select class="form-select" name="client_id"><option value="">Tous clients</option><?php foreach (($clients ?? []) as $client): ?><option value="<?php echo e((string) $client['id']); ?>" <?php echo (($filters['client_id'] ?? '') == $client['id']) ? 'selected' : ''; ?>><?php echo e($client['company_name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-2"><select class="form-select" name="mission_catalog_id"><option value="">Tous types</option><?php foreach (($catalogItems ?? []) as $item): ?><option value="<?php echo e((string) $item['id']); ?>" <?php echo (($filters['mission_catalog_id'] ?? '') == $item['id']) ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="col-md-1"><select class="form-select" name="status"><option value="">Statut</option><?php foreach (Mission::STATUSES as $status): ?><option value="<?php echo e($status); ?>" <?php echo (($filters['status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-1"><select class="form-select" name="priority"><option value="">Priorite</option><?php foreach (Mission::PRIORITIES as $priority): ?><option value="<?php echo e($priority); ?>" <?php echo (($filters['priority'] ?? '') === $priority) ? 'selected' : ''; ?>><?php echo e($priority); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-1"><input class="form-control" type="date" name="start_from" value="<?php echo e($filters['start_from'] ?? ''); ?>"></div>
        <div class="col-md-1"><input class="form-control" type="date" name="start_to" value="<?php echo e($filters['start_to'] ?? ''); ?>"></div>
        <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filtrer</button></div>
    </form>
    <div class="table-responsive bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Titre</th><th>Client</th><th>Type</th><th>Debut</th><th>Fin prevue</th><th>Statut</th><th>Priorite</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach (($missions ?? []) as $mission): ?>
                <tr>
                    <td><?php echo e($mission['title']); ?></td><td><?php echo e($mission['client_name']); ?></td><td><?php echo e($mission['catalog_name']); ?></td>
                    <td><?php echo e($mission['start_date']); ?></td><td><?php echo e($mission['planned_end_date']); ?></td>
                    <td><span class="badge text-bg-secondary"><?php echo e($mission['status']); ?></span></td><td><span class="badge text-bg-info"><?php echo e($mission['priority']); ?></span></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary" href="/MNS_CORPORATE/frontend/views/missions/show.php?id=<?php echo e((string) $mission['id']); ?>">Voir</a>
                        <?php if ($isExpert): ?>
                        <a class="btn btn-sm btn-outline-secondary" href="/MNS_CORPORATE/frontend/views/missions/edit.php?id=<?php echo e((string) $mission['id']); ?>">Modifier</a>
                        <form method="post" action="/MNS_CORPORATE/frontend/views/missions/list.php?action=status" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo e((string) $mission['id']); ?>">
                            <select name="status" class="form-select form-select-sm d-inline-block w-auto"><?php foreach (Mission::STATUSES as $status): ?><option value="<?php echo e($status); ?>" <?php echo $mission['status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select>
                            <button class="btn btn-sm btn-outline-dark" type="submit">Changer</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (($missions ?? []) === []): ?><tr><td colspan="8" class="text-center text-muted py-4">Aucune mission trouvee.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav class="mt-3"><ul class="pagination"><?php for ($i = 1; $i <= $pages; $i++): ?><?php $queryBase['page'] = $i; ?><li class="page-item <?php echo $i === ($page ?? 1) ? 'active' : ''; ?>"><a class="page-link" href="/MNS_CORPORATE/frontend/views/missions/list.php?<?php echo e(http_build_query($queryBase)); ?>"><?php echo e((string) $i); ?></a></li><?php endfor; ?></ul></nav>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
