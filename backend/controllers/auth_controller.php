<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

function handleLogin(): void
{
    startSecureSession();

    $errors = [];
    $email = '';

    if (!isPostRequest()) {
        require_once __DIR__ . '/../../frontend/views/auth/login_view.php';
        return;
    }

    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '') {
        $errors[] = 'Email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }

    if ($password === '') {
        $errors[] = 'Mot de passe est requis.';
    }

    if (!empty($errors)) {
        $_SESSION['old_input'] = ['email' => $email];
        setFlashMessage('error', 'Identifiants invalides.');
        require_once __DIR__ . '/../../frontend/views/auth/login_view.php';
        return;
    }

    try {
        $pdo = getDatabaseConnection();
        $userModel = new User($pdo);
        $user = $userModel->findByEmail($email);
    } catch (Throwable $exception) {
        setFlashMessage('error', 'Une erreur est survenue.');
        require_once __DIR__ . '/../../frontend/views/auth/login_view.php';
        return;
    }

    if (
        $user === null ||
        !isset($user['id'], $user['full_name'], $user['email'], $user['role'], $user['password_hash'], $user['is_active']) ||
        !password_verify($password, $user['password_hash']) ||
        (int) $user['is_active'] === 0
    ) {
        $_SESSION['old_input'] = ['email' => $email];
        setFlashMessage('error', 'Identifiants invalides.');
        require_once __DIR__ . '/../../frontend/views/auth/login_view.php';
        return;
    }

    regenerateSession();

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => (string) $user['full_name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];

    try {
        $userModel->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        $auditLog = new AuditLog($pdo);
        $auditLog->log([
            'user_id' => (int) $user['id'],
            'action' => 'login',
            'description' => 'Connexion réussie',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $exception) {
        // Ne pas bloquer la connexion en cas d'échec du logging.
    }

    redirect('/MNS_CORPORATE/index.php');
}

function handleLogout(): void
{
    startSecureSession();

    if (isset($_SESSION['user']['id'])) {
        try {
            $pdo = getDatabaseConnection();
            $auditLog = new AuditLog($pdo);
            $auditLog->log([
                'user_id' => (int) $_SESSION['user']['id'],
                'action' => 'logout',
                'description' => 'Déconnexion utilisateur',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $exception) {
            // Ne pas bloquer la déconnexion en cas de problème de logging.
        }
    }

    destroySession();
    startSecureSession();
    setFlashMessage('success', 'Vous êtes bien déconnecté.');
    redirect('/MNS_CORPORATE/login.php');
}
