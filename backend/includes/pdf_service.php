<?php
declare(strict_types=1);

function renderPrintableInvoice(array $invoice, array $payments, float $paid, float $balance): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: inline; filename="facture_' . preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $invoice['invoice_number']) . '.html"');

    $esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $money = static fn($value): string => number_format((float) $value, 0, ',', ' ') . ' FCFA';
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture <?php echo $esc($invoice['invoice_number']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 40px; }
        .top { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #111827; padding-bottom: 18px; }
        h1 { margin: 0; font-size: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { border: 1px solid #d1d5db; padding: 10px; text-align: left; }
        th { background: #f3f4f6; }
        .totals { width: 360px; margin-left: auto; }
        .muted { color: #6b7280; }
        .print { margin-top: 24px; }
        @media print { .print { display: none; } body { margin: 18mm; } }
    </style>
</head>
<body>
    <div class="top">
        <div>
            <h1>MNS CORPORATE</h1>
            <p class="muted">Facture imprimable - sauvegarde PDF via le navigateur</p>
        </div>
        <div>
            <strong>Facture <?php echo $esc($invoice['invoice_number']); ?></strong><br>
            Statut : <?php echo $esc($invoice['status']); ?>
        </div>
    </div>

    <table>
        <tbody>
            <tr><th>Client</th><td><?php echo $esc($invoice['company_name']); ?></td></tr>
            <tr><th>Mission</th><td><?php echo $esc($invoice['mission_title'] ?? ''); ?></td></tr>
            <tr><th>Date emission</th><td><?php echo $esc($invoice['issue_date']); ?></td></tr>
            <tr><th>Date echeance</th><td><?php echo $esc($invoice['due_date']); ?></td></tr>
        </tbody>
    </table>

    <table class="totals">
        <tbody>
            <tr><th>Montant HT</th><td><?php echo $esc($money($invoice['subtotal'])); ?></td></tr>
            <tr><th>TVA</th><td><?php echo $esc($money($invoice['tax_amount'])); ?></td></tr>
            <tr><th>Montant TTC</th><td><?php echo $esc($money($invoice['total_amount'])); ?></td></tr>
            <tr><th>Paiements</th><td><?php echo $esc($money($paid)); ?></td></tr>
            <tr><th>Solde restant</th><td><?php echo $esc($money($balance)); ?></td></tr>
        </tbody>
    </table>

    <h2>Paiements enregistres</h2>
    <table>
        <thead><tr><th>Date</th><th>Montant</th><th>Moyen</th><th>Reference</th></tr></thead>
        <tbody>
        <?php if ($payments === []): ?>
            <tr><td colspan="4">Aucun paiement enregistre.</td></tr>
        <?php endif; ?>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?php echo $esc($payment['payment_date']); ?></td>
                <td><?php echo $esc($money($payment['amount'])); ?></td>
                <td><?php echo $esc($payment['payment_method']); ?></td>
                <td><?php echo $esc($payment['reference_number'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p>Merci pour votre confiance.</p>
    <button class="print" onclick="window.print()">Imprimer ou enregistrer en PDF</button>
</body>
</html>
    <?php
}
