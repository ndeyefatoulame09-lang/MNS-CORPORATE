<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

requireRole(['EXPERT']);

$client = $client ?? null;

if ($client === null) {
    setFlashMessage('error','Client introuvable.');
    redirect('/MNS_CORPORATE/clients.php');
}

?>
<div class="container py-4">
    <h1 class="h4 mb-3">Fiche client</h1>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h5 mb-3"><?php echo e($client['company_name']); ?></h2>
            <dl class="row">
                <dt class="col-sm-3 text-muted">Forme juridique</dt>
                <dd class="col-sm-9"><?php echo e($client['legal_form']); ?></dd>

                <dt class="col-sm-3 text-muted">Contact</dt>
                <dd class="col-sm-9"><?php echo e($client['contact_name']); ?></dd>

                <dt class="col-sm-3 text-muted">Email</dt>
                <dd class="col-sm-9"><?php echo e($client['email']); ?></dd>

                <dt class="col-sm-3 text-muted">Téléphone</dt>
                <dd class="col-sm-9"><?php echo e($client['phone']); ?></dd>

                <dt class="col-sm-3 text-muted">Adresse</dt>
                <dd class="col-sm-9"><?php echo e($client['address']); ?></dd>

                <dt class="col-sm-3 text-muted">NINEA</dt>
                <dd class="col-sm-9"><?php echo e($client['ninea']); ?></dd>

                <dt class="col-sm-3 text-muted">RCCM</dt>
                <dd class="col-sm-9"><?php echo e($client['rccm']); ?></dd>

                <dt class="col-sm-3 text-muted">Régime fiscal</dt>
                <dd class="col-sm-9"><?php echo e($client['tax_regime']); ?></dd>

                <dt class="col-sm-3 text-muted">Exercice comptable</dt>
                <dd class="col-sm-9"><?php echo e($client['accounting_year_start'] ?? 'N/A'); ?> → <?php echo e($client['accounting_year_end'] ?? 'N/A'); ?></dd>

                <dt class="col-sm-3 text-muted">Statut</dt>
                <dd class="col-sm-9"><?php echo e($client['status']); ?></dd>
            </dl>
        </div>
    </div>

    <a href="/MNS_CORPORATE/clients.php" class="btn btn-link">Retour à la liste</a>
    <a href="/MNS_CORPORATE/clients.php?action=edit&id=<?php echo e((string)$client['id']); ?>" class="btn btn-secondary">Modifier</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
