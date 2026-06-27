<?php
declare(strict_types=1);

if (!defined('MNS_CONTROLLER_RENDER')) {
    require_once __DIR__ . '/../../../backend/controllers/invoice_controller.php';
    if (($_GET['action'] ?? '') === 'status') {
        changeInvoiceStatus((int) ($_POST['id'] ?? 0));
    } else {
        showInvoice((int) ($_GET['id'] ?? 0));
    }
    return;
}

require_once __DIR__ . '/../../../backend/includes/helpers.php';
$user = currentUser();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Facture <?php echo e((string) $invoice['invoice_number']); ?></h1>
            <a class="btn btn-outline-primary" href="/MNS_CORPORATE/frontend/views/invoices/pdf.php?invoice_id=<?php echo e((string) $invoice['id']); ?>">Telecharger PDF</a>
        </div>

        <div class="bg-white border rounded p-3">
            <dl class="row">
                <?php foreach (['company_name' => 'Client', 'mission_title' => 'Mission', 'issue_date' => 'Emission', 'due_date' => 'Echeance', 'subtotal' => 'HT', 'tax_amount' => 'TVA', 'total_amount' => 'TTC', 'status' => 'Statut', 'creator_name' => 'Cree par', 'notes' => 'Notes'] as $key => $label): ?>
                    <dt class="col-sm-3"><?php echo e($label); ?></dt>
                    <dd class="col-sm-9"><?php echo e((string) ($invoice[$key] ?? '')); ?></dd>
                <?php endforeach; ?>
                <dt class="col-sm-3">Paye</dt>
                <dd class="col-sm-9"><?php echo e((string) $paid); ?></dd>
                <dt class="col-sm-3">Solde</dt>
                <dd class="col-sm-9"><?php echo e((string) $balance); ?></dd>
            </dl>

            <?php if (($user['role'] ?? '') === 'EXPERT'): ?>
                <a class="btn btn-primary" href="/MNS_CORPORATE/frontend/views/payments/create.php?invoice_id=<?php echo e((string) $invoice['id']); ?>">Enregistrer paiement</a>
                <?php if ($invoice['status'] === 'BROUILLON'): ?>
                    <form class="d-inline" method="post" action="/MNS_CORPORATE/frontend/views/invoices/show.php?action=status">
                        <input type="hidden" name="id" value="<?php echo e((string) $invoice['id']); ?>">
                        <button class="btn btn-outline-success" name="status" value="ENVOYEE">Envoyer</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <h2 class="h5 mt-4">Paiements</h2>
        <div class="bg-white border rounded p-3">
            <?php if ($payments === []): ?>
                <p class="text-muted mb-0">Aucun paiement enregistre.</p>
            <?php endif; ?>
            <?php foreach ($payments as $payment): ?>
                <div><?php echo e((string) $payment['payment_date']); ?> - <?php echo e((string) $payment['amount']); ?> - <?php echo e((string) $payment['payment_method']); ?></div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
