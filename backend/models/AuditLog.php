<?php
declare(strict_types=1);

/**
 * Modèle pour les journaux d'audit.
 */
class AuditLog extends BaseModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected string $action = '';
    protected ?string $description = null;
    protected ?string $ipAddress = null;
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
        $this->action = $data['action'] ?? '';
        $this->description = $data['description'] ?? $data['details'] ?? null;
        $this->ipAddress = $data['ip_address'] ?? $data['ipAddress'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'action' => $this->action,
            'description' => $this->description,
            'ip_address' => $this->ipAddress,
            'created_at' => $this->createdAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `audit_logs` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `audit_logs`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'user_id' => $data['user_id'] ?? $data['userId'] ?? null,
            'action' => $data['action'] ?? '',
            'description' => $data['description'] ?? $data['details'] ?? null,
            'ip_address' => $data['ip_address'] ?? $data['ipAddress'] ?? null,
        ];

        return $this->insert('audit_logs', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['user_id']) || isset($data['userId'])) {
            $updateData['user_id'] = $data['user_id'] ?? $data['userId'];
        }

        if (isset($data['action'])) {
            $updateData['action'] = $data['action'];
        }

        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'];
        } elseif (array_key_exists('details', $data)) {
            $updateData['description'] = $data['details'];
        }

        if (isset($data['ip_address']) || isset($data['ipAddress'])) {
            $updateData['ip_address'] = $data['ip_address'] ?? $data['ipAddress'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('audit_logs', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('audit_logs', $id);
    }

    public function log(array $data): bool
    {
        $insertId = $this->create($data);

        return $insertId > 0;
    }
}
