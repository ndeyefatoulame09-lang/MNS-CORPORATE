<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Notification extends BaseModel
{
    public const CHANNELS = ['EMAIL', 'SMS', 'INTERNE'];
    public const STATUSES = ['A_ENVOYER', 'ENVOYEE', 'LUE', 'ECHEC'];

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM notifications WHERE id = :id', ['id' => $id]);
    }

    public function findByUser(int $userId, int $limit = 20, int $offset = 0): array
    {
        return $this->fetchAll(
            'SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset',
            ['user_id' => $userId, 'limit' => $limit, 'offset' => $offset]
        );
    }

    public function countByUser(int $userId): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id', ['user_id' => $userId]);
        return $row === null ? 0 : (int) $row['total'];
    }

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
            'sent_at' => $data['sent_at'] ?? null,
        ]);
    }

    public function createForUser(int $userId, array $data): int
    {
        $data['user_id'] = $userId;
        return $this->create($data);
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE notifications SET status = 'LUE' WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE notifications SET status = 'LUE' WHERE user_id = :user_id AND status <> 'LUE'");
        return $stmt->execute(['user_id' => $userId]);
    }

    public function findUnreadCountByUser(int $userId): int
    {
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id AND status <> 'LUE'", ['user_id' => $userId]);
        return $row === null ? 0 : (int) $row['total'];
    }
}
