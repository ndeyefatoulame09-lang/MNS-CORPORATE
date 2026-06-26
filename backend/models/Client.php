<?php
declare(strict_types=1);

/**
 * Modèle pour les clients.
 */
class Client extends BaseModel
{
    protected ?int $id = null;
    protected string $companyName = '';
    protected string $contactName = '';
    protected string $email = '';
    protected string $phone = '';
    protected string $address = '';
    protected string $city = '';
    protected string $postalCode = '';
    protected string $country = '';
    protected string $status = 'active';
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
        $this->companyName = $data['company_name'] ?? $data['companyName'] ?? '';
        $this->contactName = $data['contact_name'] ?? $data['contactName'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->postalCode = $data['postal_code'] ?? $data['postalCode'] ?? '';
        $this->country = $data['country'] ?? '';
        $this->status = $data['status'] ?? 'active';
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->companyName,
            'contact_name' => $this->contactName,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `clients` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `clients`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'company_name' => $data['company_name'] ?? $data['companyName'] ?? '',
            'contact_name' => $data['contact_name'] ?? $data['contactName'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'postal_code' => $data['postal_code'] ?? $data['postalCode'] ?? '',
            'country' => $data['country'] ?? '',
            'status' => $data['status'] ?? 'active',
        ];

        return $this->insert('clients', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['company_name']) || isset($data['companyName'])) {
            $updateData['company_name'] = $data['company_name'] ?? $data['companyName'];
        }

        if (isset($data['contact_name']) || isset($data['contactName'])) {
            $updateData['contact_name'] = $data['contact_name'] ?? $data['contactName'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }

        if (isset($data['address'])) {
            $updateData['address'] = $data['address'];
        }

        if (isset($data['city'])) {
            $updateData['city'] = $data['city'];
        }

        if (isset($data['postal_code']) || isset($data['postalCode'])) {
            $updateData['postal_code'] = $data['postal_code'] ?? $data['postalCode'];
        }

        if (isset($data['country'])) {
            $updateData['country'] = $data['country'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('clients', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('clients', $id);
    }
}
