<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/document_controller.php';
    if (($_GET['action'] ?? '') === 'comment') {
        addDocumentComment((int) ($_POST['document_id'] ?? 0));
    } else {
        showDocument((int) ($_GET['id'] ?? 0));
    }
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';

$user = currentUser();
$role = (string) ($user['role'] ?? '');
$flash = getFlashMessage();
$isArchived = (int) ($document['is_archived'] ?? 0) === 1;
$canEdit = $role === 'EXPERT' || ($role === 'CLIENT' && !$isArchived && $document['status'] !== 'VALIDE');
$canReplace = $role === 'EXPERT' || ($role === 'CLIENT' && !$isArchived && in_array($document['status'], ['NOUVEAU', 'CONSULTE', 'REJETE'], true));
$canClientDelete = $role === 'CLIENT' && !$isArchived && $document['status'] === 'NOUVEAU';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container py-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0"><?php echo e($document['title']); ?></h1>
                <?php if ($isArchived): ?><span class="badge text-bg-warning mt-2">Archive</span><?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <?php if ($canEdit && (!$isArchived || $role === 'EXPERT')): ?>
                    <a class="btn btn-outline-secondary" href="/MNS_CORPORATE/frontend/views/documents/list.php?action=edit&id=<?php echo e((string) $document['id']); ?>">Modifier</a>
                <?php endif; ?>
                <a class="btn btn-outline-dark" href="/MNS_CORPORATE/frontend/views/documents/list.php?action=download&id=<?php echo e((string) $document['id']); ?>">Telecharger</a>
            </div>
        </div>

        <div class="bg-white border rounded p-3">
            <dl class="row mb-0">
                <?php foreach (['company_name'=>'Client','mission_title'=>'Mission','original_filename'=>'Fichier','document_category'=>'Categorie','status'=>'Statut','uploader_name'=>'Ajoute par','uploaded_at'=>'Date upload','archived_at'=>'Date archivage','archive_reason'=>'Motif archivage'] as $k => $l): ?>
                    <?php if (!$isArchived && in_array($k, ['archived_at', 'archive_reason'], true)) { continue; } ?>
                    <dt class="col-sm-3 text-muted"><?php echo e($l); ?></dt>
                    <dd class="col-sm-9"><?php echo e((string) ($document[$k] ?? '')); ?></dd>
                <?php endforeach; ?>
            </dl>

            <?php if ($role === 'EXPERT' && !$isArchived): ?>
                <form method="post" action="/MNS_CORPORATE/frontend/views/documents/list.php?action=status" class="mt-3 d-flex gap-2">
                    <input type="hidden" name="id" value="<?php echo e((string) $document['id']); ?>">
                    <button class="btn btn-success" name="status" value="VALIDE">Valider</button>
                    <button class="btn btn-danger" name="status" value="REJETE">Rejeter</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($canReplace && !$isArchived): ?>
            <div class="bg-white border rounded p-3 mt-3">
                <h2 class="h5">Remplacer le fichier</h2>
                <form method="post" enctype="multipart/form-data" action="/MNS_CORPORATE/frontend/views/documents/list.php?action=replace">
                    <input type="hidden" name="id" value="<?php echo e((string) $document['id']); ?>">
                    <div class="mb-3">
                        <label class="form-label">Nouveau fichier PDF/JPG/PNG</label>
                        <input class="form-control" type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <button class="btn btn-outline-primary">Remplacer le fichier</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($role === 'EXPERT'): ?>
            <div class="bg-white border rounded p-3 mt-3">
                <?php if ($isArchived): ?>
                    <form method="post" action="/MNS_CORPORATE/frontend/views/documents/list.php?action=restore">
                        <input type="hidden" name="id" value="<?php echo e((string) $document['id']); ?>">
                        <button class="btn btn-warning">Restaurer</button>
                    </form>
                <?php else: ?>
                    <h2 class="h5">Archiver</h2>
                    <form method="post" action="/MNS_CORPORATE/frontend/views/documents/list.php?action=archive">
                        <input type="hidden" name="id" value="<?php echo e((string) $document['id']); ?>">
                        <div class="mb-3">
                            <label class="form-label">Motif d archivage *</label>
                            <textarea class="form-control" name="archive_reason" rows="3" required></textarea>
                        </div>
                        <button class="btn btn-outline-danger">Archiver</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php elseif ($canClientDelete): ?>
            <form method="post" action="/MNS_CORPORATE/frontend/views/documents/list.php?action=archive" class="mt-3" onsubmit="return confirm('Confirmer la suppression logique ?');">
                <input type="hidden" name="id" value="<?php echo e((string) $document['id']); ?>">
                <button class="btn btn-outline-danger">Supprimer</button>
            </form>
        <?php endif; ?>

        <h2 class="h5 mt-4">Commentaires</h2>
        <div class="bg-white border rounded p-3">
            <?php foreach (($comments ?? []) as $c): ?>
                <div class="border-bottom py-2">
                    <strong><?php echo e($c['full_name']); ?></strong>
                    <div><?php echo e($c['message']); ?></div>
                    <small class="text-muted"><?php echo e($c['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
            <?php if (($comments ?? []) === []): ?><div class="text-muted">Aucun commentaire.</div><?php endif; ?>
        </div>
        <?php if (!$isArchived): ?>
            <?php require __DIR__ . '/comment_form.php'; ?>
        <?php endif; ?>
        <a class="btn btn-link mt-3" href="/MNS_CORPORATE/frontend/views/documents/list.php">Retour</a>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
