<?php
declare(strict_types=1);

/**
 * Modèle pour les missions.
 */
class Mission extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected ?int $id = null;
    protected ?int $clientId = null;
    protected ?int $assignedUserId = null;
    protected string $title = '';
    protected string $description = '';
    protected string $status = self::STATUS_PENDING;
    protected ?float $rate = null;
    protected ?float $estimatedHours = null;
    protected ?string $startDate = null;
    protected ?string $endDate = null;
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
        $this->assignedUserId = isset($data['assigned_user_id']) ? (int) $data['assigned_user_id'] : (isset($data['assignedUserId']) ? (int) $data['assignedUserId'] : null);
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->status = $data['status'] ?? self::STATUS_PENDING;
        $this->rate = isset($data['rate']) ? (float) $data['rate'] : null;
        $this->estimatedHours = isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : (isset($data['estimatedHours']) ? (float) $data['estimatedHours'] : null);
        $this->startDate = $data['start_date'] ?? $data['startDate'] ?? null;
        $this->endDate = $data['end_date'] ?? $data['endDate'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'assigned_user_id' => $this->assignedUserId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'rate' => $this->rate,
            'estimated_hours' => $this->estimatedHours,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `missions` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `missions`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'client_id' => $data['client_id'] ?? $data['clientId'] ?? null,
            'assigned_user_id' => $data['assigned_user_id'] ?? $data['assignedUserId'] ?? null,
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'rate' => isset($data['rate']) ? (float) $data['rate'] : null,
            'estimated_hours' => isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : (isset($data['estimatedHours']) ? (float) $data['estimatedHours'] : null),
            'start_date' => $data['start_date'] ?? $data['startDate'] ?? null,
            'end_date' => $data['end_date'] ?? $data['endDate'] ?? null,
        ];

        return $this->insert('missions', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['client_id']) || isset($data['clientId'])) {
            $updateData['client_id'] = $data['client_id'] ?? $data['clientId'];
        }

        if (isset($data['assigned_user_id']) || isset($data['assignedUserId'])) {
            $updateData['assigned_user_id'] = $data['assigned_user_id'] ?? $data['assignedUserId'];
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

        if (isset($data['rate'])) {
            $updateData['rate'] = (float) $data['rate'];
        }

        if (isset($data['estimated_hours']) || isset($data['estimatedHours'])) {
            $updateData['estimated_hours'] = $data['estimated_hours'] ?? $data['estimatedHours'];
        }

        if (isset($data['start_date']) || isset($data['startDate'])) {
            $updateData['start_date'] = $data['start_date'] ?? $data['startDate'];
        }

        if (isset($data['end_date']) || isset($data['endDate'])) {
            $updateData['end_date'] = $data['end_date'] ?? $data['endDate'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('missions', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('missions', $id);
    }

    public function findByClient(int $clientId): array
    {
        return $this->fetchAll('SELECT * FROM `missions` WHERE `client_id` = :client_id ORDER BY `id` ASC', ['client_id' => $clientId]);
    }

    public function findByAssignedUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM `missions` WHERE `assigned_user_id` = :assigned_user_id ORDER BY `id` ASC', ['assigned_user_id' => $userId]);
    }

    public function getClient(): ?array
    {
        if ($this->clientId === null) {
            return null;
        }

        return $this->fetchOne('SELECT * FROM `clients` WHERE `id` = :id', ['id' => $this->clientId]);
    }

    public function getAssignedUser(): ?array
    {
        if ($this->assignedUserId === null) {
            return null;
        }

        return $this->fetchOne('SELECT * FROM `users` WHERE `id` = :id', ['id' => $this->assignedUserId]);
    }

    public function getDocuments(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `documents` WHERE `mission_id` = :mission_id ORDER BY `id` ASC', ['mission_id' => $this->id]);
    }

    public function getComments(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `comments` WHERE `mission_id` = :mission_id ORDER BY `created_at` DESC', ['mission_id' => $this->id]);
    }

    public function getTimesheets(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `timesheets` WHERE `mission_id` = :mission_id ORDER BY `entry_date` DESC', ['mission_id' => $this->id]);
    }

    public function getAssignments(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `mission_assignments` WHERE `mission_id` = :mission_id ORDER BY `id` ASC', ['mission_id' => $this->id]);
    }
}
