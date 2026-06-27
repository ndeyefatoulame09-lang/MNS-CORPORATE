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
    requireRole(['EXPERT']);
    $pdo = getDatabaseConnection();
    $clients = fetchClients($pdo);
    $missions = fetchMissions($pdo);
    renderDocumentView('upload.php', compact('clients', 'missions'));
}

function storeDocument(): void
{
    requireRole(['EXPERT']);
    documentPostOnly();
    $pdo = getDatabaseConnection();
    try {
        $fileData = handleSecureDocumentUpload($_FILES['document_file'] ?? []);
        $data = documentInput($_POST) + $fileData + ['uploaded_by' => currentUserId()];
        $errors = validateDocument($data);
        if ($errors) {
            setFlashMessage('error', implode(' ', $errors));
            redirect('/MNS_CORPORATE/frontend/views/documents/upload.php');
        }
        $id = (new Document($pdo))->create($data);
        notifyExperts($pdo, 'Nouveau document', 'Document ajoute : ' . $data['title'], $id);
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
    if (!$model->canAccess($user, $id)) { http_response_code(403); exit('Acces refuse'); }
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
function notifyExperts(PDO $pdo, string $title, string $message, int $relatedId): void { $stmt=$pdo->prepare("SELECT id FROM users WHERE role = :role"); $stmt->execute(['role'=>'EXPERT']); $users=$stmt->fetchAll(PDO::FETCH_ASSOC); $n=new Notification($pdo); foreach($users as $u){$n->createForUser((int)$u['id'], ['title'=>$title,'message'=>$message,'related_type'=>'DOCUMENT','related_id'=>$relatedId]);} }
function documentAudit(PDO $pdo, string $action, string $description): void { (new AuditLog($pdo))->log(['user_id'=>currentUserId(),'action'=>$action,'description'=>$description,'ip_address'=>$_SERVER['REMOTE_ADDR']??null]); }
function renderDocumentView(string $view, array $vars=[]): void { extract($vars, EXTR_SKIP); define('MNS_CONTROLLER_RENDER', true); require __DIR__ . '/../../frontend/views/documents/' . $view; }
