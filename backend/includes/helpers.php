<?php
declare(strict_types=1);

/**
 * Échappe une valeur pour l'injection HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8', false);
}

/**
 * Redirige vers un chemin donné et termine l'exécution.
 *
 * @param string $path Chemin de redirection relatif ou absolu.
 * @return never
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Définit un message flash stocké en session.
 */
function setFlashMessage(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        startSecureSession();
    }

    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Lit et supprime le message flash en session.
 */
function getFlashMessage(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        startSecureSession();
    }

    if (isset($_SESSION['flash_message']) && is_array($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }

    return null;
}

/**
 * Retourne la valeur d'un champ anciennement soumis.
 */
function old(string $key, string $default = ''): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        startSecureSession();
    }

    if (isset($_SESSION['old_input']) && is_array($_SESSION['old_input'])) {
        return array_key_exists($key, $_SESSION['old_input']) ? (string) $_SESSION['old_input'][$key] : $default;
    }

    return $default;
}

/**
 * Indique si la requête HTTP est de type POST.
 */
function isPostRequest(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
