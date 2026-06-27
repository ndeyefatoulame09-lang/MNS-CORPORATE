<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Mission extends BaseModel
{
    public const STATUSES = ['A_FAIRE', 'EN_COURS', 'TERMINEE', 'EN_RETARD', 'ANNULEE'];
    public const PRIORITIES = ['BASSE', 'MOYENNE', 'HAUTE'];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT m.*, c.company_name AS client_name, mc.name AS catalog_name, u.full_name AS creator_name
             FROM `missions` m
             INNER JOIN `clients` c ON c.id = m.client_id
             INNER JOIN `mission_catalog` mc ON mc.id = m.mission_catalog_id
             INNER JOIN `users` u ON u.id = m.created_by
             WHERE m.id = :id',
            ['id' => $id]
        );
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT m.*, c.company_name AS client_name, mc.name AS catalog_name
             FROM `missions` m
             INNER JOIN `clients` c ON c.id = m.client_id
             INNER JOIN `mission_catalog` mc ON mc.id = m.mission_catalog_id
             {$where}
             ORDER BY m.start_date DESC, m.id DESC
             LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $where = $this->buildWhere($filters, $params);
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM `missions` m
             INNER JOIN `clients` c ON c.id = m.client_id
             INNER JOIN `mission_catalog` mc ON mc.id = m.mission_catalog_id
             {$where}",
            $params
        );

        return $row === null ? 0 : (int) $row['total'];
    }

    public function create(array $data): int
    {
        return $this->insert('missions', [
            'client_id' => (int) $data['client_id'],
            'mission_catalog_id' => (int) $data['mission_catalog_id'],
            'title' => $data['title'] ?? '',
            'description' => $this->nullable($data['description'] ?? null),
            'start_date' => $data['start_date'] ?? null,
            'planned_end_date' => $this->nullable($data['planned_end_date'] ?? null),
            'actual_end_date' => $this->nullable($data['actual_end_date'] ?? null),
            'status' => $data['status'] ?? 'A_FAIRE',
            'priority' => $data['priority'] ?? 'MOYENNE',
            'estimated_hours' => $this->nullable($data['estimated_hours'] ?? null),
            'created_by' => (int) $data['created_by'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateRecord('missions', $id, [
            'client_id' => (int) $data['client_id'],
            'mission_catalog_id' => (int) $data['mission_catalog_id'],
            'title' => $data['title'] ?? '',
            'description' => $this->nullable($data['description'] ?? null),
            'start_date' => $data['start_date'] ?? null,
            'planned_end_date' => $this->nullable($data['planned_end_date'] ?? null),
            'actual_end_date' => $this->nullable($data['actual_end_date'] ?? null),
            'status' => $data['status'] ?? 'A_FAIRE',
            'priority' => $data['priority'] ?? 'MOYENNE',
            'estimated_hours' => $this->nullable($data['estimated_hours'] ?? null),
        ]);
    }

    public function updateStatus(int $id, string $status, ?string $actualEndDate): bool
    {
        return $this->updateRecord('missions', $id, [
            'status' => $status,
            'actual_end_date' => $actualEndDate,
        ]);
    }

    public function existsClient(int $clientId, bool $activeOnly = false): bool
    {
        $sql = 'SELECT id FROM `clients` WHERE `id` = :id';
        if ($activeOnly) {
            $sql .= " AND `status` = 'ACTIF'";
        }

        return $this->fetchOne($sql, ['id' => $clientId]) !== null;
    }

    public function existsCatalog(int $catalogId, bool $activeOnly = false): bool
    {
        $sql = 'SELECT id FROM `mission_catalog` WHERE `id` = :id';
        if ($activeOnly) {
            $sql .= ' AND `is_active` = 1';
        }

        return $this->fetchOne($sql, ['id' => $catalogId]) !== null;
    }

    public function getActiveClients(): array
    {
        return $this->fetchAll(
            "SELECT id, company_name FROM `clients` WHERE `status` = 'ACTIF' ORDER BY `company_name` ASC"
        );
    }

    public function getActiveCatalogItems(): array
    {
        return $this->fetchAll(
            'SELECT id, name FROM `mission_catalog` WHERE `is_active` = 1 ORDER BY `name` ASC'
        );
    }

    private function buildWhere(array $filters, array &$params): string
    {
        $where = [];

        if (($filters['q'] ?? '') !== '') {
            $where[] = '(m.title LIKE :q OR m.description LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        foreach (['client_id', 'mission_catalog_id', 'status', 'priority'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = "m.`{$field}` = :{$field}";
                $params[$field] = $filters[$field];
            }
        }

        if (($filters['start_from'] ?? '') !== '') {
            $where[] = 'm.start_date >= :start_from';
            $params['start_from'] = $filters['start_from'];
        }

        if (($filters['start_to'] ?? '') !== '') {
            $where[] = 'm.start_date <= :start_to';
            $params['start_to'] = $filters['start_to'];
        }

        if (($filters['assigned_user_id'] ?? '') !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM mission_assignments ma WHERE ma.mission_id = m.id AND ma.user_id = :assigned_user_id)';
            $params['assigned_user_id'] = $filters['assigned_user_id'];
        }

        if (($filters['visible_client_id'] ?? '') !== '') {
            $where[] = 'm.client_id = :visible_client_id';
            $params['visible_client_id'] = $filters['visible_client_id'];
        }

        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }

    private function nullable(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
