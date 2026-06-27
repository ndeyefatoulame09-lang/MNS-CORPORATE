<?php require_once __DIR__ . '/../../../backend/includes/helpers.php'; $old = $_SESSION['old_input'] ?? []; if (!is_array($old)) { $old = []; } unset($_SESSION['old_input']); function timesheetValue(string $key, ?array $timesheet = null): string { global $old; return e((string) (array_key_exists($key, $old) ? $old[$key] : ($timesheet[$key] ?? ''))); } ?>
<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Mission *</label><select class="form-select" name="mission_id" required><option value="">Selectionner</option><?php foreach (($missions ?? []) as $mission): ?><option value="<?php echo e((string) $mission['id']); ?>" <?php echo timesheetValue('mission_id', $timesheet ?? null) === (string) $mission['id'] ? 'selected' : ''; ?>><?php echo e($mission['title']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3 mb-3"><label class="form-label">Date *</label><input class="form-control" type="date" name="work_date" required value="<?php echo timesheetValue('work_date', $timesheet ?? null); ?>"></div>
        <div class="col-md-3 mb-3"><label class="form-label">Heures *</label><input class="form-control" type="number" name="hours_worked" min="0.25" max="24" step="0.25" required value="<?php echo timesheetValue('hours_worked', $timesheet ?? null); ?>"></div>
        <div class="col-12 mb-3"><label class="form-label">Description *</label><textarea class="form-control" name="description" rows="4" required><?php echo timesheetValue('description', $timesheet ?? null); ?></textarea></div>
    </div>
</div>
