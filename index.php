<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/session.php';
require_once __DIR__ . '/backend/includes/auth_check.php';
require_once __DIR__ . '/backend/includes/helpers.php';

startSecureSession();
$user = currentUser();

if ($user !== null) {
    require_once __DIR__ . '/frontend/views/dashboard/dashboard_view.php';
    return;
}

$flash = getFlashMessage();
require_once __DIR__ . '/frontend/views/includes/header.php';
?>

<div class="container py-5">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>" role="alert">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h1 class="display-5 mb-3">Bienvenue chez MNS CORPORATE</h1>
                    <p class="lead text-muted">Cabinet d'expertise comptable moderne et professionnel. Connectez-vous pour accéder à votre espace sécurisé.</p>
                    <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                        <a href="/MNS_CORPORATE/login.php" class="btn btn-primary btn-lg">Se connecter</a>
                        <a href="/MNS_CORPORATE/login.php" class="btn btn-outline-secondary btn-lg">Accéder au tableau de bord</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/frontend/views/includes/footer.php';
