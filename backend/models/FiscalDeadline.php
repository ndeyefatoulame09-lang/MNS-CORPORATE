<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../config/accounting_rules.php';

class FiscalDeadline extends BaseModel
{
    public const STATUSES = ['A_VENIR', 'TERMINEE', 'EN_RETARD'];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT fd.*, c.company_name, m.title AS mission_title
             FROM fiscal_deadlines fd
             INNER JOIN clients c ON c.id = fd.client_id
             LEFT JOIN missions m ON m.id = fd.mission_id
             WHERE fd.id = :id',
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
            "SELECT fd.*, c.company_name, m.title AS mission_title
             FROM fiscal_deadlines fd
             INNER JOIN clients c ON c.id = fd.client_id
             LEFT JOIN missions m ON m.id = fd.mission_id
             {$where}
             ORDER BY fd.deadline_date ASC, fd.id ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $where = $this->where($filters, $params);
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM fiscal_deadlines fd
             INNER JOIN clients c ON c.id = fd.client_id
             LEFT JOIN missions m ON m.id = fd.mission_id {$where}",
            $params
        );
        return $row === null ? 0 : (int) $row['total'];
    }

    public function create(array $data): int
    {
        return $this->insert('fiscal_deadlines', [
            'client_id' => (int) $data['client_id'],
            'mission_id' => ($data['mission_id'] ?? '') === '' ? null : (int) $data['mission_id'],
            'title' => $data['title'] ?? '',
            'description' => ($data['description'] ?? '') === '' ? null : $data['description'],
            'deadline_date' => $data['deadline_date'] ?? null,
            'status' => $data['status'] ?? 'A_VENIR',
            'completed_at' => ($data['completed_at'] ?? '') === '' ? null : $data['completed_at'],
        ]);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateRecord('fiscal_deadlines', $id, [
            'client_id' => (int) $data['client_id'],
            'mission_id' => ($data['mission_id'] ?? '') === '' ? null : (int) $data['mission_id'],
            'title' => $data['title'] ?? '',
            'description' => ($data['description'] ?? '') === '' ? null : $data['description'],
            'deadline_date' => $data['deadline_date'] ?? null,
            'status' => $data['status'] ?? 'A_VENIR',
            'completed_at' => ($data['completed_at'] ?? '') === '' ? null : $data['completed_at'],
        ]);
    }

    public function markAsCompleted(int $id): bool
    {
        return $this->updateRecord('fiscal_deadlines', $id, [
            'status' => 'TERMINEE',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markAsOverdue(): int
    {
        $stmt = $this->db->prepare("UPDATE fiscal_deadlines SET status = 'EN_RETARD' WHERE deadline_date < CURDATE() AND status <> 'TERMINEE'");
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function generateMonthlyVatDeadlines(int $year): int
    {
        $clients = $this->fetchAll("SELECT id, company_name FROM clients WHERE status = 'ACTIF'");
        $created = 0;
        foreach ($clients as $client) {
            for ($month = 1; $month <= 12; $month++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, TVA_DEADLINE_DAY);
                $title = 'Declaration TVA ' . sprintf('%02d/%04d', $month, $year);
                if (!$this->existsForClientAndDate((int) $client['id'], $date, $title)) {
                    $this->create([
                        'client_id' => $client['id'],
                        'title' => $title,
                        'description' => 'Echeance mensuelle de declaration TVA.',
                        'deadline_date' => $date,
                        'status' => 'A_VENIR',
                    ]);
                    $created++;
                }
            }
        }
        return $created;
    }

    public function generateAnnualIsDeadlines(int $year): int
    {
        $clients = $this->fetchAll("SELECT id FROM clients WHERE status = 'ACTIF'");
        $created = 0;
        $date = sprintf('%04d-%02d-%02d', $year, IS_DEADLINE_MONTH, IS_DEADLINE_DAY);
        foreach ($clients as $client) {
            $title = 'Declaration IS ' . $year;
            if (!$this->existsForClientAndDate((int) $client['id'], $date, $title)) {
                $this->create([
                    'client_id' => $client['id'],
                    'title' => $title,
                    'description' => 'Echeance annuelle de declaration de l impot sur les societes.',
                    'deadline_date' => $date,
                    'status' => 'A_VENIR',
                ]);
                $created++;
            }
        }
        return $created;
    }

    public function existsForClientAndDate(int $clientId, string $date, string $title): bool
    {
        return $this->fetchOne(
            'SELECT id FROM fiscal_deadlines WHERE client_id = :client_id AND deadline_date = :deadline_date AND title = :title',
            ['client_id' => $clientId, 'deadline_date' => $date, 'title' => $title]
        ) !== null;
    }

    private function where(array $filters, array &$params): string
    {
        $where = [];
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(fd.title LIKE :q OR fd.description LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }
        foreach (['client_id', 'mission_id', 'status'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = "fd.{$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'fd.deadline_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'fd.deadline_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (($filters['visible_client_id'] ?? '') !== '') {
            $where[] = 'fd.client_id = :visible_client_id';
            $params['visible_client_id'] = $filters['visible_client_id'];
        }
        if (($filters['assigned_user_id'] ?? '') !== '') {
            $where[] = 'fd.mission_id IN (SELECT mission_id FROM mission_assignments WHERE user_id = :assigned_user_id)';
            $params['assigned_user_id'] = $filters['assigned_user_id'];
        }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }
}
