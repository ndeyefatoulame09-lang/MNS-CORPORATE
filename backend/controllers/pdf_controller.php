<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/pdf_service.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/AuditLog.php';

function handlePdfRequest(): void
{
    startSecureSession();
    requireAuth();

    $id = (int) ($_GET['invoice_id'] ?? 0);
    if ($id <= 0) {
        redirect('/MNS_CORPORATE/frontend/views/invoices/list.php');
    }

    generateInvoicePdfFallback($id);
}

function generateInvoicePdfFallback(int $invoiceId): void
{
    $pdo = getDatabaseConnection();
    $invoiceModel = new Invoice($pdo);
    $invoice = $invoiceModel->findById($invoiceId);
    if ($invoice === null || !pdfCanAccessInvoice($pdo, $invoice)) {
        redirect('/MNS_CORPORATE/frontend/views/invoices/list.php');
    }

    $payments = (new Payment($pdo))->findByInvoice($invoiceId);
    $paid = $invoiceModel->getPaymentsTotal($invoiceId);
    $balance = max(0, (float) $invoice['total_amount'] - $paid);

    (new AuditLog($pdo))->log([
        'user_id' => currentUserId(),
        'action' => 'GENERATION_FACTURE_PDF',
        'description' => 'Generation facture imprimable #' . $invoiceId,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);

    renderPrintableInvoice($invoice, $payments, $paid, $balance);
    exit;
}

function pdfCanAccessInvoice(PDO $pdo, array $invoice): bool
{
    $user = currentUser();
    $role = $user['role'] ?? '';
    if ($role === 'EXPERT') {
        return true;
    }
    if ($role !== 'CLIENT') {
        return false;
    }
    $stmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = :user_id AND id = :client_id');
    $stmt->execute(['user_id' => $user['id'], 'client_id' => $invoice['client_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}
