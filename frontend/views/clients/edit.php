<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

requireRole(['EXPERT']);

$flash = getFlashMessage();
$client = $client ?? null;

?>
<div class="container py-4">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>" role="alert">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <h1 class="h4 mb-3">Modifier le client</h1>

    <form method="post" action="/MNS_CORPORATE/clients.php?action=update">
        <input type="hidden" name="id" value="<?php echo e((string)$client['id']); ?>">
        <?php require __DIR__ . '/form.php'; ?>
        <div class="mt-3">
            <button class="btn btn-primary">Enregistrer</button>
            <a href="/MNS_CORPORATE/clients.php" class="btn btn-link">Annuler</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
