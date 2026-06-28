<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/includes/session.php';
require_once __DIR__ . '/backend/includes/auth_check.php';
require_once __DIR__ . '/backend/includes/helpers.php';

startSecureSession();
$user = currentUser();

if ($user !== null) {
    require_once __DIR__ . '/backend/controllers/dashboard_controller.php';
    handleDashboardRequest();
    return;
}

$flash = getFlashMessage();
require_once __DIR__ . '/frontend/views/includes/header.php';
?>

<style>
    .welcome-shell {
        min-height: calc(100vh - 1px);
        background:
            radial-gradient(circle at top left, rgba(0, 51, 153, 0.12), transparent 32rem),
            linear-gradient(135deg, #f8fbff 0%, #eef3fb 52%, #ffffff 100%);
    }

    .welcome-panel {
        border: 1px solid rgba(0, 51, 153, 0.08);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        background: #ffffff;
    }

    .welcome-brand {
        background: linear-gradient(145deg, #003399 0%, #06245f 100%);
        min-height: 100%;
    }

    .welcome-logo-box {
        width: 168px;
        height: 168px;
        border-radius: 28px;
        background: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.18);
    }

    .welcome-logo-box img {
        max-width: 132px;
        max-height: 132px;
        object-fit: contain;
    }

    .welcome-actions .btn {
        border-radius: 999px;
        padding: 0.85rem 1.35rem;
        font-weight: 600;
    }
</style>

<main class="welcome-shell d-flex align-items-center py-5">
    <div class="container">
        <?php if ($flash !== null): ?>
            <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?> mb-4" role="alert">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="welcome-panel">
            <div class="row g-0 align-items-stretch">
                <div class="col-lg-5">
                    <div class="welcome-brand text-white p-5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="welcome-logo-box mb-4">
                                <img src="/MNS_CORPORATE/frontend/assets/images/logo.jpeg" alt="Logo MNS CORPORATE">
                            </div>
                            <p class="text-white-50 mb-2">Cabinet d'expertise comptable</p>
                            <h1 class="display-6 fw-bold mb-0">MNS CORPORATE</h1>
                        </div>
                        <p class="mt-5 mb-0 text-white-50">Un espace securise pour piloter vos clients, missions et documents professionnels.</p>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="p-5">
                        <span class="badge rounded-pill text-bg-light border mb-3">Plateforme professionnelle</span>
                        <h2 class="display-5 fw-bold mb-3">Bienvenue sur votre espace de gestion</h2>
                        <p class="lead text-muted mb-4">Connectez-vous pour acceder au tableau de bord et suivre l'activite du cabinet dans une interface claire, moderne et securisee.</p>
                        <div class="welcome-actions d-flex flex-column flex-sm-row gap-3">
                            <a href="/MNS_CORPORATE/login.php" class="btn btn-primary btn-lg">Se connecter</a>
                            <a href="/MNS_CORPORATE/login.php" class="btn btn-outline-primary btn-lg">Acceder au tableau de bord</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/frontend/views/includes/footer.php';
