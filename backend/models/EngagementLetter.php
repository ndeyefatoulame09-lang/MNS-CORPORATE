<?php
declare(strict_types=1);

/**
 * Modèle pour les lettres de mission.
 */
class EngagementLetter extends BaseModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SIGNED = 'signed';

    protected ?int $id = null;
    protected ?int $clientId = null;
    protected ?int $missionId = null;
    protected string $status = self::STATUS_PENDING;
    protected string $documentPath = '';
    protected ?string $signedAt = null;
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
        $this->status = $data['status'] ?? self::STATUS_PENDING;
        $this->documentPath = $data['document_path'] ?? $data['documentPath'] ?? '';
        $this->signedAt = $data['signed_at'] ?? $data['signedAt'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'mission_id' => $this->missionId,
            'status' => $this->status,
            'document_path' => $this->documentPath,
            'signed_at' => $this->signedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `engagement_letters` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `engagement_letters`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'client_id' => $data['client_id'] ?? $data['clientId'] ?? null,
            'mission_id' => $data['mission_id'] ?? $data['missionId'] ?? null,
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'document_path' => $data['document_path'] ?? $data['documentPath'] ?? '',
            'signed_at' => $data['signed_at'] ?? $data['signedAt'] ?? null,
        ];

        return $this->insert('engagement_letters', $insertData);
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

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['document_path']) || isset($data['documentPath'])) {
            $updateData['document_path'] = $data['document_path'] ?? $data['documentPath'];
        }

        if (isset($data['signed_at']) || isset($data['signedAt'])) {
            $updateData['signed_at'] = $data['signed_at'] ?? $data['signedAt'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('engagement_letters', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('engagement_letters', $id);
    }

    public function markAsSigned(int $id): bool
    {
        return $this->updateRecord('engagement_letters', $id, [
            'status' => self::STATUS_SIGNED,
            'signed_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
