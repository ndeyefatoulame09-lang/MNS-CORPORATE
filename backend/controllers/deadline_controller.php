<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/FiscalDeadline.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/role_check.php';

const DEADLINE_LIST_URL = '/MNS_CORPORATE/frontend/views/deadlines/list.php';

function handleDeadlineRequest(): void
{
    startSecureSession();
    requireAuth();
    $action = $_GET['action'] ?? 'list';
    if ($action === 'store') { storeDeadline(); }
    elseif ($action === 'update') { updateDeadline((int) ($_POST['id'] ?? 0)); }
    elseif ($action === 'complete') { completeDeadline((int) ($_POST['id'] ?? 0)); }
    elseif ($action === 'generate') { generateDeadlines(); }
    elseif ($action === 'create') { showDeadlineCreate(); }
    elseif ($action === 'edit') { showDeadlineEdit((int) ($_GET['id'] ?? 0)); }
    elseif ($action === 'show') { showDeadline((int) ($_GET['id'] ?? 0)); }
    elseif ($action === 'calendar') { listDeadlines('calendar.php'); }
    else { listDeadlines(); }
}

function listDeadlines(string $view = 'list.php'): void
{
    $pdo = getDatabaseConnection();
    $model = new FiscalDeadline($pdo);
    $model->markAsOverdue();
    $filters = deadlineFilters();
    $user = currentUser();
    if (($user['role'] ?? '') === 'CLIENT') {
        $client = fetchClientForUser($pdo, (int) $user['id']);
        $filters['visible_client_id'] = $client['id'] ?? 0;
    } elseif (in_array(($user['role'] ?? ''), ['COLLABORATEUR', 'STAGIAIRE'], true)) {
        $filters['assigned_user_id'] = (int) $user['id'];
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $deadlines = $model->findAll($filters, $perPage, ($page - 1) * $perPage);
    $total = $model->countAll($filters);
    $clients = fetchClients($pdo);
    $missions = fetchMissions($pdo);
    renderDeadlineView($view, compact('deadlines', 'total', 'filters', 'page', 'perPage', 'clients', 'missions'));
}

function showDeadlineCreate(): void
{
    requireRole(['EXPERT']);
    $deadline = null;
    $pdo = getDatabaseConnection();
    $clients = fetchClients($pdo);
    $missions = fetchMissions($pdo);
    renderDeadlineView('create.php', compact('deadline', 'clients', 'missions'));
}

function showDeadlineEdit(int $id): void
{
    requireRole(['EXPERT']);
    $deadline = findDeadlineOrRedirect($id);
    $pdo = getDatabaseConnection();
    $clients = fetchClients($pdo);
    $missions = fetchMissions($pdo);
    renderDeadlineView('edit.php', compact('deadline', 'clients', 'missions'));
}

function showDeadline(int $id): void
{
    $deadline = findDeadlineOrRedirect($id);
    if (!deadlineCanAccess($deadline)) {
        setFlashMessage('error', 'Echeance inaccessible.');
        redirect(DEADLINE_LIST_URL);
    }
    renderDeadlineView('show.php', compact('deadline'));
}

function storeDeadline(): void
{
    requireRole(['EXPERT']);
    deadlinePostOnly();
    $pdo = getDatabaseConnection();
    $data = deadlineInput($_POST);
    $errors = validateDeadline($data);
    if ($errors) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        showDeadlineCreate();
        return;
    }
    $id = (new FiscalDeadline($pdo))->create($data);
    deadlineAudit($pdo, 'CREATION_ECHEANCE', 'Creation echeance #' . $id);
    setFlashMessage('success', 'Echeance creee.');
    redirect(DEADLINE_LIST_URL);
}

function updateDeadline(int $id): void
{
    requireRole(['EXPERT']);
    deadlinePostOnly();
    $pdo = getDatabaseConnection();
    $data = deadlineInput($_POST);
    $errors = validateDeadline($data);
    if ($errors) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        showDeadlineEdit($id);
        return;
    }
    (new FiscalDeadline($pdo))->update($id, $data);
    deadlineAudit($pdo, 'MODIFICATION_ECHEANCE', 'Modification echeance #' . $id);
    setFlashMessage('success', 'Echeance modifiee.');
    redirect(DEADLINE_LIST_URL);
}

function completeDeadline(int $id): void
{
    requireRole(['EXPERT']);
    deadlinePostOnly();
    $pdo = getDatabaseConnection();
    (new FiscalDeadline($pdo))->markAsCompleted($id);
    deadlineAudit($pdo, 'ECHEANCE_TERMINEE', 'Echeance terminee #' . $id);
    setFlashMessage('success', 'Echeance marquee terminee.');
    redirect(DEADLINE_LIST_URL);
}

function generateDeadlines(): void
{
    requireRole(['EXPERT']);
    deadlinePostOnly();
    $pdo = getDatabaseConnection();
    $year = max(2000, (int) ($_POST['year'] ?? date('Y')));
    $model = new FiscalDeadline($pdo);
    $created = $model->generateMonthlyVatDeadlines($year) + $model->generateAnnualIsDeadlines($year);
    deadlineAudit($pdo, 'GENERATION_ECHEANCES_FISCALES', 'Generation echeances fiscales ' . $year . ' : ' . $created);
    setFlashMessage('success', $created . ' echeance(s) generee(s).');
    redirect(DEADLINE_LIST_URL);
}

function deadlineInput(array $input): array
{
    foreach (['client_id','mission_id','title','description','deadline_date','status'] as $field) {
        $data[$field] = trim((string) ($input[$field] ?? ''));
    }
    $data['status'] = $data['status'] === '' ? 'A_VENIR' : $data['status'];
    return $data;
}

function validateDeadline(array $data): array
{
    $errors = [];
    if ((int) $data['client_id'] <= 0) { $errors[] = 'Client obligatoire.'; }
    if ($data['title'] === '') { $errors[] = 'Titre obligatoire.'; }
    if ($data['deadline_date'] === '') { $errors[] = 'Date echeance obligatoire.'; }
    if (!in_array($data['status'], FiscalDeadline::STATUSES, true)) { $errors[] = 'Statut invalide.'; }
    return $errors;
}

function deadlineFilters(): array
{
    $filters = [];
    foreach (['q','client_id','mission_id','status','date_from','date_to'] as $field) {
        $value = trim((string) ($_GET[$field] ?? ''));
        if ($value !== '') { $filters[$field] = $value; }
    }
    return $filters;
}

function findDeadlineOrRedirect(int $id): array
{
    $deadline = (new FiscalDeadline(getDatabaseConnection()))->findById($id);
    if ($deadline === null) {
        setFlashMessage('error', 'Echeance introuvable.');
        redirect(DEADLINE_LIST_URL);
    }
    return $deadline;
}

function fetchClients(PDO $pdo): array { $s = $pdo->prepare('SELECT id, company_name FROM clients ORDER BY company_name'); $s->execute(); return $s->fetchAll(PDO::FETCH_ASSOC); }
function fetchMissions(PDO $pdo): array { $s = $pdo->prepare('SELECT id, title, client_id FROM missions ORDER BY title'); $s->execute(); return $s->fetchAll(PDO::FETCH_ASSOC); }
function fetchClientForUser(PDO $pdo, int $userId): ?array { $s = $pdo->prepare('SELECT id FROM clients WHERE user_id = :id'); $s->execute(['id' => $userId]); $r = $s->fetch(PDO::FETCH_ASSOC); return $r ?: null; }
function deadlineCanAccess(array $deadline): bool
{
    $user = currentUser();
    if (($user['role'] ?? '') === 'EXPERT') {
        return true;
    }
    $pdo = getDatabaseConnection();
    if (($user['role'] ?? '') === 'CLIENT') {
        $client = fetchClientForUser($pdo, (int) $user['id']);
        return $client !== null && (int) $client['id'] === (int) $deadline['client_id'];
    }
    if (in_array(($user['role'] ?? ''), ['COLLABORATEUR', 'STAGIAIRE'], true) && !empty($deadline['mission_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM mission_assignments WHERE mission_id = :mission_id AND user_id = :user_id');
        $stmt->execute(['mission_id' => (int) $deadline['mission_id'], 'user_id' => (int) $user['id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    return false;
}
function deadlinePostOnly(): void { if (!isPostRequest()) { setFlashMessage('error', 'Action POST requise.'); redirect(DEADLINE_LIST_URL); } }
function deadlineAudit(PDO $pdo, string $action, string $description): void { (new AuditLog($pdo))->log(['user_id' => currentUserId(), 'action' => $action, 'description' => $description, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]); }
function renderDeadlineView(string $view, array $vars = []): void { extract($vars, EXTR_SKIP); if (!defined('MNS_CONTROLLER_RENDER')) { define('MNS_CONTROLLER_RENDER', true); } require __DIR__ . '/../../frontend/views/deadlines/' . $view; }
