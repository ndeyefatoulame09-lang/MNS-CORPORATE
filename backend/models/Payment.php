<?php
declare(strict_types=1);

/**
 * Modèle pour les paiements.
 */
class Payment extends BaseModel
{
    protected ?int $id = null;
    protected ?int $invoiceId = null;
    protected float $amount = 0.0;
    protected string $currency = 'EUR';
    protected ?string $paymentDate = null;
    protected string $method = '';
    protected ?string $reference = null;
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
        $this->invoiceId = isset($data['invoice_id']) ? (int) $data['invoice_id'] : (isset($data['invoiceId']) ? (int) $data['invoiceId'] : null);
        $this->amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $this->currency = $data['currency'] ?? 'EUR';
        $this->paymentDate = $data['payment_date'] ?? $data['paymentDate'] ?? null;
        $this->method = $data['method'] ?? '';
        $this->reference = $data['reference'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoiceId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->paymentDate,
            'method' => $this->method,
            'reference' => $this->reference,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `payments` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `payments`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'invoice_id' => $data['invoice_id'] ?? $data['invoiceId'] ?? null,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : 0.0,
            'currency' => $data['currency'] ?? 'EUR',
            'payment_date' => $data['payment_date'] ?? $data['paymentDate'] ?? null,
            'method' => $data['method'] ?? '',
            'reference' => $data['reference'] ?? null,
        ];

        return $this->insert('payments', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['invoice_id']) || isset($data['invoiceId'])) {
            $updateData['invoice_id'] = $data['invoice_id'] ?? $data['invoiceId'];
        }

        if (isset($data['amount'])) {
            $updateData['amount'] = (float) $data['amount'];
        }

        if (isset($data['currency'])) {
            $updateData['currency'] = $data['currency'];
        }

        if (isset($data['payment_date']) || isset($data['paymentDate'])) {
            $updateData['payment_date'] = $data['payment_date'] ?? $data['paymentDate'];
        }

        if (isset($data['method'])) {
            $updateData['method'] = $data['method'];
        }

        if (array_key_exists('reference', $data)) {
            $updateData['reference'] = $data['reference'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('payments', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('payments', $id);
    }

    public function findByInvoice(int $invoiceId): array
    {
        return $this->fetchAll('SELECT * FROM `payments` WHERE `invoice_id` = :invoice_id ORDER BY `id` ASC', ['invoice_id' => $invoiceId]);
    }

    public function getInvoice(): ?array
    {
        if ($this->invoiceId === null) {
            return null;
        }

        return $this->fetchOne('SELECT * FROM `invoices` WHERE `id` = :id', ['id' => $this->invoiceId]);
    }

    public function getClient(): ?array
    {
        if ($this->invoiceId === null) {
            return null;
        }

        return $this->fetchOne(
            'SELECT c.* FROM `clients` c JOIN `invoices` i ON c.id = i.client_id WHERE i.id = :invoice_id',
            ['invoice_id' => $this->invoiceId]
        );
    }
}
