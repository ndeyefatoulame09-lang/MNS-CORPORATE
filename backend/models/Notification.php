<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Notification extends BaseModel
{
    public function create(array $data): int
    {
        return $this->insert('notifications', [
            'user_id' => (int) $data['user_id'],
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
            'channel' => $data['channel'] ?? 'INTERNE',
            'status' => $data['status'] ?? 'A_ENVOYER',
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
        ]);
    }

    public function createForUser(int $userId, array $data): int
    {
        $data['user_id'] = $userId;
        return $this->create($data);
    }
}
