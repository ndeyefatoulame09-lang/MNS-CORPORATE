<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

/**
 * Modèle pour les feuilles de temps.
 */
class Timesheet extends BaseModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected ?int $missionId = null;
    protected float $hoursWorked = 0.0;
    protected ?string $workDate = null;
    protected ?string $description = null;
    protected string $status = 'SAISI';
    protected ?int $validatedBy = null;
    protected ?string $validatedAt = null;
    protected ?string $createdAt = null;

    public function __construct(PDO $db, array $data = [])
    {
        parent::__construct($db);
        $this->hydrate($data);
    }

    public function hydrate(array $data): void
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : null;
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : (isset($data['userId']) ? (int) $data['userId'] : null);
        $this->missionId = isset($data['mission_id']) ? (int) $data['mission_id'] : (isset($data['missionId']) ? (int) $data['missionId'] : null);
        $this->hoursWorked = isset($data['hours_worked']) ? (float) $data['hours_worked'] : (isset($data['hoursWorked']) ? (float) $data['hoursWorked'] : 0.0);
        $this->workDate = $data['work_date'] ?? $data['workDate'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->status = $data['status'] ?? 'SAISI';
        $this->validatedBy = isset($data['validated_by']) ? (int) $data['validated_by'] : (isset($data['validatedBy']) ? (int) $data['validatedBy'] : null);
        $this->validatedAt = $data['validated_at'] ?? $data['validatedAt'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'mission_id' => $this->missionId,
            'hours_worked' => $this->hoursWorked,
            'work_date' => $this->workDate,
            'description' => $this->description,
            'status' => $this->status,
            'validated_by' => $this->validatedBy,
            'validated_at' => $this->validatedAt,
            'created_at' => $this->createdAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `timesheets` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `timesheets`{$filterClause} ORDER BY `work_date` DESC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'user_id' => $data['user_id'] ?? $data['userId'] ?? null,
            'mission_id' => $data['mission_id'] ?? $data['missionId'] ?? null,
            'hours_worked' => isset($data['hours_worked']) ? (float) $data['hours_worked'] : (isset($data['hoursWorked']) ? (float) $data['hoursWorked'] : 0.0),
            'work_date' => $data['work_date'] ?? $data['workDate'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'SAISI',
            'validated_by' => $data['validated_by'] ?? $data['validatedBy'] ?? null,
            'validated_at' => $data['validated_at'] ?? $data['validatedAt'] ?? null,
        ];

        return $this->insert('timesheets', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['user_id']) || isset($data['userId'])) {
            $updateData['user_id'] = $data['user_id'] ?? $data['userId'];
        }

        if (isset($data['mission_id']) || isset($data['missionId'])) {
            $updateData['mission_id'] = $data['mission_id'] ?? $data['missionId'];
        }

        if (isset($data['hours_worked']) || isset($data['hoursWorked'])) {
            $updateData['hours_worked'] = $data['hours_worked'] ?? $data['hoursWorked'];
        }

        if (isset($data['work_date']) || isset($data['workDate'])) {
            $updateData['work_date'] = $data['work_date'] ?? $data['workDate'];
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (array_key_exists('validated_by', $data) || array_key_exists('validatedBy', $data)) {
            $updateData['validated_by'] = $data['validated_by'] ?? $data['validatedBy'];
        }

        if (array_key_exists('validated_at', $data) || array_key_exists('validatedAt', $data)) {
            $updateData['validated_at'] = $data['validated_at'] ?? $data['validatedAt'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('timesheets', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('timesheets', $id);
    }

    public function findByMission(int $missionId): array
    {
        return $this->fetchAll('SELECT * FROM `timesheets` WHERE `mission_id` = :mission_id ORDER BY `work_date` DESC', ['mission_id' => $missionId]);
    }

    public function findByUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM `timesheets` WHERE `user_id` = :user_id ORDER BY `work_date` DESC', ['user_id' => $userId]);
    }
}
