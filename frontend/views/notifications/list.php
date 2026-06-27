<?php
declare(strict_types=1);
if (!defined('MNS_CONTROLLER_RENDER')) { require_once __DIR__ . '/../../../backend/controllers/notification_controller.php'; handleNotificationRequest(); return; }
require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireAuth();
$flash = getFlashMessage();
$pages = max(1, (int) ceil(($total ?? 0) / ($perPage ?? 20)));
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container-fluid py-4">
    <?php if ($flash): ?><div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h1 class="h4 mb-1">Mes notifications</h1><div class="text-muted"><?php echo e((string) ($unreadCount ?? 0)); ?> non lue(s)</div></div>
        <form method="post" action="/MNS_CORPORATE/frontend/views/notifications/list.php?action=read_all"><button class="btn btn-outline-secondary">Tout marquer comme lu</button></form>
    </div>
    <div class="bg-white border rounded">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Titre</th><th>Message</th><th>Canal</th><th>Statut</th><th>Date</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach (($notifications ?? []) as $notification): ?>
                <tr>
                    <td><?php echo e($notification['title']); ?></td>
                    <td><?php echo e($notification['message']); ?></td>
                    <td><?php echo e($notification['channel']); ?></td>
                    <td><span class="badge text-bg-<?php echo $notification['status'] === 'LUE' ? 'secondary' : 'primary'; ?>"><?php echo e($notification['status'] === 'LUE' ? 'lue' : 'non lue'); ?></span></td>
                    <td><?php echo e($notification['created_at']); ?></td>
                    <td class="text-end"><?php if ($notification['status'] !== 'LUE'): ?><form method="post" action="/MNS_CORPORATE/frontend/views/notifications/list.php?action=read" class="d-inline"><input type="hidden" name="id" value="<?php echo e((string) $notification['id']); ?>"><button class="btn btn-sm btn-outline-primary">Marquer lue</button></form><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (($notifications ?? []) === []): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucune notification.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav class="mt-3"><ul class="pagination"><?php for ($i = 1; $i <= $pages; $i++): ?><li class="page-item <?php echo $i === ($page ?? 1) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo e((string) $i); ?>"><?php echo e((string) $i); ?></a></li><?php endfor; ?></ul></nav>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
