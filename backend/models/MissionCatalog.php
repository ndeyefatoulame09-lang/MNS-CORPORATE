<?php
declare(strict_types=1);

/**
 * Modèle pour le catalogue des missions.
 */
class MissionCatalog extends BaseModel
{
    protected ?int $id = null;
    protected string $name = '';
    protected string $description = '';
    protected ?float $defaultRate = null;
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
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->defaultRate = isset($data['default_rate']) ? (float) $data['default_rate'] : (isset($data['defaultRate']) ? (float) $data['defaultRate'] : null);
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'default_rate' => $this->defaultRate,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `mission_catalogs` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `mission_catalogs`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'default_rate' => isset($data['default_rate']) ? (float) $data['default_rate'] : (isset($data['defaultRate']) ? (float) $data['defaultRate'] : null),
        ];

        return $this->insert('mission_catalogs', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['description'])) {
            $updateData['description'] = $data['description'];
        }

        if (isset($data['default_rate']) || isset($data['defaultRate'])) {
            $updateData['default_rate'] = isset($data['default_rate']) ? (float) $data['default_rate'] : (float) $data['defaultRate'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('mission_catalogs', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('mission_catalogs', $id);
    }
}
