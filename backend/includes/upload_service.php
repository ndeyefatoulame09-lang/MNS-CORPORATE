<?php
declare(strict_types=1);

function handleSecureDocumentUpload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload impossible.');
    }

    if ((int) $file['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('Le fichier depasse 10 Mo.');
    }

    $originalName = basename((string) $file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Extension non autorisee.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Type MIME non autorise.');
    }

    $uploadDir = __DIR__ . '/../../frontend/assets/uploads/documents';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('Dossier upload indisponible.');
    }

    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $target = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        throw new RuntimeException('Enregistrement physique impossible.');
    }

    return [
        'original_filename' => $originalName,
        'stored_filename' => $storedName,
        'file_path' => 'frontend/assets/uploads/documents/' . $storedName,
        'file_type' => $mime,
        'file_size' => (int) $file['size'],
    ];
}
