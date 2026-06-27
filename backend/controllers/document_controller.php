<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/upload_service.php';
require_once __DIR__ . '/notification_controller.php';

const DOCUMENT_LIST_URL = '/MNS_CORPORATE/frontend/views/documents/list.php';

function handleDocumentRequest(): void
{
    startSecureSession();
    requireAuth();
    $action = $_GET['action'] ?? 'list';
    if ($action === 'store') { storeDocument(); }
    elseif ($action === 'download') { downloadDocument((int) ($_GET['id'] ?? 0)); }
    elseif ($action === 'status') { changeDocumentStatus((int) ($_POST['id'] ?? 0)); }
    elseif ($action === 'comment') { addDocumentComment((int) ($_POST['document_id'] ?? 0)); }
    elseif ($action === 'upload') { showDocumentUpload(); }
    elseif ($action === 'show') { showDocument((int) ($_GET['id'] ?? 0)); }
    else { listDocuments(); }
}

function listDocuments(): void
{
    $model = new Document(getDatabaseConnection());
    $filters = documentFilters();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $user = currentUser();
    $documents = $model->findAccessibleByUser($user, $filters, $perPage, ($page - 1) * $perPage);
    $total = $model->countAccessibleByUser($user, $filters);
    renderDocumentView('list.php', compact('documents', 'total', 'filters', 'page', 'perPage'));
}

function showDocumentUpload(): void
{
    requireAuth();
    $pdo = getDatabaseConnection();
    $clients = documentSelectableClients($pdo, currentUser());
    $missions = documentSelectableMissions($pdo, currentUser());
    renderDocumentView('upload.php', compact('clients', 'missions'));
}

function storeDocument(): void
{
    requireAuth();
    documentPostOnly();
    $pdo = getDatabaseConnection();
    try {
        $fileData = handleSecureDocumentUpload($_FILES['document_file'] ?? []);
        $data = documentInput($_POST) + $fileData + ['uploaded_by' => currentUserId()];
        if (!documentCanUploadForSelection($pdo, currentUser(), (int) $data['client_id'], $data['mission_id'] === '' ? null : (int) $data['mission_id'])) {
            setFlashMessage('error', 'Perimetre document non autorise.');
            redirect('/MNS_CORPORATE/frontend/views/documents/upload.php');
        }
        $errors = validateDocument($data);
        if ($errors) {
            setFlashMessage('error', implode(' ', $errors));
            redirect('/MNS_CORPORATE/frontend/views/documents/upload.php');
        }
        $id = (new Document($pdo))->create($data);
        notifyDocumentUploadRecipients($pdo, currentUser(), (int) $data['client_id'], $data['mission_id'] === '' ? null : (int) $data['mission_id'], $data['title'], $id);
        documentAudit($pdo, 'UPLOAD_DOCUMENT', 'Upload document #' . $id);
        setFlashMessage('success', 'Document ajoute.');
        redirect(DOCUMENT_LIST_URL);
    } catch (Throwable $e) {
        setFlashMessage('error', $e->getMessage());
        redirect('/MNS_CORPORATE/frontend/views/documents/upload.php');
    }
}

function showDocument(int $id): void
{
    $pdo = getDatabaseConnection();
    $model = new Document($pdo);
    $user = currentUser();
    if (!$model->canAccess($user, $id)) { setFlashMessage('error', 'Document inaccessible.'); redirect(DOCUMENT_LIST_URL); }
    $document = $model->getDocumentWithRelations($id);
    if ($document === null) { redirect(DOCUMENT_LIST_URL); }
    if ($document['status'] === 'NOUVEAU') { $model->updateStatus($id, 'CONSULTE'); }
    $comments = (new Comment($pdo))->findByDocument($id);
    documentAudit($pdo, 'CONSULTATION_DOCUMENT', 'Consultation document #' . $id);
    renderDocumentView('show.php', compact('document', 'comments'));
}

function downloadDocument(int $id): void
{
    $pdo = getDatabaseConnection();
    $model = new Document($pdo);
    $user = currentUser();
    if (!$model->canAccess($user, $id)) { setFlashMessage('error', 'Document inaccessible.'); redirect('/MNS_CORPORATE/index.php'); }
    $document = $model->getDocumentWithRelations($id);
    if ($document === null) { http_response_code(404); exit('Introuvable'); }
    $path = __DIR__ . '/../../' . $document['file_path'];
    if (!is_file($path)) { http_response_code(404); exit('Fichier introuvable'); }
    documentAudit($pdo, 'TELECHARGEMENT_DOCUMENT', 'Telechargement document #' . $id);
    header('Content-Type: ' . ($document['file_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . basename($document['original_filename']) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

function changeDocumentStatus(int $id): void
{
    requireRole(['EXPERT']);
    documentPostOnly();
    $status = trim((string) ($_POST['status'] ?? ''));
    if (!in_array($status, ['VALIDE', 'REJETE'], true)) { setFlashMessage('error', 'Statut invalide.'); redirect(DOCUMENT_LIST_URL); }
    $pdo = getDatabaseConnection();
    (new Document($pdo))->updateStatus($id, $status);
    documentAudit($pdo, $status === 'VALIDE' ? 'VALIDATION_DOCUMENT' : 'REJET_DOCUMENT', 'Statut document #' . $id . ' : ' . $status);
    setFlashMessage('success', 'Document mis a jour.');
    redirect('/MNS_CORPORATE/frontend/views/documents/show.php?id=' . $id);
}

function addDocumentComment(int $documentId): void
{
    documentPostOnly();
    $pdo = getDatabaseConnection();
    $model = new Document($pdo);
    if (!$model->canAccess(currentUser(), $documentId)) { redirect(DOCUMENT_LIST_URL); }
    $message = trim((string) ($_POST['message'] ?? ''));
    if ($message !== '') {
        (new Comment($pdo))->create(['document_id' => $documentId, 'user_id' => currentUserId(), 'message' => $message]);
        $document = $model->getDocumentWithRelations($documentId);
        if ($document !== null) {
            notifyDocumentCommentRecipients($pdo, currentUser(), $document, $documentId);
        }
        documentAudit($pdo, 'AJOUT_COMMENTAIRE_DOCUMENT', 'Commentaire document #' . $documentId);
    }
    redirect('/MNS_CORPORATE/frontend/views/documents/show.php?id=' . $documentId);
}

function documentInput(array $input): array
{
    foreach (['client_id','mission_id','title','document_category'] as $field) { $data[$field] = trim((string) ($input[$field] ?? '')); }
    $data['document_category'] = $data['document_category'] === '' ? 'AUTRE' : $data['document_category'];
    return $data;
}
function validateDocument(array $data): array { $e=[]; if((int)$data['client_id']<=0){$e[]='Client obligatoire.';} if($data['title']===''){$e[]='Titre obligatoire.';} if(!in_array($data['document_category'], Document::CATEGORIES, true)){$e[]='Categorie invalide.';} return $e; }
function documentFilters(): array { $f=[]; foreach(['q','client_id','mission_id','status','document_category'] as $k){$v=trim((string)($_GET[$k]??'')); if($v!==''){$f[$k]=$v;}} return $f; }
function documentPostOnly(): void { if(!isPostRequest()){setFlashMessage('error','Action POST requise.'); redirect(DOCUMENT_LIST_URL);} }
function documentSelectableClients(PDO $pdo, array $user): array
{
    if ($user['role'] === 'EXPERT') {
        $stmt = $pdo->prepare('SELECT id, company_name FROM clients ORDER BY company_name');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($user['role'] === 'CLIENT') {
        $stmt = $pdo->prepare('SELECT id, company_name FROM clients WHERE user_id = :user_id');
        $stmt->execute(['user_id' => (int) $user['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if (in_array($user['role'], ['COLLABORATEUR', 'STAGIAIRE'], true)) {
        $stmt = $pdo->prepare('SELECT DISTINCT c.id, c.company_name FROM clients c INNER JOIN missions m ON m.client_id = c.id INNER JOIN mission_assignments ma ON ma.mission_id = m.id WHERE ma.user_id = :user_id ORDER BY c.company_name');
        $stmt->execute(['user_id' => (int) $user['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    return [];
}

function documentSelectableMissions(PDO $pdo, array $user): array
{
    if ($user['role'] === 'EXPERT') {
        $stmt = $pdo->prepare('SELECT id, title, client_id FROM missions ORDER BY title');
    } elseif (in_array($user['role'], ['COLLABORATEUR', 'STAGIAIRE'], true)) {
        $stmt = $pdo->prepare('SELECT m.id, m.title, m.client_id FROM missions m INNER JOIN mission_assignments ma ON ma.mission_id = m.id WHERE ma.user_id = :user_id ORDER BY m.title');
        $stmt->execute(['user_id' => (int) $user['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($user['role'] === 'CLIENT') {
        $stmt = $pdo->prepare('SELECT m.id, m.title, m.client_id FROM missions m INNER JOIN clients c ON c.id = m.client_id WHERE c.user_id = :user_id ORDER BY m.title');
        $stmt->execute(['user_id' => (int) $user['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        return [];
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function documentCanUploadForSelection(PDO $pdo, array $user, int $clientId, ?int $missionId): bool
{
    foreach (documentSelectableClients($pdo, $user) as $client) {
        if ((int) $client['id'] === $clientId) {
            if ($missionId === null) {
                return true;
            }
            foreach (documentSelectableMissions($pdo, $user) as $mission) {
                if ((int) $mission['id'] === $missionId && (int) $mission['client_id'] === $clientId) {
                    return true;
                }
            }
        }
    }
    return false;
}

function notifyDocumentUploadRecipients(PDO $pdo, array $actor, int $clientId, ?int $missionId, string $title, int $documentId): void
{
    $recipients = documentExpertIds($pdo);
    foreach ($recipients as $userId) {
        if ($userId !== (int) $actor['id']) {
            createInternalNotification($pdo, $userId, 'Nouveau document', 'Document ajoute : ' . $title, 'DOCUMENT', $documentId, true);
        }
    }
}

function notifyDocumentCommentRecipients(PDO $pdo, array $actor, array $document, int $documentId): void
{
    $recipients = [];
    if ($actor['role'] === 'CLIENT') {
        $recipients = array_merge($recipients, documentExpertIds($pdo), documentAssignedUserIds($pdo, $document['mission_id'] ? (int) $document['mission_id'] : null));
    } elseif ($actor['role'] === 'EXPERT') {
        $clientUserId = documentClientUserId($pdo, (int) $document['client_id']);
        if ($clientUserId !== null) { $recipients[] = $clientUserId; }
        $recipients = array_merge($recipients, documentAssignedUserIds($pdo, $document['mission_id'] ? (int) $document['mission_id'] : null));
    } else {
        $recipients = documentExpertIds($pdo);
    }
    foreach (array_unique($recipients) as $userId) {
        if ($userId !== (int) $actor['id']) {
            createInternalNotification($pdo, (int) $userId, 'Nouveau commentaire', 'Un commentaire a ete ajoute au document : ' . $document['title'], 'DOCUMENT', $documentId, true);
        }
    }
}

function documentExpertIds(PDO $pdo): array { $stmt=$pdo->prepare("SELECT id FROM users WHERE role = 'EXPERT'"); $stmt->execute(); return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id')); }
function documentAssignedUserIds(PDO $pdo, ?int $missionId): array { if ($missionId === null) { return []; } $stmt=$pdo->prepare('SELECT user_id FROM mission_assignments WHERE mission_id = :mission_id'); $stmt->execute(['mission_id'=>$missionId]); return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id')); }
function documentClientUserId(PDO $pdo, int $clientId): ?int { $stmt=$pdo->prepare('SELECT user_id FROM clients WHERE id = :id'); $stmt->execute(['id'=>$clientId]); $row=$stmt->fetch(PDO::FETCH_ASSOC); return $row && $row['user_id'] !== null ? (int) $row['user_id'] : null; }
function documentAudit(PDO $pdo, string $action, string $description): void { (new AuditLog($pdo))->log(['user_id'=>currentUserId(),'action'=>$action,'description'=>$description,'ip_address'=>$_SERVER['REMOTE_ADDR']??null]); }
function renderDocumentView(string $view, array $vars=[]): void { extract($vars, EXTR_SKIP); if (!defined('MNS_CONTROLLER_RENDER')) { define('MNS_CONTROLLER_RENDER', true); } require __DIR__ . '/../../frontend/views/documents/' . $view; }
