<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/includes/helpers.php';

$oldInput = $_SESSION['old_input'] ?? [];
if (!is_array($oldInput)) {
    $oldInput = [];
}
unset($_SESSION['old_input']);
$today = date('Y-m-d');
$isCreate = empty($mission['id']);

function missionFormValue(string $name, ?array $mission): string
{
    global $oldInput;
    return array_key_exists($name, $oldInput) ? e((string) $oldInput[$name]) : e((string) ($mission[$name] ?? ''));
}
?>
<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Client *</label>
            <select class="form-select" name="client_id" required>
                <option value="">Selectionner</option>
                <?php foreach (($clients ?? []) as $client): ?>
                    <option value="<?php echo e((string) $client['id']); ?>" <?php echo missionFormValue('client_id', $mission ?? null) == $client['id'] ? 'selected' : ''; ?>><?php echo e($client['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selectionnez un client actif.</div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Type de mission *</label>
            <select class="form-select" name="mission_catalog_id" required>
                <option value="">Selectionner</option>
                <?php foreach (($catalogItems ?? []) as $item): ?>
                    <option value="<?php echo e((string) $item['id']); ?>" <?php echo missionFormValue('mission_catalog_id', $mission ?? null) == $item['id'] ? 'selected' : ''; ?>><?php echo e($item['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selectionnez un type de mission actif.</div>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Titre *</label>
            <input class="form-control" name="title" required maxlength="180" value="<?php echo missionFormValue('title', $mission ?? null); ?>">
            <div class="invalid-feedback">Le titre de la mission est obligatoire.</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Date debut *</label>
            <input class="form-control" type="date" name="start_date" required <?php echo $isCreate ? 'min="' . e($today) . '" data-min-today="true"' : ''; ?> value="<?php echo missionFormValue('start_date', $mission ?? null); ?>">
            <div class="form-text"><?php echo $isCreate ? 'Pour une nouvelle mission, choisissez aujourd hui ou une date future.' : 'La date de debut sert de reference aux dates de fin.'; ?></div>
            <div class="invalid-feedback">Choisissez une date de debut valide.</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Fin prevue</label>
            <input class="form-control" type="date" name="planned_end_date" data-after="start_date" value="<?php echo missionFormValue('planned_end_date', $mission ?? null); ?>">
            <div class="form-text">La fin prevue ne peut pas etre avant le debut.</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Fin reelle</label>
            <input class="form-control" type="date" name="actual_end_date" data-after="start_date" value="<?php echo missionFormValue('actual_end_date', $mission ?? null); ?>">
            <div class="form-text">A renseigner lorsque la mission est terminee.</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Statut</label>
            <select class="form-select" name="status" required>
                <?php $currentStatus = missionFormValue('status', $mission ?? ['status' => 'A_FAIRE']); foreach (Mission::STATUSES as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo $currentStatus === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Priorite</label>
            <select class="form-select" name="priority" required>
                <?php $currentPriority = missionFormValue('priority', $mission ?? ['priority' => 'MOYENNE']); foreach (Mission::PRIORITIES as $priority): ?>
                    <option value="<?php echo e($priority); ?>" <?php echo $currentPriority === $priority ? 'selected' : ''; ?>><?php echo e($priority); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Heures estimees</label>
            <input class="form-control" type="number" min="0.25" step="0.25" name="estimated_hours" value="<?php echo missionFormValue('estimated_hours', $mission ?? null); ?>">
            <div class="form-text">Indiquez un nombre positif, par palier de 15 minutes.</div>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4"><?php echo missionFormValue('description', $mission ?? null); ?></textarea>
        </div>
    </div>
</div>