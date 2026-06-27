<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../backend/includes/helpers.php';

$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

function clientFormValue(string $name, ?array $client): string
{
    global $oldInput;
    if (array_key_exists($name, $oldInput)) {
        return e((string) $oldInput[$name]);
    }

    return e((string) ($client[$name] ?? ''));
}
?>
<div class="bg-white border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Nom entreprise *</label><input class="form-control" name="company_name" required maxlength="180" value="<?php echo clientFormValue('company_name', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Forme juridique</label><input class="form-control" name="legal_form" maxlength="100" value="<?php echo clientFormValue('legal_form', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Contact</label><input class="form-control" name="contact_name" maxlength="150" value="<?php echo clientFormValue('contact_name', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="150" value="<?php echo clientFormValue('email', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Telephone</label><input class="form-control" name="phone" maxlength="30" value="<?php echo clientFormValue('phone', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Regime fiscal</label><input class="form-control" name="tax_regime" maxlength="100" value="<?php echo clientFormValue('tax_regime', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">NINEA</label><input class="form-control" name="ninea" maxlength="50" value="<?php echo clientFormValue('ninea', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">RCCM</label><input class="form-control" name="rccm" maxlength="80" value="<?php echo clientFormValue('rccm', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Debut exercice</label><input class="form-control" type="date" name="accounting_year_start" value="<?php echo clientFormValue('accounting_year_start', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3"><label class="form-label">Fin exercice</label><input class="form-control" type="date" name="accounting_year_end" value="<?php echo clientFormValue('accounting_year_end', $client ?? null); ?>"></div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Statut</label>
            <?php $status = clientFormValue('status', $client ?? ['status' => 'ACTIF']); ?>
            <select class="form-select" name="status" required>
                <option value="ACTIF" <?php echo $status === 'ACTIF' ? 'selected' : ''; ?>>ACTIF</option>
                <option value="INACTIF" <?php echo $status === 'INACTIF' ? 'selected' : ''; ?>>INACTIF</option>
            </select>
        </div>
        <div class="col-12 mb-3"><label class="form-label">Adresse</label><textarea class="form-control" name="address" maxlength="255" rows="3"><?php echo clientFormValue('address', $client ?? null); ?></textarea></div>
    </div>
</div>
