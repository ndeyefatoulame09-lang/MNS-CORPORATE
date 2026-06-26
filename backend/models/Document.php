<?php
declare(strict_types=1);

/**
 * Modèle pour les documents.
 */
class Document extends BaseModel
{
    protected ?int $id = null;
    protected ?int $clientId = null;
    protected ?int $missionId = null;
    protected string $title = '';
    protected string $filePath = '';
    protected string $fileType = '';
    protected ?string $uploadedAt = null;
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
        $this->missionId = isset($data['mission_id']) ? (int) $data['mission_id'] : (isset($data['missionId']) ? (int) $data['missionId'] : null);
        $this->title = $data['title'] ?? '';
        $this->filePath = $data['file_path'] ?? $data['filePath'] ?? '';
        $this->fileType = $data['file_type'] ?? $data['fileType'] ?? '';
        $this->uploadedAt = $data['uploaded_at'] ?? $data['uploadedAt'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'mission_id' => $this->missionId,
            'title' => $this->title,
            'file_path' => $this->filePath,
            'file_type' => $this->fileType,
            'uploaded_at' => $this->uploadedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `documents` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `documents`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'client_id' => $data['client_id'] ?? $data['clientId'] ?? null,
            'mission_id' => $data['mission_id'] ?? $data['missionId'] ?? null,
            'title' => $data['title'] ?? '',
            'file_path' => $data['file_path'] ?? $data['filePath'] ?? '',
            'file_type' => $data['file_type'] ?? $data['fileType'] ?? '',
            'uploaded_at' => $data['uploaded_at'] ?? $data['uploadedAt'] ?? null,
        ];

        return $this->insert('documents', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['client_id']) || isset($data['clientId'])) {
            $updateData['client_id'] = $data['client_id'] ?? $data['clientId'];
        }

        if (isset($data['mission_id']) || isset($data['missionId'])) {
            $updateData['mission_id'] = $data['mission_id'] ?? $data['missionId'];
        }

        if (isset($data['title'])) {
            $updateData['title'] = $data['title'];
        }

        if (isset($data['file_path']) || isset($data['filePath'])) {
            $updateData['file_path'] = $data['file_path'] ?? $data['filePath'];
        }

        if (isset($data['file_type']) || isset($data['fileType'])) {
            $updateData['file_type'] = $data['file_type'] ?? $data['fileType'];
        }

        if (isset($data['uploaded_at']) || isset($data['uploadedAt'])) {
            $updateData['uploaded_at'] = $data['uploaded_at'] ?? $data['uploadedAt'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('documents', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('documents', $id);
    }

    public function findByClient(int $clientId): array
    {
        return $this->fetchAll('SELECT * FROM `documents` WHERE `client_id` = :client_id ORDER BY `id` ASC', ['client_id' => $clientId]);
    }

    public function findByMission(int $missionId): array
    {
        return $this->fetchAll('SELECT * FROM `documents` WHERE `mission_id` = :mission_id ORDER BY `id` ASC', ['mission_id' => $missionId]);
    }

    public function getClient(): ?array
    {
        if ($this->clientId === null) {
            return null;
        }

        return $this->fetchOne('SELECT * FROM `clients` WHERE `id` = :id', ['id' => $this->clientId]);
    }

    public function getMission(): ?array
    {
        if ($this->missionId === null) {
            return null;
        }

        return $this->fetchOne('SELECT * FROM `missions` WHERE `id` = :id', ['id' => $this->missionId]);
    }
}
