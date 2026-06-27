<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/includes/helpers.php';

$oldInput = $_SESSION['old_input'] ?? [];
if (!is_array($oldInput)) {
    $oldInput = [];
}
unset($_SESSION['old_input']);

$timesheetData = isset($timesheet) && is_array($timesheet) ? $timesheet : [];
$missionOptions = isset($missions) && is_array($missions) ? $missions : [];

if (!function_exists('timesheetFormValue')) {
    function timesheetFormValue(string $key, array $oldInput = [], array $timesheetData = []): string
    {
        if (is_array($oldInput) && array_key_exists($key, $oldInput)) {
            return e((string) $oldInput[$key]);
        }

        if (is_array($timesheetData) && array_key_exists($key, $timesheetData)) {
            return e((string) $timesheetData[$key]);
        }

        return '';
    }
}

$selectedMissionId = timesheetFormValue('mission_id', $oldInput, $timesheetData);
?>
<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-12 col-md-6 mb-3">
            <label class="form-label">Mission *</label>
            <select class="form-select" name="mission_id" required>
                <option value="">Selectionner</option>
                <?php foreach ($missionOptions as $mission): ?>
                    <?php $missionId = (string) ($mission['id'] ?? ''); ?>
                    <option value="<?php echo e($missionId); ?>" <?php echo $selectedMissionId === $missionId ? 'selected' : ''; ?>>
                        <?php echo e((string) ($mission['title'] ?? '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-3 mb-3">
            <label class="form-label">Date *</label>
            <input class="form-control" type="date" name="work_date" required value="<?php echo timesheetFormValue('work_date', $oldInput, $timesheetData); ?>">
        </div>
        <div class="col-12 col-md-3 mb-3">
            <label class="form-label">Heures *</label>
            <input class="form-control" type="number" name="hours_worked" min="0.25" max="24" step="0.25" required value="<?php echo timesheetFormValue('hours_worked', $oldInput, $timesheetData); ?>">
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Description *</label>
            <textarea class="form-control" name="description" rows="4" required><?php echo timesheetFormValue('description', $oldInput, $timesheetData); ?></textarea>
        </div>
    </div>
</div>
