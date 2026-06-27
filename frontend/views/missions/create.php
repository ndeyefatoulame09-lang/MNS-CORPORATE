<?php
declare(strict_types=1);
if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/mission_controller.php';
    if (($_GET['action'] ?? '') === 'store') {
        storeMission();
    } else {
        startSecureSession();
        requireRole(['EXPERT']);
        $model = new Mission(getDatabaseConnection());
        $mission = null;
        $clients = $model->getActiveClients();
        $catalogItems = $model->getActiveCatalogItems();
        $flash = getFlashMessage();
        require_once __DIR__ . '/../includes/header.php';
        ?>
        <div class="d-flex"><?php require_once __DIR__ . '/../includes/sidebar.php'; ?><main class="container py-4"><?php if ($flash !== null): ?><div class="alert alert-danger"><?php echo e($flash['message']); ?></div><?php endif; ?><h1 class="h4 mb-3">Nouvelle mission</h1><form method="post" action="/MNS_CORPORATE/frontend/views/missions/create.php?action=store"><?php require __DIR__ . '/form.php'; ?><div class="mt-3"><button class="btn btn-primary" type="submit">Creer</button><a class="btn btn-link" href="/MNS_CORPORATE/frontend/views/missions/list.php">Annuler</a></div></form></main></div><?php require_once __DIR__ . '/../includes/footer.php';
    }
    return;
}
require_once __DIR__ . '/../../../backend/includes/helpers.php'; require_once __DIR__ . '/../../../backend/includes/role_check.php'; requireRole(['EXPERT']); $flash = getFlashMessage(); require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex"><?php require_once __DIR__ . '/../includes/sidebar.php'; ?><main class="container py-4"><?php if ($flash !== null): ?><div class="alert alert-danger"><?php echo e($flash['message']); ?></div><?php endif; ?><h1 class="h4 mb-3">Nouvelle mission</h1><form method="post" action="/MNS_CORPORATE/frontend/views/missions/create.php?action=store"><?php require __DIR__ . '/form.php'; ?><div class="mt-3"><button class="btn btn-primary" type="submit">Creer</button><a class="btn btn-link" href="/MNS_CORPORATE/frontend/views/missions/list.php">Annuler</a></div></form></main></div><?php require_once __DIR__ . '/../includes/footer.php'; ?>
