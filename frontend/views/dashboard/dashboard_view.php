<?php
declare(strict_types=1);

if (!function_exists('e')) {
    require_once __DIR__ . '/../includes/helpers.php';
}

$flash = getFlashMessage();
$user = $user ?? null;
$role = $user['role'] ?? 'INVITE';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container py-5">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>" role="alert">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Tableau de bord</h1>
            <p class="text-muted mb-0">Bienvenue, <?php echo e($user['full_name'] ?? 'Utilisateur'); ?>.</p>
        </div>
        <a href="/MNS_CORPORATE/logout.php" class="btn btn-outline-secondary">Déconnexion</a>
    </div>

    <div class="row gy-4">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Information du compte</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Nom</dt>
                        <dd class="col-sm-8"><?php echo e($user['full_name'] ?? 'N/A'); ?></dd>

                        <dt class="col-sm-4 text-muted">Email</dt>
                        <dd class="col-sm-8"><?php echo e($user['email'] ?? 'N/A'); ?></dd>

                        <dt class="col-sm-4 text-muted">Rôle</dt>
                        <dd class="col-sm-8"><?php echo e($role); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Ressources rapides</h2>
                    <div class="list-group">
                        <?php if ($role === 'EXPERT'): ?>
                            <a href="#" class="list-group-item list-group-item-action">Gestion des dossiers clients</a>
                            <a href="#" class="list-group-item list-group-item-action">Planification des échéances</a>
                            <a href="#" class="list-group-item list-group-item-action">Suivi des honoraires</a>
                        <?php elseif ($role === 'COLLABORATEUR'): ?>
                            <a href="#" class="list-group-item list-group-item-action">Feuilles de temps</a>
                            <a href="#" class="list-group-item list-group-item-action">Documents partagés</a>
                            <a href="#" class="list-group-item list-group-item-action">Tâches en cours</a>
                        <?php elseif ($role === 'STAGIAIRE'): ?>
                            <a href="#" class="list-group-item list-group-item-action">Suivi des missions</a>
                            <a href="#" class="list-group-item list-group-item-action">Notes de formation</a>
                            <a href="#" class="list-group-item list-group-item-action">Supports internes</a>
                        <?php elseif ($role === 'CLIENT'): ?>
                            <a href="#" class="list-group-item list-group-item-action">Mes factures</a>
                            <a href="#" class="list-group-item list-group-item-action">Mes documents</a>
                            <a href="#" class="list-group-item list-group-item-action">Contact expert-comptable</a>
                        <?php else: ?>
                            <a href="#" class="list-group-item list-group-item-action">Page d'accueil</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card shadow-sm border-start border-4 border-primary">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-3">Résumé</h2>
                    <p class="mb-1">Accédez rapidement à votre espace de travail professionnel.</p>
                    <p class="small text-muted">Ce tableau de bord est construit pour un cabinet d’expertise comptable et reste compatible XAMPP.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
