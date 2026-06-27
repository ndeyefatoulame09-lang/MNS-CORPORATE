<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class MissionCatalog extends BaseModel
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `mission_catalog` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `mission_catalog`{$where} ORDER BY `name` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $row = $this->fetchOne("SELECT COUNT(*) AS total FROM `mission_catalog`{$where}", $params);

        return $row === null ? 0 : (int) $row['total'];
    }

    public function create(array $data): int
    {
        return $this->insert('mission_catalog', [
            'name' => $data['name'] ?? '',
            'description' => $this->nullable($data['description'] ?? null),
            'default_duration_days' => $this->nullable($data['default_duration_days'] ?? null),
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateRecord('mission_catalog', $id, [
            'name' => $data['name'] ?? '',
            'description' => $this->nullable($data['description'] ?? null),
            'default_duration_days' => $this->nullable($data['default_duration_days'] ?? null),
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
    }

    public function setActiveStatus(int $id, bool $isActive): bool
    {
        return $this->updateRecord('mission_catalog', $id, ['is_active' => $isActive ? 1 : 0]);
    }

    public function existsByName(string $name, int $excludeId = 0): bool
    {
        $sql = 'SELECT id FROM `mission_catalog` WHERE `name` = :name';
        $params = ['name' => $name];

        if ($excludeId > 0) {
            $sql .= ' AND `id` <> :id';
            $params['id'] = $excludeId;
        }

        return $this->fetchOne($sql, $params) !== null;
    }

    private function buildWhere(array $filters, array &$params): string
    {
        $where = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '`name` LIKE :q';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        if (($filters['is_active'] ?? '') !== '') {
            $where[] = '`is_active` = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    private function nullable(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
