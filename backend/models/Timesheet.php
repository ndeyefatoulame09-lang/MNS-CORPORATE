<?php
declare(strict_types=1);

/**
 * Modèle pour les feuilles de temps.
 */
class Timesheet extends BaseModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected ?int $missionId = null;
    protected float $hours = 0.0;
    protected ?string $entryDate = null;
    protected ?string $description = null;
    protected ?float $rate = null;
    protected ?float $total = null;
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
        $this->missionId = isset($data['mission_id']) ? (int) $data['mission_id'] : (isset($data['missionId']) ? (int) $data['missionId'] : null);
        $this->hours = isset($data['hours']) ? (float) $data['hours'] : 0.0;
        $this->entryDate = $data['entry_date'] ?? $data['entryDate'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->rate = isset($data['rate']) ? (float) $data['rate'] : null;
        $this->total = isset($data['total']) ? (float) $data['total'] : null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'mission_id' => $this->missionId,
            'hours' => $this->hours,
            'entry_date' => $this->entryDate,
            'description' => $this->description,
            'rate' => $this->rate,
            'total' => $this->total,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
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
            "SELECT * FROM `timesheets`{$filterClause} ORDER BY `entry_date` DESC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'user_id' => $data['user_id'] ?? $data['userId'] ?? null,
            'mission_id' => $data['mission_id'] ?? $data['missionId'] ?? null,
            'hours' => isset($data['hours']) ? (float) $data['hours'] : 0.0,
            'entry_date' => $data['entry_date'] ?? $data['entryDate'] ?? null,
            'description' => $data['description'] ?? null,
            'rate' => isset($data['rate']) ? (float) $data['rate'] : null,
            'total' => isset($data['total']) ? (float) $data['total'] : null,
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

        if (isset($data['hours'])) {
            $updateData['hours'] = (float) $data['hours'];
        }

        if (isset($data['entry_date']) || isset($data['entryDate'])) {
            $updateData['entry_date'] = $data['entry_date'] ?? $data['entryDate'];
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['rate'])) {
            $updateData['rate'] = (float) $data['rate'];
        }

        if (isset($data['total'])) {
            $updateData['total'] = (float) $data['total'];
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
        return $this->fetchAll('SELECT * FROM `timesheets` WHERE `mission_id` = :mission_id ORDER BY `entry_date` DESC', ['mission_id' => $missionId]);
    }

    public function findByUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM `timesheets` WHERE `user_id` = :user_id ORDER BY `entry_date` DESC', ['user_id' => $userId]);
    }
}
