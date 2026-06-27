<?php
require_once __DIR__ . '/../../../backend/includes/helpers.php';

$oldInput = $_SESSION['old_input'] ?? [];
$oldInput = is_array($oldInput) ? $oldInput : [];
unset($_SESSION['old_input']);

$letterData = isset($letter) && is_array($letter) ? $letter : [];
$clientOptions = isset($clients) && is_array($clients) ? $clients : [];
$missionOptions = isset($missions) && is_array($missions) ? $missions : [];

function letterFieldValue(string $key, mixed $letter = null, string $default = ''): string
{
    global $oldInput, $content;

    if (is_array($oldInput) && array_key_exists($key, $oldInput)) {
        return e((string) $oldInput[$key]);
    }

    if ($key === 'content') {
        return e((string) ($content ?? $default));
    }

    if (is_array($letter) && array_key_exists($key, $letter)) {
        return e((string) $letter[$key]);
    }

    return e($default);
}
?>
<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Client *</label>
            <select class="form-select" name="client_id" required>
                <option value="">Selectionner</option>
                <?php foreach ($clientOptions as $client): ?>
                    <option value="<?php echo e((string) ($client['id'] ?? '')); ?>" <?php echo letterFieldValue('client_id', $letterData) === (string) ($client['id'] ?? '') ? 'selected' : ''; ?>>
                        <?php echo e($client['company_name'] ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Mission</label>
            <select class="form-select" name="mission_id">
                <option value="">Aucune</option>
                <?php foreach ($missionOptions as $mission): ?>
                    <option value="<?php echo e((string) ($mission['id'] ?? '')); ?>" <?php echo letterFieldValue('mission_id', $letterData) === (string) ($mission['id'] ?? '') ? 'selected' : ''; ?>>
                        <?php echo e($mission['title'] ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Titre *</label>
            <input class="form-control" name="title" required maxlength="180" value="<?php echo letterFieldValue('title', $letterData); ?>">
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Texte de la lettre *</label>
            <textarea class="form-control" name="content" rows="12" required><?php echo letterFieldValue('content', $letterData); ?></textarea>
            <div class="form-text">Le contenu est enregistre dans un fichier texte lie a la lettre, car la table SQL ne contient pas de colonne contenu.</div>
        </div>
    </div>
</div>
