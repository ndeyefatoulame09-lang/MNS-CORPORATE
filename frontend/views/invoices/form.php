<?php
require_once __DIR__ . '/../../../backend/includes/helpers.php';
$old = $_SESSION['old_input'] ?? [];
if (!is_array($old)) {
    $old = [];
}
unset($_SESSION['old_input']);
function invv($k, $i = null, $d = '')
{
    global $old;
    return e((string) (array_key_exists($k, $old) ? $old[$k] : ($i[$k] ?? $d)));
}
?>
<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Numero *</label>
            <input class="form-control" name="invoice_number" required maxlength="50" value="<?php echo invv('invoice_number', $invoice ?? null); ?>">
            <div class="invalid-feedback">Le numero est obligatoire et doit etre unique.</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Client *</label>
            <select class="form-select" name="client_id" required>
                <option value="">Selectionner</option>
                <?php foreach (($clients ?? []) as $c): ?>
                    <option value="<?php echo e((string) $c['id']); ?>" <?php echo invv('client_id', $invoice ?? null) == $c['id'] ? 'selected' : ''; ?>><?php echo e($c['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selectionnez un client.</div>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">Mission</label>
            <select class="form-select" name="mission_id">
                <option value="">Aucune</option>
                <?php foreach (($missions ?? []) as $m): ?>
                    <option value="<?php echo e((string) $m['id']); ?>" <?php echo invv('mission_id', $invoice ?? null) == $m['id'] ? 'selected' : ''; ?>><?php echo e($m['title']); ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">La mission doit appartenir au client choisi.</div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Emission *</label>
            <input class="form-control" type="date" name="issue_date" required value="<?php echo invv('issue_date', $invoice ?? null); ?>">
            <div class="invalid-feedback">La date d emission est obligatoire.</div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Echeance *</label>
            <input class="form-control" type="date" name="due_date" required data-after="issue_date" value="<?php echo invv('due_date', $invoice ?? null); ?>">
            <div class="form-text">L echeance ne peut pas etre avant l emission.</div>
            <div class="invalid-feedback">Choisissez une date d echeance valide.</div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Montant HT *</label>
            <input class="form-control" type="number" step="0.01" min="0.01" name="subtotal" required value="<?php echo invv('subtotal', $invoice ?? null); ?>">
            <div class="invalid-feedback">Le montant HT doit etre superieur a zero.</div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">TVA %</label>
            <input class="form-control" type="number" step="0.01" min="0" name="tax_rate" value="<?php echo invv('tax_rate', $invoice ?? null, (string) TVA_STANDARD_RATE); ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Statut</label>
            <select class="form-select" name="status">
                <option value="BROUILLON" <?php echo invv('status', $invoice ?? null, 'BROUILLON') === 'BROUILLON' ? 'selected' : ''; ?>>BROUILLON</option>
                <option value="ENVOYEE" <?php echo invv('status', $invoice ?? null) === 'ENVOYEE' ? 'selected' : ''; ?>>ENVOYEE</option>
            </select>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Notes</label>
            <textarea class="form-control" name="notes" rows="3"><?php echo invv('notes', $invoice ?? null); ?></textarea>
        </div>
    </div>
</div>