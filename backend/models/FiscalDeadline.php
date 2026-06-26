<?php
declare(strict_types=1);

/**
 * Modèle pour les échéances fiscales.
 */
class FiscalDeadline extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected ?int $id = null;
    protected ?int $clientId = null;
    protected string $title = '';
    protected string $description = '';
    protected string $status = self::STATUS_PENDING;
    protected ?string $dueDate = null;
    protected ?bool $isCompleted = null;
    protected ?string $completedAt = null;
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
        $this->clientId = isset($data['client_id']) ? (int) $data['client_id'] : (isset($data['clientId']) ? (int) $data['clientId'] : null);
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_PENDING;
        $this->dueDate = $data['due_date'] ?? $data['dueDate'] ?? null;
        $this->isCompleted = isset($data['is_completed']) ? (bool) $data['is_completed'] : (isset($data['isCompleted']) ? (bool) $data['isCompleted'] : null);
        $this->completedAt = $data['completed_at'] ?? $data['completedAt'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'is_completed' => $this->isCompleted,
            'completed_at' => $this->completedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `fiscal_deadlines` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `fiscal_deadlines`{$filterClause} ORDER BY `due_date` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'client_id' => $data['client_id'] ?? $data['clientId'] ?? null,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'due_date' => $data['due_date'] ?? $data['dueDate'] ?? null,
            'is_completed' => isset($data['is_completed']) ? (bool) $data['is_completed'] : (isset($data['isCompleted']) ? (bool) $data['isCompleted'] : false),
            'completed_at' => $data['completed_at'] ?? $data['completedAt'] ?? null,
        ];

        return $this->insert('fiscal_deadlines', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['client_id']) || isset($data['clientId'])) {
            $updateData['client_id'] = $data['client_id'] ?? $data['clientId'];
        }

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['due_date']) || isset($data['dueDate'])) {
            $updateData['due_date'] = $data['due_date'] ?? $data['dueDate'];
        }

        if (array_key_exists('is_completed', $data) || array_key_exists('isCompleted', $data)) {
            $updateData['is_completed'] = isset($data['is_completed']) ? (bool) $data['is_completed'] : (isset($data['isCompleted']) ? (bool) $data['isCompleted'] : null);
        }

        if (isset($data['completed_at']) || isset($data['completedAt'])) {
            $updateData['completed_at'] = $data['completed_at'] ?? $data['completedAt'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('fiscal_deadlines', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('fiscal_deadlines', $id);
    }

    public function findUpcoming(int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT * FROM `fiscal_deadlines` WHERE `due_date` > NOW() AND `status` = :status ORDER BY `due_date` ASC LIMIT :limit',
            ['status' => self::STATUS_PENDING, 'limit' => $limit]
        );
    }

    public function findOverdue(int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT * FROM `fiscal_deadlines` WHERE `due_date` < NOW() AND `status` != :status ORDER BY `due_date` ASC LIMIT :limit',
            ['status' => self::STATUS_COMPLETED, 'limit' => $limit]
        );
    }

    public function markAsCompleted(int $id): bool
    {
        return $this->updateRecord('fiscal_deadlines', $id, [
            'status' => self::STATUS_COMPLETED,
            'is_completed' => true,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
