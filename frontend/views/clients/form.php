<?php
declare(strict_types=1);
if (!function_exists('e')) {
    require_once __DIR__ . '/../../../backend/includes/helpers.php';
}

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

function fieldValue($name, $client) {
    global $old;
    if (isset($old[$name])) return e((string)$old[$name]);
    if ($client !== null && isset($client[$name])) return e((string)$client[$name]);
    return '';
}

?>

<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Nom de l'entreprise *</label>
            <input name="company_name" type="text" class="form-control" required value="<?php echo fieldValue('company_name',$client ?? null); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Forme juridique</label>
            <input name="legal_form" type="text" class="form-control" value="<?php echo fieldValue('legal_form',$client ?? null); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Nom du contact</label>
            <input name="contact_name" type="text" class="form-control" value="<?php echo fieldValue('contact_name',$client ?? null); ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input name="email" type="email" class="form-control" value="<?php echo fieldValue('email',$client ?? null); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Téléphone</label>
                <input name="phone" type="text" class="form-control" value="<?php echo fieldValue('phone',$client ?? null); ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Adresse</label>
            <textarea name="address" class="form-control"><?php echo fieldValue('address',$client ?? null); ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">NINEA</label>
                <input name="ninea" type="text" class="form-control" value="<?php echo fieldValue('ninea',$client ?? null); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">RCCM</label>
                <input name="rccm" type="text" class="form-control" value="<?php echo fieldValue('rccm',$client ?? null); ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Régime fiscal</label>
                <input name="tax_regime" type="text" class="form-control" value="<?php echo fieldValue('tax_regime',$client ?? null); ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Début exercice</label>
                <input name="accounting_year_start" type="date" class="form-control" value="<?php echo fieldValue('accounting_year_start',$client ?? null); ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Fin exercice</label>
                <input name="accounting_year_end" type="date" class="form-control" value="<?php echo fieldValue('accounting_year_end',$client ?? null); ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="status" class="form-select">
                <option value="ACTIF" <?php echo (fieldValue('status',$client ?? null) === 'ACTIF') ? 'selected' : ''; ?>>ACTIF</option>
                <option value="INACTIF" <?php echo (fieldValue('status',$client ?? null) === 'INACTIF') ? 'selected' : ''; ?>>INACTIF</option>
            </select>
        </div>
    </div>
</div>
