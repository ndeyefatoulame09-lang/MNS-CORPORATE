<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../backend/includes/helpers.php';
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
function missionFormValue(string $name, ?array $mission): string
{
    global $oldInput;
    return array_key_exists($name, $oldInput) ? e((string) $oldInput[$name]) : e((string) ($mission[$name] ?? ''));
}
?>
<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Client *</label><select class="form-select" name="client_id" required><option value="">Selectionner</option><?php foreach (($clients ?? []) as $client): ?><option value="<?php echo e((string) $client['id']); ?>" <?php echo missionFormValue('client_id', $mission ?? null) == $client['id'] ? 'selected' : ''; ?>><?php echo e($client['company_name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 mb-3"><label class="form-label">Type de mission *</label><select class="form-select" name="mission_catalog_id" required><option value="">Selectionner</option><?php foreach (($catalogItems ?? []) as $item): ?><option value="<?php echo e((string) $item['id']); ?>" <?php echo missionFormValue('mission_catalog_id', $mission ?? null) == $item['id'] ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-12 mb-3"><label class="form-label">Titre *</label><input class="form-control" name="title" required maxlength="180" value="<?php echo missionFormValue('title', $mission ?? null); ?>"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Date debut *</label><input class="form-control" type="date" name="start_date" required value="<?php echo missionFormValue('start_date', $mission ?? null); ?>"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Fin prevue</label><input class="form-control" type="date" name="planned_end_date" value="<?php echo missionFormValue('planned_end_date', $mission ?? null); ?>"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Fin reelle</label><input class="form-control" type="date" name="actual_end_date" value="<?php echo missionFormValue('actual_end_date', $mission ?? null); ?>"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Statut</label><select class="form-select" name="status"><?php $currentStatus = missionFormValue('status', $mission ?? ['status' => 'A_FAIRE']); foreach (Mission::STATUSES as $status): ?><option value="<?php echo e($status); ?>" <?php echo $currentStatus === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 mb-3"><label class="form-label">Priorite</label><select class="form-select" name="priority"><?php $currentPriority = missionFormValue('priority', $mission ?? ['priority' => 'MOYENNE']); foreach (Mission::PRIORITIES as $priority): ?><option value="<?php echo e($priority); ?>" <?php echo $currentPriority === $priority ? 'selected' : ''; ?>><?php echo e($priority); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4 mb-3"><label class="form-label">Heures estimees</label><input class="form-control" type="number" min="0" step="0.25" name="estimated_hours" value="<?php echo missionFormValue('estimated_hours', $mission ?? null); ?>"></div>
        <div class="col-12 mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?php echo missionFormValue('description', $mission ?? null); ?></textarea></div>
    </div>
</div>
