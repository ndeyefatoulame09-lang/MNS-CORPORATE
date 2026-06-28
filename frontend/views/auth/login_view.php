<?php
declare(strict_types=1);

if (!function_exists('e')) {
    require_once __DIR__ . '/../includes/helpers.php';
}

$flash = getFlashMessage();
$oldEmail = old('email', '');
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
    .login-shell {
        min-height: 100vh;
        background:
            radial-gradient(circle at top right, rgba(0, 51, 153, 0.14), transparent 30rem),
            linear-gradient(135deg, #f7faff 0%, #eef3fb 48%, #ffffff 100%);
    }

    .login-card {
        border: 1px solid rgba(0, 51, 153, 0.09);
        border-radius: 24px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.13);
        overflow: hidden;
    }

    .login-logo {
        width: 112px;
        height: 112px;
        border-radius: 24px;
        object-fit: contain;
        padding: 12px;
        background: #ffffff;
        box-shadow: 0 16px 36px rgba(0, 51, 153, 0.16);
    }

    .login-card .form-control {
        border-radius: 14px;
        padding: 0.85rem 1rem;
    }

    .login-card .btn {
        border-radius: 999px;
        padding: 0.85rem 1rem;
        font-weight: 600;
    }
</style>

<main class="login-shell d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card login-card border-0">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <img class="login-logo mb-3" src="/MNS_CORPORATE/frontend/assets/images/logo.jpeg" alt="Logo MNS CORPORATE">
                            <h1 class="h3 fw-bold mb-1">Connexion</h1>
                            <p class="text-muted mb-0">Accedez a votre espace MNS CORPORATE</p>
                        </div>

                        <?php if ($flash !== null): ?>
                            <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>" role="alert">
                                <?php echo e($flash['message']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="/MNS_CORPORATE/login.php" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="<?php echo e($oldEmail); ?>"
                                    required
                                    autofocus
                                >
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    required
                                >
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                        </form>
                    </div>
                </div>
                <p class="text-center text-muted mt-3">Cabinet d'expertise comptable MNS CORPORATE</p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
