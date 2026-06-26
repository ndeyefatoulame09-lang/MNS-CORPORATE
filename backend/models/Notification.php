<?php
declare(strict_types=1);

/**
 * Modèle pour les notifications utilisateurs.
 */
class Notification extends BaseModel
{
    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';

    protected ?int $id = null;
    protected ?int $userId = null;
    protected string $title = '';
    protected string $message = '';
    protected string $status = self::STATUS_UNREAD;
    protected ?string $readAt = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;

    public function __construct(PDO $db, array $data = [])
    {
        parent::__construct($db);
        $this->hydrate($data);
    }

    public function hydrate(array $data): void
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : (isset($data['userId']) ? (int) $data['userId'] : null);
        $this->title = $data['title'] ?? '';
        $this->message = $data['message'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_UNREAD;
        $this->readAt = $data['read_at'] ?? $data['readAt'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `notifications` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `notifications`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'user_id' => $data['user_id'] ?? $data['userId'] ?? null,
            'title' => $data['title'] ?? '',
            'message' => $data['message'] ?? '',
            'status' => $data['status'] ?? self::STATUS_UNREAD,
            'read_at' => $data['read_at'] ?? $data['readAt'] ?? null,
        ];

        return $this->insert('notifications', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['user_id']) || isset($data['userId'])) {
            $updateData['user_id'] = $data['user_id'] ?? $data['userId'];
        }

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (isset($data['message'])) {
            $updateData['message'] = $data['message'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['read_at']) || isset($data['readAt'])) {
            $updateData['read_at'] = $data['read_at'] ?? $data['readAt'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('notifications', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('notifications', $id);
    }

    public function createForUser(int $userId, array $data): int
    {
        $data['user_id'] = $userId;

        return $this->create($data);
    }

    public function markAsRead(int $id): bool
    {
        return $this->updateRecord('notifications', $id, [
            'status' => self::STATUS_READ,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
