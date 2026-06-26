<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_check.php';

/**
 * Vérifie que l'utilisateur connecté possède un rôle autorisé.
 */
function requireRole(array $allowedRoles): void
{
    requireAuth();

    $user = currentUser();
    $role = $user['role'] ?? '';

    if (!hasRole($role) || !in_array($role, $allowedRoles, true)) {
        setFlashMessage('error', 'Vous n’avez pas les droits pour accéder à cette page.');
        redirect('/MNS_CORPORATE/index.php');
    }
}

/**
 * Vérifie si un ou plusieurs rôles sont valides et correspondent au rôle actuel.
 */
function hasRole(string ...$roles): bool
{
    $validRoles = [
        'EXPERT',
        'COLLABORATEUR',
        'STAGIAIRE',
        'CLIENT',
    ];

    foreach ($roles as $role) {
        if (!in_array($role, $validRoles, true)) {
            return false;
        }
    }

    $user = currentUser();
    if ($user === null || !isset($user['role']) || !is_string($user['role'])) {
        return false;
    }

    return in_array($user['role'], $roles, true);
}
