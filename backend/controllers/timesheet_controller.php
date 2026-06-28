<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Timesheet.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/notification_controller.php';

const TIMESHEET_LIST_URL = '/MNS_CORPORATE/frontend/views/timesheets/list.php';

function handleTimesheetRequest(): void
{
    startSecureSession();
    requireAuth();
    $action = $_GET['action'] ?? 'list';
    if ($action === 'store') { storeTimesheet(); }
    elseif ($action === 'validate') { validateTimesheet((int) ($_POST['id'] ?? 0)); }
    elseif ($action === 'reject') { rejectTimesheet((int) ($_POST['id'] ?? 0)); }
    elseif ($action === 'create') { showTimesheetCreate(); }
    elseif ($action === 'show') { showTimesheet((int) ($_GET['id'] ?? 0)); }
    elseif ($action === 'summary') { showTimesheetSummary(); }
    else { listTimesheets(); }
}

function listTimesheets(): void
{
    $pdo = getDatabaseConnection();
    $model = new Timesheet($pdo);
    $filters = timesheetFilters();
    $user = currentUser();
    if (in_array($user['role'], ['COLLABORATEUR', 'STAGIAIRE'], true)) {
        $filters['user_id'] = (int) $user['id'];
    } elseif ($user['role'] !== 'EXPERT') {
        setFlashMessage('error', 'Acces refuse.');
        redirect('/MNS_CORPORATE/index.php');
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $timesheets = $model->findAll($filters, $perPage, ($page - 1) * $perPage);
    $total = $model->countAll($filters);
    $totalHours = $model->getUserHoursSummary($filters);
    $missions = timesheetMissionsForUser($pdo, $user);
    $users = timesheetUsers($pdo);
    renderTimesheetView('list.php', compact('timesheets', 'total', 'totalHours', 'filters', 'page', 'perPage', 'missions', 'users'));
}

function showTimesheetCreate(): void
{
    requireRole(['COLLABORATEUR', 'STAGIAIRE']);
    $model = new Timesheet(getDatabaseConnection());
    $timesheet = null;
    $missions = $model->findAssignedMissions((int) currentUserId());
    renderTimesheetView('create.php', compact('timesheet', 'missions'));
}

function storeTimesheet(): void
{
    requireRole(['COLLABORATEUR', 'STAGIAIRE']);
    timesheetPostOnly();
    $pdo = getDatabaseConnection();
    $model = new Timesheet($pdo);
    $data = timesheetInput($_POST);
    $data['user_id'] = currentUserId();
    $errors = validateTimesheetInput($model, $data);
    if ($errors) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        showTimesheetCreate();
        return;
    }
    $id = $model->create($data);
    timesheetAudit($pdo, 'CREATION_TIMESHEET', 'Creation timesheet #' . $id);
    setFlashMessage('success', 'Temps saisi.');
    redirect(TIMESHEET_LIST_URL);
}

function showTimesheet(int $id): void
{
    $timesheet = findTimesheetOrRedirect($id);
    $user = currentUser();
    if ($user['role'] !== 'EXPERT' && (int) $timesheet['user_id'] !== (int) $user['id']) {
        setFlashMessage('error', 'Acces refuse.');
        redirect(TIMESHEET_LIST_URL);
    }
    renderTimesheetView('show.php', compact('timesheet'));
}

function validateTimesheet(int $id): void
{
    requireRole(['EXPERT']);
    timesheetPostOnly();
    $pdo = getDatabaseConnection();
    $timesheet = findTimesheetOrRedirect($id);
    (new Timesheet($pdo))->validate($id, (int) currentUserId());
    createInternalNotification($pdo, (int) $timesheet['user_id'], 'Timesheet valide', 'Votre saisie du ' . $timesheet['work_date'] . ' a ete validee.', 'TIMESHEET', $id, true);
    timesheetAudit($pdo, 'VALIDATION_TIMESHEET', 'Validation timesheet #' . $id);
    setFlashMessage('success', 'Timesheet valide.');
    redirect(TIMESHEET_LIST_URL);
}

function rejectTimesheet(int $id): void
{
    requireRole(['EXPERT']);
    timesheetPostOnly();
    $pdo = getDatabaseConnection();
    $timesheet = findTimesheetOrRedirect($id);
    (new Timesheet($pdo))->reject($id, (int) currentUserId());
    createInternalNotification($pdo, (int) $timesheet['user_id'], 'Timesheet refuse', 'Votre saisie du ' . $timesheet['work_date'] . ' a ete refusee.', 'TIMESHEET', $id, true);
    timesheetAudit($pdo, 'REFUS_TIMESHEET', 'Refus timesheet #' . $id);
    setFlashMessage('success', 'Timesheet refuse.');
    redirect(TIMESHEET_LIST_URL);
}

function showTimesheetSummary(): void
{
    requireRole(['EXPERT']);
    $summary = (new Timesheet(getDatabaseConnection()))->getMissionHoursSummary(['mission_id' => trim((string) ($_GET['mission_id'] ?? ''))]);
    renderTimesheetView('summary.php', compact('summary'));
}

function validateTimesheetInput(Timesheet $model, array $data): array
{
    $errors = [];
    if ((int) $data['mission_id'] <= 0 || !$model->isUserAssignedToMission((int) $data['user_id'], (int) $data['mission_id'])) { $errors[] = 'Mission invalide.'; }
    if ($data['work_date'] === '') { $errors[] = 'Date obligatoire.'; } elseif ($data['work_date'] > date('Y-m-d')) { $errors[] = 'La date de travail ne peut pas etre dans le futur.'; }
    if ($data['description'] === '') { $errors[] = 'Description obligatoire.'; } elseif (mb_strlen($data['description']) < 5) { $errors[] = 'La description doit etre plus precise.'; }
    if (!is_numeric($data['hours_worked']) || (float) $data['hours_worked'] <= 0 || (float) $data['hours_worked'] > 24) { $errors[] = 'Les heures doivent etre entre 0 et 24.'; }
    if ($data['work_date'] !== '' && is_numeric($data['hours_worked']) && $model->getTotalHoursByUserAndDate((int) $data['user_id'], $data['work_date']) + (float) $data['hours_worked'] > 24) { $errors[] = 'Le total journalier depasse 24 heures.'; }
    return $errors;
}

function timesheetInput(array $input): array
{
    foreach (['mission_id','work_date','hours_worked','description'] as $field) {
        $data[$field] = trim((string) ($input[$field] ?? ''));
    }
    return $data;
}

function timesheetFilters(): array
{
    $filters = [];
    foreach (['q','user_id','mission_id','status','date_from','date_to'] as $field) {
        $value = trim((string) ($_GET[$field] ?? ''));
        if ($value !== '') { $filters[$field] = $value; }
    }
    return $filters;
}

function findTimesheetOrRedirect(int $id): array
{
    $timesheet = (new Timesheet(getDatabaseConnection()))->findById($id);
    if ($timesheet === null) {
        setFlashMessage('error', 'Timesheet introuvable.');
        redirect(TIMESHEET_LIST_URL);
    }
    return $timesheet;
}

function timesheetMissionsForUser(PDO $pdo, array $user): array
{
    if ($user['role'] === 'EXPERT') {
        $stmt = $pdo->prepare('SELECT id, title FROM missions ORDER BY title');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return (new Timesheet($pdo))->findAssignedMissions((int) $user['id']);
}

function timesheetUsers(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role IN ('COLLABORATEUR', 'STAGIAIRE') ORDER BY full_name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function timesheetPostOnly(): void { if (!isPostRequest()) { redirect(TIMESHEET_LIST_URL); } }
function timesheetAudit(PDO $pdo, string $action, string $description): void { (new AuditLog($pdo))->log(['user_id'=>currentUserId(),'action'=>$action,'description'=>$description,'ip_address'=>$_SERVER['REMOTE_ADDR']??null]); }
function renderTimesheetView(string $view, array $vars = []): void { extract($vars, EXTR_SKIP); if (!defined('MNS_CONTROLLER_RENDER')) { define('MNS_CONTROLLER_RENDER', true); } require __DIR__ . '/../../frontend/views/timesheets/' . $view; }
