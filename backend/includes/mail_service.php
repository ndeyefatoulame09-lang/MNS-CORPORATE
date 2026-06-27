<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/mail_config.php';

function sendNotificationEmail(string $to, string $subject, string $htmlMessage): bool
{
    if (!MAIL_ENABLED || $to === '') {
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
    ];

    try {
        return @mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
    } catch (Throwable $exception) {
        return false;
    }
}
