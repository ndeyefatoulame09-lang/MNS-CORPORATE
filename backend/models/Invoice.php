<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Invoice extends BaseModel
{
    public const STATUSES = ['BROUILLON', 'ENVOYEE', 'PARTIELLEMENT_PAYEE', 'PAYEE', 'EN_RETARD', 'ANNULEE'];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT i.*, c.company_name, m.title AS mission_title, u.full_name AS creator_name
             FROM invoices i
             INNER JOIN clients c ON c.id = i.client_id
             LEFT JOIN missions m ON m.id = i.mission_id
             INNER JOIN users u ON u.id = i.created_by
             WHERE i.id = :id',
            ['id' => $id]
        );
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $where = $this->where($filters, $params);
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        return $this->fetchAll(
            "SELECT i.*, c.company_name, m.title AS mission_title,
                    COALESCE(SUM(p.amount), 0) AS paid_amount,
                    GREATEST(i.total_amount - COALESCE(SUM(p.amount), 0), 0) AS balance_due
             FROM invoices i
             INNER JOIN clients c ON c.id = i.client_id
             LEFT JOIN missions m ON m.id = i.mission_id
             LEFT JOIN payments p ON p.invoice_id = i.id
             {$where}
             GROUP BY i.id, c.company_name, m.title
             ORDER BY i.issue_date DESC, i.id DESC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $where = $this->where($filters, $params);
        $row = $this->fetchOne(
            "SELECT COUNT(DISTINCT i.id) AS total FROM invoices i INNER JOIN clients c ON c.id = i.client_id LEFT JOIN missions m ON m.id = i.mission_id {$where}",
            $params
        );
        return $row === null ? 0 : (int) $row['total'];
    }

    public function create(array $data): int
    {
        return $this->insert('invoices', [
            'client_id' => (int) $data['client_id'],
            'mission_id' => ($data['mission_id'] ?? '') === '' ? null : (int) $data['mission_id'],
            'invoice_number' => $data['invoice_number'] ?? '',
            'issue_date' => $data['issue_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'subtotal' => (float) $data['subtotal'],
            'tax_rate' => (float) $data['tax_rate'],
            'tax_amount' => (float) $data['tax_amount'],
            'total_amount' => (float) $data['total_amount'],
            'status' => $data['status'] ?? 'BROUILLON',
            'notes' => ($data['notes'] ?? '') === '' ? null : $data['notes'],
            'created_by' => (int) $data['created_by'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateRecord('invoices', $id, [
            'client_id' => (int) $data['client_id'],
            'mission_id' => ($data['mission_id'] ?? '') === '' ? null : (int) $data['mission_id'],
            'invoice_number' => $data['invoice_number'] ?? '',
            'issue_date' => $data['issue_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'subtotal' => (float) $data['subtotal'],
            'tax_rate' => (float) $data['tax_rate'],
            'tax_amount' => (float) $data['tax_amount'],
            'total_amount' => (float) $data['total_amount'],
            'status' => $data['status'] ?? 'BROUILLON',
            'notes' => ($data['notes'] ?? '') === '' ? null : $data['notes'],
        ]);
    }

    public function findByClient(int $clientId): array { return $this->findAll(['client_id' => $clientId], 100, 0); }
    public function findByMission(int $missionId): array { return $this->findAll(['mission_id' => $missionId], 100, 0); }
    public function getPaymentsTotal(int $invoiceId): float { $r=$this->fetchOne('SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE invoice_id = :id',['id'=>$invoiceId]); return $r ? (float)$r['total'] : 0.0; }
    public function getRemainingBalance(int $invoiceId): float { $i=$this->findById($invoiceId); return $i ? max(0, (float)$i['total_amount'] - $this->getPaymentsTotal($invoiceId)) : 0.0; }

    public function refreshPaymentStatus(int $invoiceId): bool
    {
        $invoice = $this->findById($invoiceId);
        if ($invoice === null || $invoice['status'] === 'ANNULEE') { return false; }
        $paid = $this->getPaymentsTotal($invoiceId);
        $total = (float) $invoice['total_amount'];
        $status = $paid <= 0 ? ((strtotime($invoice['due_date']) < strtotime(date('Y-m-d'))) ? 'EN_RETARD' : $invoice['status']) : ($paid >= $total ? 'PAYEE' : 'PARTIELLEMENT_PAYEE');
        return $this->updateRecord('invoices', $invoiceId, ['status' => $status]);
    }

    public function markOverdue(): int
    {
        $stmt = $this->db->prepare("UPDATE invoices i SET status = 'EN_RETARD' WHERE i.due_date < CURDATE() AND i.status NOT IN ('PAYEE','ANNULEE') AND i.total_amount > (SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.invoice_id = i.id)");
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function getBalanceAgedSummary(): array
    {
        return $this->fetchAll(
            "SELECT i.*, c.company_name, COALESCE(SUM(p.amount),0) AS paid_amount,
                    GREATEST(i.total_amount - COALESCE(SUM(p.amount),0),0) AS balance_due,
                    DATEDIFF(CURDATE(), i.due_date) AS days_late
             FROM invoices i
             INNER JOIN clients c ON c.id = i.client_id
             LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE i.status <> 'ANNULEE'
             GROUP BY i.id, c.company_name
             HAVING balance_due > 0
             ORDER BY i.due_date ASC"
        );
    }

    public function invoiceNumberExists(string $number, int $excludeId = 0): bool
    {
        $sql = 'SELECT id FROM invoices WHERE invoice_number = :number';
        $params = ['number' => $number];
        if ($excludeId > 0) { $sql .= ' AND id <> :id'; $params['id'] = $excludeId; }
        return $this->fetchOne($sql, $params) !== null;
    }

    private function where(array $filters, array &$params): string
    {
        $where = [];
        if (($filters['q'] ?? '') !== '') { $where[] = '(i.invoice_number LIKE :q OR c.company_name LIKE :q OR i.notes LIKE :q)'; $params['q'] = '%' . $filters['q'] . '%'; }
        foreach (['client_id','mission_id','status'] as $f) { if (($filters[$f] ?? '') !== '') { $where[] = "i.$f = :$f"; $params[$f] = $filters[$f]; } }
        if (($filters['issue_from'] ?? '') !== '') { $where[]='i.issue_date >= :issue_from'; $params['issue_from']=$filters['issue_from']; }
        if (($filters['issue_to'] ?? '') !== '') { $where[]='i.issue_date <= :issue_to'; $params['issue_to']=$filters['issue_to']; }
        if (($filters['due_from'] ?? '') !== '') { $where[]='i.due_date >= :due_from'; $params['due_from']=$filters['due_from']; }
        if (($filters['due_to'] ?? '') !== '') { $where[]='i.due_date <= :due_to'; $params['due_to']=$filters['due_to']; }
        if (($filters['visible_client_id'] ?? '') !== '') { $where[]='i.client_id = :visible_client_id'; $params['visible_client_id']=$filters['visible_client_id']; }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }
}
