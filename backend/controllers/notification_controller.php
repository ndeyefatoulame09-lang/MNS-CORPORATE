<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/mail_service.php';

const NOTIFICATION_LIST_URL = '/MNS_CORPORATE/frontend/views/notifications/list.php';

function handleNotificationRequest(): void
{
    startSecureSession();
    requireAuth();
    $action = $_GET['action'] ?? 'list';
    if ($action === 'read') {
        markNotificationRead((int) ($_POST['id'] ?? 0));
    } elseif ($action === 'read_all') {
        markAllNotificationsRead();
    } else {
        listNotifications();
    }
}

function listNotifications(): void
{
    $model = new Notification(getDatabaseConnection());
    $userId = currentUserId();
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = 20;
    $notifications = $model->findByUser((int) $userId, $perPage, ($page - 1) * $perPage);
    $total = $model->countByUser((int) $userId);
    $unreadCount = $model->findUnreadCountByUser((int) $userId);
    renderNotificationView('list.php', compact('notifications', 'total', 'unreadCount', 'page', 'perPage'));
}

function markNotificationRead(int $id): void
{
    requireAuth();
    if (!isPostRequest()) {
        redirect(NOTIFICATION_LIST_URL);
    }
    $pdo = getDatabaseConnection();
    (new Notification($pdo))->markAsRead($id, (int) currentUserId());
    notificationAudit($pdo, 'LECTURE_NOTIFICATION', 'Lecture notification #' . $id);
    redirect(NOTIFICATION_LIST_URL);
}

function markAllNotificationsRead(): void
{
    requireAuth();
    if (!isPostRequest()) {
        redirect(NOTIFICATION_LIST_URL);
    }
    $pdo = getDatabaseConnection();
    (new Notification($pdo))->markAllAsRead((int) currentUserId());
    notificationAudit($pdo, 'LECTURE_TOUTES_NOTIFICATIONS', 'Lecture de toutes les notifications');
    redirect(NOTIFICATION_LIST_URL);
}

function createInternalNotification(PDO $pdo, int $userId, string $title, string $message, ?string $relatedType = null, ?int $relatedId = null, bool $tryEmail = false): int
{
    $id = (new Notification($pdo))->createForUser($userId, [
        'title' => $title,
        'message' => $message,
        'channel' => 'INTERNE',
        'status' => 'A_ENVOYER',
        'related_type' => $relatedType,
        'related_id' => $relatedId,
    ]);
    notificationAudit($pdo, 'CREATION_NOTIFICATION', 'Notification #' . $id . ' pour utilisateur #' . $userId);

    if ($tryEmail && MAIL_ENABLED) {
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = :id');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            sendNotificationEmail((string) $user['email'], $title, nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')));
            notificationAudit($pdo, 'ENVOI_EMAIL_NOTIFICATION', 'Tentative email notification #' . $id);
        }
    }

    return $id;
}

function notificationAudit(PDO $pdo, string $action, string $description): void
{
    (new AuditLog($pdo))->log([
        'user_id' => currentUserId(),
        'action' => $action,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function renderNotificationView(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    if (!defined('MNS_CONTROLLER_RENDER')) {
        define('MNS_CONTROLLER_RENDER', true);
    }
    require __DIR__ . '/../../frontend/views/notifications/' . $view;
}
