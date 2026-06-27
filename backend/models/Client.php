<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

/**
 * Modèle pour les clients.
 */
class Client extends BaseModel
{
    protected ?int $id = null;
    protected ?int $userId = null;
    protected string $companyName = '';
    protected string $legalForm = '';
    protected string $contactName = '';
    protected string $email = '';
    protected string $phone = '';
    protected string $address = '';
    protected string $ninea = '';
    protected string $rccm = '';
    protected string $taxRegime = '';
    protected ?string $accountingYearStart = null;
    protected ?string $accountingYearEnd = null;
    protected string $status = 'ACTIF';
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
        $this->companyName = $data['company_name'] ?? $data['companyName'] ?? '';
        $this->legalForm = $data['legal_form'] ?? $data['legalForm'] ?? '';
        $this->contactName = $data['contact_name'] ?? $data['contactName'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->address = $data['address'] ?? '';
        $this->ninea = $data['ninea'] ?? '';
        $this->rccm = $data['rccm'] ?? '';
        $this->taxRegime = $data['tax_regime'] ?? $data['taxRegime'] ?? '';
        $this->accountingYearStart = $data['accounting_year_start'] ?? $data['accountingYearStart'] ?? null;
        $this->accountingYearEnd = $data['accounting_year_end'] ?? $data['accountingYearEnd'] ?? null;
        $this->status = $data['status'] ?? 'ACTIF';
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'company_name' => $this->companyName,
            'legal_form' => $this->legalForm,
            'contact_name' => $this->contactName,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'ninea' => $this->ninea,
            'rccm' => $this->rccm,
            'tax_regime' => $this->taxRegime,
            'accounting_year_start' => $this->accountingYearStart,
            'accounting_year_end' => $this->accountingYearEnd,
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
        // Use a flexible search supporting partial matches for company/contact/email/ninea/rccm
        $params = [];
        $where = [];

        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[] = '(company_name LIKE :q OR contact_name LIKE :q OR email LIKE :q OR ninea LIKE :q OR rccm LIKE :q)';
            $params['q'] = $q;
        }

        if (!empty($filters['status'])) {
            $where[] = '`status` = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['tax_regime'])) {
            $where[] = '`tax_regime` = :tax_regime';
            $params['tax_regime'] = $filters['tax_regime'];
        }

        $whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `clients`{$whereClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function count(array $filters = []): int
    {
        $params = [];
        $where = [];

        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[] = '(company_name LIKE :q OR contact_name LIKE :q OR email LIKE :q OR ninea LIKE :q OR rccm LIKE :q)';
            $params['q'] = $q;
        }

        if (!empty($filters['status'])) {
            $where[] = '`status` = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['tax_regime'])) {
            $where[] = '`tax_regime` = :tax_regime';
            $params['tax_regime'] = $filters['tax_regime'];
        }

        $whereClause = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $result = $this->fetchOne("SELECT COUNT(*) AS c FROM `clients`{$whereClause}", $params);

        return $result === null ? 0 : (int) $result['c'];
    }

    public function create(array $data): int
    {
        $insertData = [
            'user_id' => isset($data['user_id']) ? $data['user_id'] : null,
            'company_name' => $data['company_name'] ?? $data['companyName'] ?? '',
            'legal_form' => $this->nullable($data['legal_form'] ?? $data['legalForm'] ?? null),
            'contact_name' => $this->nullable($data['contact_name'] ?? $data['contactName'] ?? null),
            'email' => $this->nullable($data['email'] ?? null),
            'phone' => $this->nullable($data['phone'] ?? null),
            'address' => $this->nullable($data['address'] ?? null),
            'ninea' => $this->nullable($data['ninea'] ?? null),
            'rccm' => $this->nullable($data['rccm'] ?? null),
            'tax_regime' => $this->nullable($data['tax_regime'] ?? $data['taxRegime'] ?? null),
            'accounting_year_start' => $this->nullable($data['accounting_year_start'] ?? $data['accountingYearStart'] ?? null),
            'accounting_year_end' => $this->nullable($data['accounting_year_end'] ?? $data['accountingYearEnd'] ?? null),
            'status' => $data['status'] ?? 'ACTIF',
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
            $updateData['contact_name'] = $this->nullable($data['contact_name'] ?? $data['contactName']);
        }

        if (isset($data['email'])) {
            $updateData['email'] = $this->nullable($data['email']);
        }

        if (isset($data['phone'])) {
            $updateData['phone'] = $this->nullable($data['phone']);
        }

        if (isset($data['address'])) {
            $updateData['address'] = $this->nullable($data['address']);
        }

        if (isset($data['legal_form'])) {
            $updateData['legal_form'] = $this->nullable($data['legal_form']);
        }

        if (isset($data['ninea'])) {
            $updateData['ninea'] = $this->nullable($data['ninea']);
        }

        if (isset($data['rccm'])) {
            $updateData['rccm'] = $this->nullable($data['rccm']);
        }

        if (isset($data['tax_regime'])) {
            $updateData['tax_regime'] = $this->nullable($data['tax_regime']);
        }

        if (isset($data['accounting_year_start']) || isset($data['accountingYearStart'])) {
            $updateData['accounting_year_start'] = $this->nullable($data['accounting_year_start'] ?? $data['accountingYearStart']);
        }

        if (isset($data['accounting_year_end']) || isset($data['accountingYearEnd'])) {
            $updateData['accounting_year_end'] = $this->nullable($data['accounting_year_end'] ?? $data['accountingYearEnd']);
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

    public function setStatus(int $id, string $status): bool
    {
        return $this->updateRecord('clients', $id, ['status' => $status]);
    }

    public function countAll(array $filters = []): int
    {
        return $this->count($filters);
    }

    public function existsByNinea(string $ninea, int $excludeId = 0): bool
    {
        return $this->existsByUniqueField('ninea', $ninea, $excludeId);
    }

    public function existsByRccm(string $rccm, int $excludeId = 0): bool
    {
        return $this->existsByUniqueField('rccm', $rccm, $excludeId);
    }

    public function getTaxRegimes(): array
    {
        return $this->fetchAll(
            'SELECT DISTINCT tax_regime FROM `clients` WHERE tax_regime IS NOT NULL AND tax_regime <> "" ORDER BY tax_regime ASC'
        );
    }

    public function getMissions(int $clientId = null): array
    {
        $clientId = $clientId ?? $this->id;

        if ($clientId === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `missions` WHERE `client_id` = :client_id ORDER BY `id` ASC', ['client_id' => $clientId]);
    }

    public function getDocuments(int $clientId = null): array
    {
        $clientId = $clientId ?? $this->id;

        if ($clientId === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `documents` WHERE `client_id` = :client_id ORDER BY `id` ASC', ['client_id' => $clientId]);
    }

    public function getInvoices(int $clientId = null): array
    {
        $clientId = $clientId ?? $this->id;

        if ($clientId === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `invoices` WHERE `client_id` = :client_id ORDER BY `id` ASC', ['client_id' => $clientId]);
    }

    public function getFiscalDeadlines(int $clientId = null): array
    {
        $clientId = $clientId ?? $this->id;

        if ($clientId === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `fiscal_deadlines` WHERE `client_id` = :client_id ORDER BY `deadline_date` ASC', ['client_id' => $clientId]);
    }

    public function getEngagementLetters(int $clientId = null): array
    {
        $clientId = $clientId ?? $this->id;

        if ($clientId === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `engagement_letters` WHERE `client_id` = :client_id ORDER BY `id` ASC', ['client_id' => $clientId]);
    }

    private function existsByUniqueField(string $field, string $value, int $excludeId): bool
    {
        $sql = "SELECT id FROM `clients` WHERE `{$field}` = :value";
        $params = ['value' => $value];

        if ($excludeId > 0) {
            $sql .= ' AND `id` <> :id';
            $params['id'] = $excludeId;
        }

        return $this->fetchOne($sql, $params) !== null;
    }

    private function nullable(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }
}
