<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

const CLIENT_LIST_URL = '/MNS_CORPORATE/frontend/views/clients/list.php';

function handleClientsRequest(): void
{
    startSecureSession();
    requireRole(['EXPERT']);

    $action = $_GET['action'] ?? 'list';

    if ($action === 'store') {
        storeClient();
        return;
    }

    if ($action === 'update') {
        updateClient((int) ($_POST['id'] ?? 0));
        return;
    }

    if ($action === 'toggle') {
        toggleClientStatus((int) ($_POST['id'] ?? 0));
        return;
    }

    if ($action === 'create') {
        showCreateClientForm();
        return;
    }

    if ($action === 'edit') {
        showEditClientForm((int) ($_GET['id'] ?? 0));
        return;
    }

    if ($action === 'show') {
        showClient((int) ($_GET['id'] ?? 0));
        return;
    }

    listClients();
}

function listClients(): void
{
    startSecureSession();
    requireRole(['EXPERT']);

    $model = new Client(getDatabaseConnection());
    $q = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? ''));
    $taxRegime = trim((string) ($_GET['tax_regime'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $filters = [];

    if ($q !== '') {
        $filters['q'] = $q;
    }
    if (in_array($status, ['ACTIF', 'INACTIF'], true)) {
        $filters['status'] = $status;
    }
    if ($taxRegime !== '') {
        $filters['tax_regime'] = $taxRegime;
    }

    $clients = $model->findAll($filters, $perPage, ($page - 1) * $perPage);
    $total = $model->countAll($filters);
    $taxRegimes = $model->getTaxRegimes();

    renderClientView('list.php', compact('clients', 'total', 'filters', 'page', 'perPage', 'taxRegimes'));
}

function showCreateClientForm(): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $client = null;

    renderClientView('create.php', compact('client'));
}

function storeClient(): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureClientPostOrRedirect(CLIENT_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new Client($pdo);
    $data = normalizeClientInput($_POST);
    $errors = validateClientData($model, $data);

    if ($errors !== []) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        $client = null;
        renderClientView('create.php', compact('client'));
        return;
    }

    $id = $model->create($data);
    logClientAudit($pdo, 'CREATION_CLIENT', 'Creation du client #' . $id . ' : ' . $data['company_name']);
    setFlashMessage('success', 'Client cree avec succes.');
    redirect(CLIENT_LIST_URL);
}

function showEditClientForm(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $client = findClientOrRedirect($id);

    renderClientView('edit.php', compact('client'));
}

function updateClient(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureClientPostOrRedirect(CLIENT_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new Client($pdo);
    $client = findClientOrRedirect($id);
    $data = normalizeClientInput($_POST);
    $errors = validateClientData($model, $data, $id);

    if ($errors !== []) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        renderClientView('edit.php', compact('client'));
        return;
    }

    $model->update($id, $data);
    logClientAudit($pdo, 'MODIFICATION_CLIENT', 'Modification du client #' . $id . ' : ' . $data['company_name']);
    setFlashMessage('success', 'Client modifie avec succes.');
    redirect(CLIENT_LIST_URL);
}

function showClient(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $client = findClientOrRedirect($id);

    renderClientView('show.php', compact('client'));
}

function toggleClientStatus(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureClientPostOrRedirect(CLIENT_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new Client($pdo);
    $client = findClientOrRedirect($id);
    $newStatus = $client['status'] === 'ACTIF' ? 'INACTIF' : 'ACTIF';
    $model->setStatus($id, $newStatus);

    logClientAudit(
        $pdo,
        $newStatus === 'ACTIF' ? 'REACTIVATION_CLIENT' : 'DESACTIVATION_CLIENT',
        'Changement du statut du client #' . $id . ' vers ' . $newStatus
    );

    setFlashMessage('success', 'Statut du client mis a jour.');
    redirect(CLIENT_LIST_URL);
}

function validateClientData(Client $model, array $data, int $id = 0): array
{
    $errors = [];

    if ($data['company_name'] === '') {
        $errors[] = 'Le nom de l entreprise est obligatoire.';
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L email est invalide.';
    }

    if ($data['ninea'] !== '' && $model->existsByNinea($data['ninea'], $id)) {
        $errors[] = 'Le NINEA est deja utilise.';
    }

    if ($data['rccm'] !== '' && $model->existsByRccm($data['rccm'], $id)) {
        $errors[] = 'Le RCCM est deja utilise.';
    }

    if ($data['accounting_year_start'] !== '' && $data['accounting_year_end'] !== '' && $data['accounting_year_end'] < $data['accounting_year_start']) {
        $errors[] = 'La date de fin d exercice doit etre superieure ou egale a la date de debut.';
    }

    if (!in_array($data['status'], ['ACTIF', 'INACTIF'], true)) {
        $errors[] = 'Le statut client est invalide.';
    }

    return $errors;
}

function normalizeClientInput(array $input): array
{
    $fields = [
        'company_name', 'legal_form', 'contact_name', 'email', 'phone', 'address',
        'ninea', 'rccm', 'tax_regime', 'accounting_year_start', 'accounting_year_end', 'status',
    ];
    $data = [];

    foreach ($fields as $field) {
        $data[$field] = trim((string) ($input[$field] ?? ''));
    }

    $data['status'] = $data['status'] === '' ? 'ACTIF' : $data['status'];

    return $data;
}

function findClientOrRedirect(int $id): array
{
    $client = (new Client(getDatabaseConnection()))->findById($id);

    if ($client === null) {
        setFlashMessage('error', 'Client introuvable.');
        redirect(CLIENT_LIST_URL);
    }

    return $client;
}

function renderClientView(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    if (!defined('MNS_CONTROLLER_RENDER')) {
        define('MNS_CONTROLLER_RENDER', true);
    }
    require __DIR__ . '/../../frontend/views/clients/' . $view;
}

function ensureClientPostOrRedirect(string $target): void
{
    if (!isPostRequest()) {
        setFlashMessage('error', 'Action non autorisee en GET.');
        redirect($target);
    }
}

function logClientAudit(PDO $pdo, string $action, string $description): void
{
    (new AuditLog($pdo))->log([
        'user_id' => currentUserId(),
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
