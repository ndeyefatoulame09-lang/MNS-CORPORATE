<?php
declare(strict_types=1);

/**
 * Modèle pour les factures.
 */
class Invoice extends BaseModel
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';

    protected ?int $id = null;
    protected ?int $clientId = null;
    protected float $amount = 0.0;
    protected string $currency = 'EUR';
    protected string $status = self::STATUS_DRAFT;
    protected ?string $dueDate = null;
    protected ?string $issuedAt = null;
    protected ?string $paidAt = null;
    protected ?float $balance = null;
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
        $this->amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $this->currency = $data['currency'] ?? 'EUR';
        $this->status = $data['status'] ?? self::STATUS_DRAFT;
        $this->dueDate = $data['due_date'] ?? $data['dueDate'] ?? null;
        $this->issuedAt = $data['issued_at'] ?? $data['issuedAt'] ?? null;
        $this->paidAt = $data['paid_at'] ?? $data['paidAt'] ?? null;
        $this->balance = isset($data['balance']) ? (float) $data['balance'] : null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'due_date' => $this->dueDate,
            'issued_at' => $this->issuedAt,
            'paid_at' => $this->paidAt,
            'balance' => $this->balance,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `invoices` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `invoices`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'client_id' => $data['client_id'] ?? $data['clientId'] ?? null,
            'amount' => isset($data['amount']) ? (float) $data['amount'] : 0.0,
            'currency' => $data['currency'] ?? 'EUR',
            'status' => $data['status'] ?? self::STATUS_DRAFT,
            'due_date' => $data['due_date'] ?? $data['dueDate'] ?? null,
            'issued_at' => $data['issued_at'] ?? $data['issuedAt'] ?? null,
            'paid_at' => $data['paid_at'] ?? $data['paidAt'] ?? null,
            'balance' => isset($data['balance']) ? (float) $data['balance'] : null,
        ];

        return $this->insert('invoices', $insertData);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['client_id']) || isset($data['clientId'])) {
            $updateData['client_id'] = $data['client_id'] ?? $data['clientId'];
        }

        if (isset($data['amount'])) {
            $updateData['amount'] = (float) $data['amount'];
        }

        if (isset($data['currency'])) {
            $updateData['currency'] = $data['currency'];
        }

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['due_date']) || isset($data['dueDate'])) {
            $updateData['due_date'] = $data['due_date'] ?? $data['dueDate'];
        }

        if (isset($data['issued_at']) || isset($data['issuedAt'])) {
            $updateData['issued_at'] = $data['issued_at'] ?? $data['issuedAt'];
        }

        if (isset($data['paid_at']) || isset($data['paidAt'])) {
            $updateData['paid_at'] = $data['paid_at'] ?? $data['paidAt'];
        }

        if (isset($data['balance'])) {
            $updateData['balance'] = (float) $data['balance'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('invoices', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('invoices', $id);
    }

    public function findByClient(int $clientId): array
    {
        return $this->fetchAll('SELECT * FROM `invoices` WHERE `client_id` = :client_id ORDER BY `id` ASC', ['client_id' => $clientId]);
    }

    public function calculateBalance(int $id): float
    {
        $invoice = $this->findById($id);

        if ($invoice === null) {
            return 0.0;
        }

        $paid = $this->fetchOne(
            'SELECT COALESCE(SUM(`amount`), 0) AS total_paid FROM `payments` WHERE `invoice_id` = :invoice_id',
            ['invoice_id' => $id]
        );

        $totalPaid = isset($paid['total_paid']) ? (float) $paid['total_paid'] : 0.0;
        $balance = (float) $invoice['amount'] - $totalPaid;

        return $balance < 0.0 ? 0.0 : $balance;
    }

    public function markAsPaid(int $id): bool
    {
        $balance = $this->calculateBalance($id);

        return $this->updateRecord('invoices', $id, [
            'status' => self::STATUS_PAID,
            'paid_at' => date('Y-m-d H:i:s'),
            'balance' => $balance,
        ]);
    }
}
