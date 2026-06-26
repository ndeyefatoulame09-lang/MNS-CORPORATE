<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/role_check.php';

function handleClientsRequest(): void
{
	startSecureSession();
	requireRole(['EXPERT']);

	$action = $_GET['action'] ?? 'list';

	switch ($action) {
		case 'create':
			showCreateForm();
			break;
		case 'store':
			storeClient();
			break;
		case 'edit':
			showEditForm((int)($_GET['id'] ?? 0));
			break;
		case 'update':
			updateClient((int)($_POST['id'] ?? 0));
			break;
		case 'show':
			showClient((int)($_GET['id'] ?? 0));
			break;
		case 'toggle':
			toggleStatus((int)($_POST['id'] ?? $_GET['id'] ?? 0));
			break;
		default:
			listClients();
	}
}

function listClients(): void
{
	$pdo = getDatabaseConnection();
	$clientModel = new Client($pdo);

	$q = trim((string)($_GET['q'] ?? ''));
	$status = $_GET['status'] ?? '';
	$tax = $_GET['tax_regime'] ?? '';
	$page = max(1, (int)($_GET['page'] ?? 1));
	$perPage = 20;
	$offset = ($page - 1) * $perPage;

	$filters = [];
	if ($q !== '') $filters['q'] = $q;
	if ($status !== '') $filters['status'] = $status;
	if ($tax !== '') $filters['tax_regime'] = $tax;

	$clients = $clientModel->findAll($filters, $perPage, $offset);
	$total = $clientModel->count($filters);

	require_once __DIR__ . '/../../frontend/views/clients/list.php';
}

function showCreateForm(): void
{
	$client = null;
	require_once __DIR__ . '/../../frontend/views/clients/create.php';
}

function storeClient(): void
{
	startSecureSession();
	$pdo = getDatabaseConnection();
	$clientModel = new Client($pdo);
	$audit = new AuditLog($pdo);

	$data = array_map(function($v){ return is_string($v)? trim($v): $v; }, $_POST);

	$errors = validateClientData($pdo, $data);
	if (!empty($errors)) {
		$_SESSION['old_input'] = $_POST;
		setFlashMessage('error', implode(' ', $errors));
		require_once __DIR__ . '/../../frontend/views/clients/create.php';
		return;
	}

	$id = $clientModel->create($data);

	if ($id > 0) {
		$audit->log(['user_id' => currentUserId(), 'action' => 'client_create', 'description' => 'Création client: ' . ($data['company_name'] ?? '') , 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]);
		setFlashMessage('success', 'Client créé avec succès.');
		redirect('/MNS_CORPORATE/clients.php');
	}

	setFlashMessage('error', 'Impossible de créer le client.');
	redirect('/MNS_CORPORATE/clients.php');
}

function showEditForm(int $id): void
{
	$pdo = getDatabaseConnection();
	$clientModel = new Client($pdo);
	$client = $clientModel->findById($id);
	if ($client === null) {
		setFlashMessage('error', 'Client introuvable.');
		redirect('/MNS_CORPORATE/clients.php');
	}

	require_once __DIR__ . '/../../frontend/views/clients/edit.php';
}

function updateClient(int $id): void
{
	startSecureSession();
	$pdo = getDatabaseConnection();
	$clientModel = new Client($pdo);
	$audit = new AuditLog($pdo);

	$data = array_map(function($v){ return is_string($v)? trim($v): $v; }, $_POST);

	$errors = validateClientData($pdo, $data, $id);
	if (!empty($errors)) {
		$_SESSION['old_input'] = $_POST;
		setFlashMessage('error', implode(' ', $errors));
		require_once __DIR__ . '/../../frontend/views/clients/edit.php';
		return;
	}

	$ok = $clientModel->update($id, $data);
	if ($ok) {
		$audit->log(['user_id' => currentUserId(), 'action' => 'client_update', 'description' => 'Mise à jour client id:' . $id, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]);
		setFlashMessage('success', 'Client mis à jour.');
		redirect('/MNS_CORPORATE/clients.php');
	}

	setFlashMessage('error', 'Aucune modification effectuée.');
	redirect('/MNS_CORPORATE/clients.php');
}

function showClient(int $id): void
{
	$pdo = getDatabaseConnection();
	$clientModel = new Client($pdo);
	$client = $clientModel->findById($id);
	if ($client === null) {
		setFlashMessage('error', 'Client introuvable.');
		redirect('/MNS_CORPORATE/clients.php');
	}
	require_once __DIR__ . '/../../frontend/views/clients/show.php';
}

function toggleStatus(int $id): void
{
	startSecureSession();
	$pdo = getDatabaseConnection();
	$clientModel = new Client($pdo);
	$audit = new AuditLog($pdo);

	$client = $clientModel->findById($id);
	if ($client === null) {
		setFlashMessage('error', 'Client introuvable.');
		redirect('/MNS_CORPORATE/clients.php');
	}

	$newStatus = ($client['status'] === 'ACTIF') ? 'INACTIF' : 'ACTIF';
	$clientModel->update($id, ['status' => $newStatus]);

	$audit->log(['user_id' => currentUserId(), 'action' => 'client_status_toggle', 'description' => 'Changement statut client id:' . $id . ' => ' . $newStatus, 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]);

	setFlashMessage('success', 'Statut mis à jour.');
	redirect('/MNS_CORPORATE/clients.php');
}

function validateClientData(PDO $pdo, array $data, int $id = 0): array
{
	$errors = [];

	$company = trim((string)($data['company_name'] ?? ''));
	if ($company === '') {
		$errors[] = 'Le nom de l entreprise est requis.';
	}

	$email = trim((string)($data['email'] ?? ''));
	if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Email invalide.';
	}

	$ninea = trim((string)($data['ninea'] ?? ''));
	if ($ninea !== '') {
		$stmt = $pdo->prepare('SELECT id FROM clients WHERE ninea = :ninea' . ($id ? ' AND id != :id' : ''));
		$params = ['ninea' => $ninea];
		if ($id) $params['id'] = $id;
		$stmt->execute($params);
		if ($stmt->fetch()) $errors[] = 'NINEA déjà utilisé.';
	}

	$rccm = trim((string)($data['rccm'] ?? ''));
	if ($rccm !== '') {
		$stmt = $pdo->prepare('SELECT id FROM clients WHERE rccm = :rccm' . ($id ? ' AND id != :id' : ''));
		$params = ['rccm' => $rccm];
		if ($id) $params['id'] = $id;
		$stmt->execute($params);
		if ($stmt->fetch()) $errors[] = 'RCCM déjà utilisé.';
	}

	$start = $data['accounting_year_start'] ?? null;
	$end = $data['accounting_year_end'] ?? null;
	if ($start && $end) {
		if (strtotime($end) < strtotime($start)) {
			$errors[] = 'La date de fin de l exercice ne peut être antérieure au début.';
		}
	}

	$status = $data['status'] ?? '';
	if ($status === '') $status = 'ACTIF';
	if (!in_array($status, ['ACTIF','INACTIF'], true)) {
		$errors[] = 'Statut invalide.';
	}

	return $errors;
}
