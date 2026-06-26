<?php
declare(strict_types=1);

if (!function_exists('e')) {
    require_once __DIR__ . '/../includes/helpers.php';
}

$flash = getFlashMessage();
$oldEmail = old('email', '');
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3 text-center">Connexion</h1>

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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
