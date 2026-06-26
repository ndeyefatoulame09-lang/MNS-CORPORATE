<?php
declare(strict_types=1);

/**
 * Modèle pour les commentaires.
 */
class Comment extends BaseModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected ?int $missionId = null;
    protected string $content = '';
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
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : (isset($data['userId']) ? (int) $data['userId'] : null);
        $this->missionId = isset($data['mission_id']) ? (int) $data['mission_id'] : (isset($data['missionId']) ? (int) $data['missionId'] : null);
        $this->content = $data['content'] ?? '';
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'mission_id' => $this->missionId,
            'content' => $this->content,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `comments` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `comments`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'user_id' => $data['user_id'] ?? $data['userId'] ?? null,
            'mission_id' => $data['mission_id'] ?? $data['missionId'] ?? null,
            'content' => $data['content'] ?? '',
        ];

        return $this->insert('comments', $insertData);
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

        if (isset($data['content'])) {
            $updateData['content'] = $data['content'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('comments', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('comments', $id);
    }

    public function findByMission(int $missionId): array
    {
        return $this->fetchAll('SELECT * FROM `comments` WHERE `mission_id` = :mission_id ORDER BY `id` ASC', ['mission_id' => $missionId]);
    }

    public function findByUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM `comments` WHERE `user_id` = :user_id ORDER BY `id` ASC', ['user_id' => $userId]);
    }
}
