<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/includes/helpers.php';
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
function catalogFormValue(string $name, ?array $catalogItem): string
{
    global $oldInput;
    return array_key_exists($name, $oldInput) ? e((string) $oldInput[$name]) : e((string) ($catalogItem[$name] ?? ''));
}
?>
<div class="bg-white border rounded p-3">
    <div class="mb-3"><label class="form-label">Nom *</label><input class="form-control" name="name" required maxlength="150" value="<?php echo catalogFormValue('name', $catalogItem ?? null); ?>"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?php echo catalogFormValue('description', $catalogItem ?? null); ?></textarea></div>
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Duree par defaut en jours</label><input class="form-control" type="number" min="1" step="1" name="default_duration_days" value="<?php echo catalogFormValue('default_duration_days', $catalogItem ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Statut</label><?php $active = catalogFormValue('is_active', $catalogItem ?? ['is_active' => '1']); ?><select class="form-select" name="is_active"><option value="1" <?php echo $active !== '0' ? 'selected' : ''; ?>>Actif</option><option value="0" <?php echo $active === '0' ? 'selected' : ''; ?>>Inactif</option></select></div>
    </div>
</div>
