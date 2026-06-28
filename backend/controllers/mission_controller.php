<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Mission.php';
require_once __DIR__ . '/../models/MissionCatalog.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

const MISSION_LIST_URL = '/MNS_CORPORATE/frontend/views/missions/list.php';
const CATALOG_LIST_URL = '/MNS_CORPORATE/frontend/views/missions/catalog_list.php';

function handleMissionsRequest(): void
{
    startSecureSession();
    requireAuth();

    $action = $_GET['action'] ?? 'list';

    if ($action === 'store') {
        storeMission();
    } elseif ($action === 'update') {
        updateMission((int) ($_POST['id'] ?? 0));
    } elseif ($action === 'status') {
        changeMissionStatus((int) ($_POST['id'] ?? 0));
    } elseif ($action === 'create') {
        showCreateMissionForm();
    } elseif ($action === 'edit') {
        showEditMissionForm((int) ($_GET['id'] ?? 0));
    } elseif ($action === 'show') {
        showMission((int) ($_GET['id'] ?? 0));
    } else {
        listMissions();
    }
}

function handleMissionCatalogRequest(): void
{
    startSecureSession();
    requireRole(['EXPERT']);

    $action = $_GET['action'] ?? 'list';

    if ($action === 'store') {
        storeCatalogItem();
    } elseif ($action === 'update') {
        updateCatalogItem((int) ($_POST['id'] ?? 0));
    } elseif ($action === 'toggle') {
        toggleCatalogItem((int) ($_POST['id'] ?? 0));
    } elseif ($action === 'create') {
        showCreateCatalogForm();
    } elseif ($action === 'edit') {
        showEditCatalogForm((int) ($_GET['id'] ?? 0));
    } else {
        listCatalogItems();
    }
}

function listMissions(): void
{
    startSecureSession();
    requireAuth();
    $model = new Mission(getDatabaseConnection());
    $filters = missionFiltersFromQuery();
    $user = currentUser();
    if (in_array(($user['role'] ?? ''), ['COLLABORATEUR', 'STAGIAIRE'], true)) {
        $filters['assigned_user_id'] = (int) $user['id'];
    } elseif (($user['role'] ?? '') === 'CLIENT') {
        $client = missionClientForUser(getDatabaseConnection(), (int) $user['id']);
        $filters['visible_client_id'] = $client['id'] ?? 0;
    } elseif (($user['role'] ?? '') !== 'EXPERT') {
        setFlashMessage('error', 'Vous nÃ¢â‚¬â„¢avez pas les droits pour accÃƒÂ©der ÃƒÂ  cette page.');
        redirect('/MNS_CORPORATE/index.php');
    }
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $missions = $model->findAll($filters, $perPage, ($page - 1) * $perPage);
    $total = $model->countAll($filters);
    $clients = $model->getActiveClients();
    $catalogItems = $model->getActiveCatalogItems();

    renderMissionView('list.php', compact('missions', 'total', 'filters', 'page', 'perPage', 'clients', 'catalogItems'));
}

function showCreateMissionForm(): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $model = new Mission(getDatabaseConnection());
    $mission = null;
    $clients = $model->getActiveClients();
    $catalogItems = $model->getActiveCatalogItems();

    renderMissionView('create.php', compact('mission', 'clients', 'catalogItems'));
}

function storeMission(): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureMissionPostOrRedirect(MISSION_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new Mission($pdo);
    $data = normalizeMissionInput($_POST);
    $data['created_by'] = currentUserId();
    $errors = validateMissionData($model, $data, true);

    if ($errors !== []) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        showCreateMissionForm();
        return;
    }

    $id = $model->create($data);
    logMissionAudit($pdo, 'CREATION_MISSION', 'Creation de la mission #' . $id . ' : ' . $data['title']);
    setFlashMessage('success', 'Mission creee avec succes.');
    redirect(MISSION_LIST_URL);
}

function showEditMissionForm(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $model = new Mission(getDatabaseConnection());
    $mission = findMissionOrRedirect($id);
    $clients = $model->getActiveClients();
    $catalogItems = $model->getActiveCatalogItems();

    renderMissionView('edit.php', compact('mission', 'clients', 'catalogItems'));
}

function updateMission(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureMissionPostOrRedirect(MISSION_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new Mission($pdo);
    $mission = findMissionOrRedirect($id);
    $data = normalizeMissionInput($_POST);
    $errors = validateMissionData($model, $data, false);

    if ($errors !== []) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        $clients = $model->getActiveClients();
        $catalogItems = $model->getActiveCatalogItems();
        renderMissionView('edit.php', compact('mission', 'clients', 'catalogItems'));
        return;
    }

    $model->update($id, $data);
    logMissionAudit($pdo, 'MODIFICATION_MISSION', 'Modification de la mission #' . $id . ' : ' . $data['title']);
    setFlashMessage('success', 'Mission modifiee avec succes.');
    redirect(MISSION_LIST_URL);
}

function showMission(int $id): void
{
    startSecureSession();
    requireAuth();
    $mission = findMissionOrRedirect($id);
    if (!missionCanAccess($mission)) {
        setFlashMessage('error', 'Mission inaccessible.');
        redirect('/MNS_CORPORATE/index.php');
    }

    renderMissionView('show.php', compact('mission'));
}

function changeMissionStatus(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureMissionPostOrRedirect(MISSION_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new Mission($pdo);
    $mission = findMissionOrRedirect($id);
    $status = trim((string) ($_POST['status'] ?? ''));

    if (!in_array($status, Mission::STATUSES, true)) {
        setFlashMessage('error', 'Statut de mission invalide.');
        redirect(MISSION_LIST_URL);
    }

    $actualEndDate = $mission['actual_end_date'] ?? null;
    if ($status === 'TERMINEE' && empty($actualEndDate)) {
        $actualEndDate = date('Y-m-d');
    }
    if ($status !== 'TERMINEE' && $status !== 'ANNULEE') {
        $actualEndDate = null;
    }

    $model->updateStatus($id, $status, $actualEndDate);
    logMissionAudit($pdo, 'CHANGEMENT_STATUT_MISSION', 'Mission #' . $id . ' : ' . $mission['status'] . ' vers ' . $status);
    setFlashMessage('success', 'Statut de mission mis a jour.');
    redirect(MISSION_LIST_URL);
}

function listCatalogItems(): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $model = new MissionCatalog(getDatabaseConnection());
    $filters = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'is_active' => trim((string) ($_GET['is_active'] ?? '')),
    ];
    $filters = array_filter($filters, static fn($value): bool => $value !== '');
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $catalogItems = $model->findAll($filters, $perPage, ($page - 1) * $perPage);
    $total = $model->countAll($filters);

    renderMissionView('catalog_list.php', compact('catalogItems', 'total', 'filters', 'page', 'perPage'));
}

function showCreateCatalogForm(): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $catalogItem = null;

    renderMissionView('catalog_create.php', compact('catalogItem'));
}

function storeCatalogItem(): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureMissionPostOrRedirect(CATALOG_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new MissionCatalog($pdo);
    $data = normalizeCatalogInput($_POST);
    $errors = validateCatalogData($model, $data);

    if ($errors !== []) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        showCreateCatalogForm();
        return;
    }

    $id = $model->create($data);
    logMissionAudit($pdo, 'CREATION_TYPE_MISSION', 'Creation du type de mission #' . $id . ' : ' . $data['name']);
    setFlashMessage('success', 'Type de mission cree avec succes.');
    redirect(CATALOG_LIST_URL);
}

function showEditCatalogForm(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    $catalogItem = findCatalogItemOrRedirect($id);

    renderMissionView('catalog_edit.php', compact('catalogItem'));
}

function updateCatalogItem(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureMissionPostOrRedirect(CATALOG_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new MissionCatalog($pdo);
    $catalogItem = findCatalogItemOrRedirect($id);
    $data = normalizeCatalogInput($_POST);
    $errors = validateCatalogData($model, $data, $id);

    if ($errors !== []) {
        $_SESSION['old_input'] = $data;
        setFlashMessage('error', implode(' ', $errors));
        renderMissionView('catalog_edit.php', compact('catalogItem'));
        return;
    }

    $model->update($id, $data);
    logMissionAudit($pdo, 'MODIFICATION_TYPE_MISSION', 'Modification du type de mission #' . $id . ' : ' . $data['name']);
    setFlashMessage('success', 'Type de mission modifie avec succes.');
    redirect(CATALOG_LIST_URL);
}

function toggleCatalogItem(int $id): void
{
    startSecureSession();
    requireRole(['EXPERT']);
    ensureMissionPostOrRedirect(CATALOG_LIST_URL);

    $pdo = getDatabaseConnection();
    $model = new MissionCatalog($pdo);
    $catalogItem = findCatalogItemOrRedirect($id);
    $newStatus = (int) $catalogItem['is_active'] === 1 ? 0 : 1;
    $model->setActiveStatus($id, $newStatus === 1);

    logMissionAudit(
        $pdo,
        $newStatus === 1 ? 'ACTIVATION_TYPE_MISSION' : 'DESACTIVATION_TYPE_MISSION',
        'Changement du statut du type de mission #' . $id . ' vers ' . ($newStatus === 1 ? 'actif' : 'inactif')
    );

    setFlashMessage('success', 'Statut du type de mission mis a jour.');
    redirect(CATALOG_LIST_URL);
}

function validateMissionData(Mission $model, array $data, bool $activeOnly): array
{
    $errors = [];
    $clientId = (int) $data['client_id'];
    $catalogId = (int) $data['mission_catalog_id'];

    if ($clientId <= 0 || !$model->existsClient($clientId, $activeOnly)) {
        $errors[] = 'Le client selectionne est invalide.';
    }

    if ($catalogId <= 0 || !$model->existsCatalog($catalogId, $activeOnly)) {
        $errors[] = 'Le type de mission selectionne est invalide.';
    }

    if ($data['title'] === '') {
        $errors[] = 'Le titre est obligatoire.';
    }

    if ($data['start_date'] === '') {
        $errors[] = 'La date de debut est obligatoire.';
    } elseif ($activeOnly && $data['start_date'] < date('Y-m-d')) {
        $errors[] = 'Une nouvelle mission doit commencer aujourd hui ou a une date future.';
    }

    if ($data['planned_end_date'] !== '' && $data['start_date'] !== '' && $data['planned_end_date'] < $data['start_date']) {
        $errors[] = 'La date de fin prevue ne peut pas etre avant la date de debut.';
    }

    if ($data['actual_end_date'] !== '' && $data['start_date'] !== '' && $data['actual_end_date'] < $data['start_date']) {
        $errors[] = 'La date de fin reelle ne peut pas etre avant la date de debut.';
    }

    if ($data['actual_end_date'] !== '' && $data['status'] !== 'TERMINEE') {
        $errors[] = 'La date de fin reelle est reservee aux missions terminees.';
    }

    if ($data['estimated_hours'] !== '' && (!is_numeric($data['estimated_hours']) || (float) $data['estimated_hours'] <= 0)) {
        $errors[] = 'Le temps estime doit etre un nombre positif.';
    }

    if (!in_array($data['status'], Mission::STATUSES, true)) {
        $errors[] = 'Le statut est invalide.';
    }

    if (!in_array($data['priority'], Mission::PRIORITIES, true)) {
        $errors[] = 'La priorite est invalide.';
    }

    if (!isset($data['created_by']) && $activeOnly) {
        $errors[] = 'Utilisateur createur introuvable.';
    }

    return $errors;
}

function validateCatalogData(MissionCatalog $model, array $data, int $id = 0): array
{
    $errors = [];

    if ($data['name'] === '') {
        $errors[] = 'Le nom du type de mission est obligatoire.';
    } elseif ($model->existsByName($data['name'], $id)) {
        $errors[] = 'Ce type de mission existe deja.';
    }

    if ($data['default_duration_days'] !== '' && (!ctype_digit($data['default_duration_days']) || (int) $data['default_duration_days'] <= 0)) {
        $errors[] = 'La duree par defaut doit etre un entier positif.';
    }

    if (!in_array($data['is_active'], ['0', '1'], true)) {
        $errors[] = 'Le statut actif/inactif est invalide.';
    }

    return $errors;
}

function normalizeMissionInput(array $input): array
{
    $fields = [
        'client_id', 'mission_catalog_id', 'title', 'description', 'start_date',
        'planned_end_date', 'actual_end_date', 'status', 'priority', 'estimated_hours',
    ];
    $data = [];

    foreach ($fields as $field) {
        $data[$field] = trim((string) ($input[$field] ?? ''));
    }

    $data['status'] = $data['status'] === '' ? 'A_FAIRE' : $data['status'];
    $data['priority'] = $data['priority'] === '' ? 'MOYENNE' : $data['priority'];

    return $data;
}

function normalizeCatalogInput(array $input): array
{
    return [
        'name' => trim((string) ($input['name'] ?? '')),
        'description' => trim((string) ($input['description'] ?? '')),
        'default_duration_days' => trim((string) ($input['default_duration_days'] ?? '')),
        'is_active' => trim((string) ($input['is_active'] ?? '1')),
    ];
}

function missionFiltersFromQuery(): array
{
    $filters = [];

    foreach (['q', 'client_id', 'mission_catalog_id', 'status', 'priority', 'start_from', 'start_to'] as $field) {
        $value = trim((string) ($_GET[$field] ?? ''));
        if ($value !== '') {
            $filters[$field] = $value;
        }
    }

    return $filters;
}

function findMissionOrRedirect(int $id): array
{
    $mission = (new Mission(getDatabaseConnection()))->findById($id);

    if ($mission === null) {
        setFlashMessage('error', 'Mission introuvable.');
        redirect(MISSION_LIST_URL);
    }

    return $mission;
}

function findCatalogItemOrRedirect(int $id): array
{
    $catalogItem = (new MissionCatalog(getDatabaseConnection()))->findById($id);

    if ($catalogItem === null) {
        setFlashMessage('error', 'Type de mission introuvable.');
        redirect(CATALOG_LIST_URL);
    }

    return $catalogItem;
}

function missionClientForUser(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT id FROM clients WHERE user_id = :user_id');
    $stmt->execute(['user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function missionCanAccess(array $mission): bool
{
    $user = currentUser();
    if (($user['role'] ?? '') === 'EXPERT') {
        return true;
    }

    $pdo = getDatabaseConnection();
    if (in_array(($user['role'] ?? ''), ['COLLABORATEUR', 'STAGIAIRE'], true)) {
        $stmt = $pdo->prepare('SELECT id FROM mission_assignments WHERE mission_id = :mission_id AND user_id = :user_id');
        $stmt->execute(['mission_id' => (int) $mission['id'], 'user_id' => (int) $user['id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    if (($user['role'] ?? '') === 'CLIENT') {
        $client = missionClientForUser($pdo, (int) $user['id']);
        return $client !== null && (int) $client['id'] === (int) $mission['client_id'];
    }

    return false;
}

function renderMissionView(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    if (!defined('MNS_CONTROLLER_RENDER')) {
        define('MNS_CONTROLLER_RENDER', true);
    }
    require __DIR__ . '/../../frontend/views/missions/' . $view;
}

function ensureMissionPostOrRedirect(string $target): void
{
    if (!isPostRequest()) {
        setFlashMessage('error', 'Action non autorisee en GET.');
        redirect($target);
    }
}

function logMissionAudit(PDO $pdo, string $action, string $description): void
{
    (new AuditLog($pdo))->log([
        'user_id' => currentUserId(),
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
