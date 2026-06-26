<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';

/**
 * Vérifie qu'un utilisateur est authentifié.
 */
function requireAuth(): void
{
    startSecureSession();

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        setFlashMessage('error', 'Vous devez être connecté pour accéder à cette page.');
        redirect('/MNS_CORPORATE/login.php');
    }

    $user = $_SESSION['user'];

    if (
        !isset($user['id'], $user['full_name'], $user['email'], $user['role']) ||
        !is_int($user['id']) ||
        !is_string($user['full_name']) ||
        !is_string($user['email']) ||
        !is_string($user['role'])
    ) {
        setFlashMessage('error', 'Vous devez être connecté pour accéder à cette page.');
        redirect('/MNS_CORPORATE/login.php');
    }
}

/**
 * Retourne les données de l'utilisateur connecté ou null.
 */
function currentUser(): ?array
{
    startSecureSession();

    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    return null;
}

/**
 * Retourne l'identifiant de l'utilisateur connecté ou null.
 */
function currentUserId(): ?int
{
    $user = currentUser();

    if ($user !== null && isset($user['id']) && is_int($user['id'])) {
        return $user['id'];
    }

    return null;
}
