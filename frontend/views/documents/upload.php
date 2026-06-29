<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/document_controller.php';
    if (($_GET['action'] ?? '') === 'store') {
        storeDocument();
    } else {
        showDocumentUpload();
    }
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
require_once __DIR__ . '/../../../backend/includes/role_check.php';
requireAuth();

$flash = getFlashMessage();
$isEdit = ($mode ?? '') === 'edit';
$formAction = $isEdit
    ? '/MNS_CORPORATE/frontend/views/documents/list.php?action=update'
    : '/MNS_CORPORATE/frontend/views/documents/upload.php?action=store';
$title = $isEdit ? 'Modifier le document' : 'Uploader une piece justificative';
$selectedMissionId = (string) ($document['mission_id'] ?? '');
$selectedCategory = (string) ($document['document_category'] ?? 'AUTRE');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container py-4">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type'] === 'success' ? 'success' : 'danger'); ?>"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>

        <h1 class="h4 mb-3"><?php echo e($title); ?></h1>
        <form method="post" enctype="multipart/form-data" action="<?php echo e($formAction); ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?php echo e((string) $document['id']); ?>"><?php endif; ?>
            <div class="bg-white border rounded p-3">
                <div class="mb-3">
                    <label class="form-label">Titre *</label>
                    <input class="form-control" name="title" required maxlength="180" value="<?php echo e((string) ($document['title'] ?? '')); ?>">
                </div>
                <div class="row">
                    <?php if (!$isEdit): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client *</label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Selectionner</option>
                                <?php foreach (($clients ?? []) as $c): ?>
                                    <option value="<?php echo e((string) $c['id']); ?>"><?php echo e($c['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client</label>
                            <input class="form-control" value="<?php echo e((string) ($document['company_name'] ?? '')); ?>" disabled>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Mission</label>
                        <select class="form-select" name="mission_id">
                            <option value="">Aucune</option>
                            <?php foreach (($missions ?? []) as $m): ?>
                                <?php if ($isEdit && (int) $m['client_id'] !== (int) $document['client_id']) { continue; } ?>
                                <option value="<?php echo e((string) $m['id']); ?>" <?php echo $selectedMissionId === (string) $m['id'] ? 'selected' : ''; ?>><?php echo e($m['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Categorie</label>
                    <select class="form-select" name="document_category">
                        <?php foreach (Document::CATEGORIES as $c): ?>
                            <option value="<?php echo e($c); ?>" <?php echo $selectedCategory === $c ? 'selected' : ''; ?>><?php echo e($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!$isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label">Fichier PDF/JPG/PNG *</label>
                        <input class="form-control" type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary"><?php echo $isEdit ? 'Enregistrer' : 'Uploader'; ?></button>
                <a class="btn btn-link" href="/MNS_CORPORATE/frontend/views/documents/list.php">Annuler</a>
            </div>
        </form>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
