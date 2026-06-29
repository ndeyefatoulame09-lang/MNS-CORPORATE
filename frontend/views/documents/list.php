<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/document_controller.php';
    handleDocumentRequest();
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';

$user = currentUser();
$role = (string) ($user['role'] ?? '');
$flash = getFlashMessage();
$pages = max(1, (int) ceil(($total ?? 0) / ($perPage ?? 20)));
$queryBase = $filters ?? [];
$showArchived = (($filters['show_archived'] ?? '') === '1');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container-fluid py-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-1">Documents securises</h1>
                <div class="text-muted"><?php echo e((string) ($total ?? 0)); ?> resultat(s)</div>
            </div>
            <?php if (in_array($role, ['EXPERT', 'CLIENT'], true)): ?>
                <a class="btn btn-primary" href="/MNS_CORPORATE/frontend/views/documents/upload.php">Uploader</a>
            <?php endif; ?>
        </div>

        <form class="row g-2 mb-3" method="get">
            <div class="col-md-4">
                <input class="form-control" name="q" value="<?php echo e($filters['q'] ?? ''); ?>" placeholder="Titre ou fichier">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">Tous statuts</option>
                    <?php foreach (Document::STATUSES as $status): ?>
                        <option value="<?php echo e($status); ?>" <?php echo (($filters['status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="document_category">
                    <option value="">Toutes categories</option>
                    <?php foreach (Document::CATEGORIES as $category): ?>
                        <option value="<?php echo e($category); ?>" <?php echo (($filters['document_category'] ?? '') === $category) ? 'selected' : ''; ?>><?php echo e($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($role === 'EXPERT'): ?>
                <div class="col-md-2 form-check d-flex align-items-center gap-2 ps-4">
                    <input class="form-check-input" type="checkbox" name="show_archived" value="1" id="showArchived" <?php echo $showArchived ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="showArchived">Afficher les archives</label>
                </div>
            <?php endif; ?>
            <div class="col-md-1">
                <button class="btn btn-outline-secondary">Filtrer</button>
            </div>
        </form>

        <div class="table-responsive bg-white border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Titre</th>
                        <th>Client</th>
                        <th>Mission</th>
                        <th>Categorie</th>
                        <th>Statut</th>
                        <th>Fichier</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($documents ?? []) as $document): ?>
                        <?php
                        $isArchived = (int) ($document['is_archived'] ?? 0) === 1;
                        $canEdit = $role === 'EXPERT' || ($role === 'CLIENT' && !$isArchived && $document['status'] !== 'VALIDE');
                        $canClientDelete = $role === 'CLIENT' && !$isArchived && $document['status'] === 'NOUVEAU';
                        ?>
                        <tr>
                            <td><?php echo e($document['title']); ?></td>
                            <td><?php echo e($document['company_name']); ?></td>
                            <td><?php echo e($document['mission_title']); ?></td>
                            <td><?php echo e($document['document_category']); ?></td>
                            <td>
                                <span class="badge text-bg-secondary"><?php echo e($document['status']); ?></span>
                                <?php if ($isArchived): ?><span class="badge text-bg-warning ms-1">Archive</span><?php endif; ?>
                            </td>
                            <td><?php echo e($document['original_filename']); ?></td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/MNS_CORPORATE/frontend/views/documents/show.php?id=<?php echo e((string) $document['id']); ?>">Voir</a>
                                <a class="btn btn-sm btn-outline-dark" href="/MNS_CORPORATE/frontend/views/documents/list.php?action=download&id=<?php echo e((string) $document['id']); ?>">Telecharger</a>
                                <?php if ($canEdit && (!$isArchived || $role === 'EXPERT')): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="/MNS_CORPORATE/frontend/views/documents/list.php?action=edit&id=<?php echo e((string) $document['id']); ?>">Modifier</a>
                                <?php endif; ?>
                                <?php if ($canClientDelete): ?>
                                    <form method="post" action="/MNS_CORPORATE/frontend/views/documents/list.php?action=archive" class="d-inline" onsubmit="return confirm('Confirmer la suppression logique ?');">
                                        <input type="hidden" name="id" value="<?php echo e((string) $document['id']); ?>">
                                        <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (($documents ?? []) === []): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun document.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <nav class="mt-3">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $pages; $i++): $queryBase['page'] = $i; ?>
                    <li class="page-item <?php echo $i === ($page ?? 1) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo e(http_build_query($queryBase)); ?>"><?php echo e((string) $i); ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
