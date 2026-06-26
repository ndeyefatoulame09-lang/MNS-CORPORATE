<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';

requireRole(['EXPERT']);

$flash = getFlashMessage();
$filters = $filters ?? [];
$page = $page ?? 1;
$perPage = $perPage ?? 20;
$total = $total ?? 0;
$clients = $clients ?? [];

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container py-4">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>" role="alert">
            <?php echo e($flash['message']); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Clients</h1>
        <a href="/MNS_CORPORATE/clients.php?action=create" class="btn btn-primary">Nouveau client</a>
    </div>

    <form method="get" action="/MNS_CORPORATE/clients.php" class="row g-2 mb-3">
        <input type="hidden" name="action" value="list">
        <div class="col-md-4">
            <input type="search" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" class="form-control" placeholder="Recherche par nom, contact, email, NINEA ou RCCM">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Tous statuts</option>
                <option value="ACTIF" <?php echo (isset($filters['status']) && $filters['status']==='ACTIF') ? 'selected' : ''; ?>>ACTIF</option>
                <option value="INACTIF" <?php echo (isset($filters['status']) && $filters['status']==='INACTIF') ? 'selected' : ''; ?>>INACTIF</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="tax_regime" value="<?php echo e($filters['tax_regime'] ?? ''); ?>" class="form-control" placeholder="Tax regime">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100">Filtrer</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Entreprise</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>NINEA / RCCM</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><?php echo e((string)$c['id']); ?></td>
                        <td><?php echo e($c['company_name']); ?></td>
                        <td><?php echo e($c['contact_name']); ?></td>
                        <td><?php echo e($c['email']); ?></td>
                        <td><?php echo e($c['ninea']) . ' / ' . e($c['rccm']); ?></td>
                        <td><?php echo e($c['status']); ?></td>
                        <td class="text-end">
                            <a href="/MNS_CORPORATE/clients.php?action=show&id=<?php echo e((string)$c['id']); ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                            <a href="/MNS_CORPORATE/clients.php?action=edit&id=<?php echo e((string)$c['id']); ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                            <form method="post" action="/MNS_CORPORATE/clients.php?action=toggle" style="display:inline-block" onsubmit="return confirm('Confirmer le changement de statut ?');">
                                <input type="hidden" name="id" value="<?php echo e((string)$c['id']); ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><?php echo $c['status'] === 'ACTIF' ? 'Désactiver' : 'Réactiver'; ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div>Résultats: <?php echo e((string)$total); ?></div>
        <nav>
            <?php $pages = (int) ceil($total / $perPage); ?>
            <ul class="pagination mb-0">
                <?php for ($i=1;$i<=$pages;$i++): ?>
                    <li class="page-item <?php echo $i===$page ? 'active' : ''; ?>"><a class="page-link" href="/MNS_CORPORATE/clients.php?action=list&page=<?php echo $i; ?><?php echo $filters['q'] ? '&q=' . urlencode($filters['q']) : ''; ?><?php echo $filters['status'] ? '&status=' . urlencode($filters['status']) : ''; ?><?php echo $filters['tax_regime'] ? '&tax_regime=' . urlencode($filters['tax_regime']) : ''; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
