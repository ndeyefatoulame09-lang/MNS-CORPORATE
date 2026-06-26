<?php
declare(strict_types=1);

/**
 * Modèle pour l'affectation des missions.
 */
class MissionAssignment extends BaseModel
{
    protected ?int $id = null;
    protected ?int $missionId = null;
    protected ?int $userId = null;
    protected string $role = '';
    protected string $status = 'active';
    protected ?string $assignedAt = null;
    protected ?string $createdAt = null;
    protected ?string $updatedAt = null;

    public function __construct(\PDO $db, array $data = [])
    {
        parent::__construct($db);
        $this->hydrate($data);
    }

    public function hydrate(array $data): void
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->missionId = isset($data['mission_id']) ? (int) $data['mission_id'] : (isset($data['missionId']) ? (int) $data['missionId'] : null);
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : (isset($data['userId']) ? (int) $data['userId'] : null);
        $this->role = $data['role'] ?? '';
        $this->status = $data['status'] ?? 'active';
        $this->assignedAt = $data['assigned_at'] ?? $data['assignedAt'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'mission_id' => $this->missionId,
            'user_id' => $this->userId,
            'role' => $this->role,
            'status' => $this->status,
            'assigned_at' => $this->assignedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `mission_assignments` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `mission_assignments`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'mission_id' => $data['mission_id'] ?? $data['missionId'] ?? null,
            'user_id' => $data['user_id'] ?? $data['userId'] ?? null,
            'role' => $data['role'] ?? '',
            'status' => $data['status'] ?? 'active',
            'assigned_at' => $data['assigned_at'] ?? $data['assignedAt'] ?? null,
        ];

        return $this->insert('mission_assignments', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['mission_id']) || isset($data['missionId'])) {
            $updateData['mission_id'] = $data['mission_id'] ?? $data['missionId'];
        }

        if (isset($data['user_id']) || isset($data['userId'])) {
            $updateData['user_id'] = $data['user_id'] ?? $data['userId'];
        }

        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['assigned_at']) || isset($data['assignedAt'])) {
            $updateData['assigned_at'] = $data['assigned_at'] ?? $data['assignedAt'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('mission_assignments', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('mission_assignments', $id);
    }

    public function findByMission(int $missionId): array
    {
        return $this->fetchAll('SELECT * FROM `mission_assignments` WHERE `mission_id` = :mission_id ORDER BY `id` ASC', ['mission_id' => $missionId]);
    }

    public function findByUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM `mission_assignments` WHERE `user_id` = :user_id ORDER BY `id` ASC', ['user_id' => $userId]);
    }
}
