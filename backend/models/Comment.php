<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Comment extends BaseModel
{
    public function findByDocument(int $documentId): array
    {
        return $this->fetchAll(
            'SELECT c.*, u.full_name FROM comments c INNER JOIN users u ON u.id = c.user_id WHERE c.document_id = :document_id ORDER BY c.created_at ASC',
            ['document_id' => $documentId]
        );
    }

    public function create(array $data): int
    {
        return $this->insert('comments', [
            'document_id' => (int) $data['document_id'],
            'user_id' => (int) $data['user_id'],
            'message' => $data['message'] ?? '',
        ]);
    }
}
