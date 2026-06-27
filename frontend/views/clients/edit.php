<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/client_controller.php';
    if (($_GET['action'] ?? '') === 'update') {
        updateClient((int) ($_POST['id'] ?? 0));
    } else {
        startSecureSession();
        requireRole(['EXPERT']);
        $client = findClientOrRedirect((int) ($_GET['id'] ?? 0));
        $flash = getFlashMessage();
        require_once __DIR__ . '/../includes/header.php';
        ?>
        <div class="d-flex">
        <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="container py-4">
            <?php if ($flash !== null): ?><div class="alert alert-danger"><?php echo e($flash['message']); ?></div><?php endif; ?>
            <h1 class="h4 mb-3">Modifier le client</h1>
            <form method="post" action="/MNS_CORPORATE/frontend/views/clients/edit.php?action=update">
                <input type="hidden" name="id" value="<?php echo e((string) $client['id']); ?>">
                <?php require __DIR__ . '/form.php'; ?>
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit">Enregistrer</button>
                    <a class="btn btn-link" href="/MNS_CORPORATE/frontend/views/clients/list.php">Annuler</a>
                </div>
            </form>
        </main>
        </div>
        <?php require_once __DIR__ . '/../includes/footer.php';
    }
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireRole(['EXPERT']);
$flash = getFlashMessage();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<main class="container py-4">
    <?php if ($flash !== null): ?><div class="alert alert-danger"><?php echo e($flash['message']); ?></div><?php endif; ?>
    <h1 class="h4 mb-3">Modifier le client</h1>
    <form method="post" action="/MNS_CORPORATE/frontend/views/clients/edit.php?action=update">
        <input type="hidden" name="id" value="<?php echo e((string) $client['id']); ?>">
        <?php require __DIR__ . '/form.php'; ?>
        <div class="mt-3">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn btn-link" href="/MNS_CORPORATE/frontend/views/clients/list.php">Annuler</a>
        </div>
    </form>
</main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
