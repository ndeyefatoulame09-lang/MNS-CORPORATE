<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/includes/helpers.php';

$oldInput = $_SESSION['old_input'] ?? [];
if (!is_array($oldInput)) {
    $oldInput = [];
}
unset($_SESSION['old_input']);
$today = date('Y-m-d');
$isCreate = empty($deadline['id']);

function deadlineValue(string $key, ?array $deadline, string $default = ''): string
{
    global $oldInput;

    if (is_array($oldInput) && array_key_exists($key, $oldInput)) {
        return e((string) $oldInput[$key]);
    }

    if ($deadline !== null && array_key_exists($key, $deadline)) {
        return e((string) $deadline[$key]);
    }

    return e($default);
}
?>

<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Client *</label>
            <select class="form-select" name="client_id" required>
                <option value="">Selectionner un client</option>
                <?php foreach (($clients ?? []) as $client): ?>
                    <option value="<?php echo e((string) $client['id']); ?>" <?php echo deadlineValue('client_id', $deadline ?? null) === (string) $client['id'] ? 'selected' : ''; ?>>
                        <?php echo e($client['company_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Le client est obligatoire.</div>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Mission</label>
            <select class="form-select" name="mission_id">
                <option value="">Aucune mission liee</option>
                <?php foreach (($missions ?? []) as $mission): ?>
                    <option value="<?php echo e((string) $mission['id']); ?>" <?php echo deadlineValue('mission_id', $deadline ?? null) === (string) $mission['id'] ? 'selected' : ''; ?>>
                        <?php echo e($mission['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-8 mb-3">
            <label class="form-label">Titre *</label>
            <input class="form-control" name="title" required maxlength="180" value="<?php echo deadlineValue('title', $deadline ?? null); ?>">
            <div class="invalid-feedback">Le titre est obligatoire.</div>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Date d'echeance *</label>
            <input class="form-control" type="date" name="deadline_date" required <?php echo $isCreate ? 'min="' . e($today) . '" data-min-today="true"' : ''; ?> value="<?php echo deadlineValue('deadline_date', $deadline ?? null); ?>">
            <div class="form-text"><?php echo $isCreate ? 'Une nouvelle echeance ne peut pas etre creee dans le passe.' : 'Conservez une date coherente avec le suivi fiscal.'; ?></div>
            <div class="invalid-feedback">Choisissez une date d echeance valide.</div>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Statut</label>
            <?php $currentStatus = deadlineValue('status', $deadline ?? null, 'A_VENIR'); ?>
            <select class="form-select" name="status" required>
                <?php foreach (FiscalDeadline::STATUSES as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo $currentStatus === $status ? 'selected' : ''; ?>>
                        <?php echo e($status); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4"><?php echo deadlineValue('description', $deadline ?? null); ?></textarea>
        </div>
    </div>
</div>