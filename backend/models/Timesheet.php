<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Timesheet extends BaseModel
{
    public const STATUSES = ['SAISI', 'VALIDE', 'REFUSE'];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT t.*, m.title AS mission_title, u.full_name AS user_name, v.full_name AS validator_name
             FROM timesheets t
             INNER JOIN missions m ON m.id = t.mission_id
             INNER JOIN users u ON u.id = t.user_id
             LEFT JOIN users v ON v.id = t.validated_by
             WHERE t.id = :id',
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
            "SELECT t.*, m.title AS mission_title, u.full_name AS user_name
             FROM timesheets t
             INNER JOIN missions m ON m.id = t.mission_id
             INNER JOIN users u ON u.id = t.user_id
             {$where}
             ORDER BY t.work_date DESC, t.id DESC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $where = $this->where($filters, $params);
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM timesheets t INNER JOIN missions m ON m.id = t.mission_id INNER JOIN users u ON u.id = t.user_id {$where}",
            $params
        );
        return $row === null ? 0 : (int) $row['total'];
    }

    public function findByUser(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $filters['user_id'] = $userId;
        return $this->findAll($filters, $limit, $offset);
    }

    public function findByMission(int $missionId): array
    {
        return $this->findAll(['mission_id' => $missionId], 500, 0);
    }

    public function create(array $data): int
    {
        return $this->insert('timesheets', [
            'mission_id' => (int) $data['mission_id'],
            'user_id' => (int) $data['user_id'],
            'work_date' => $data['work_date'] ?? null,
            'hours_worked' => (float) $data['hours_worked'],
            'description' => $data['description'] ?? '',
            'status' => 'SAISI',
            'validated_by' => null,
            'validated_at' => null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        return $this->updateRecord('timesheets', $id, [
            'mission_id' => (int) $data['mission_id'],
            'work_date' => $data['work_date'] ?? null,
            'hours_worked' => (float) $data['hours_worked'],
            'description' => $data['description'] ?? '',
        ]);
    }

    public function validate(int $id, int $expertId): bool
    {
        return $this->updateRecord('timesheets', $id, [
            'status' => 'VALIDE',
            'validated_by' => $expertId,
            'validated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function reject(int $id, int $expertId): bool
    {
        return $this->updateRecord('timesheets', $id, [
            'status' => 'REFUSE',
            'validated_by' => $expertId,
            'validated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getTotalHoursByUserAndDate(int $userId, string $workDate, ?int $excludeId = null): float
    {
        $sql = 'SELECT COALESCE(SUM(hours_worked), 0) AS total FROM timesheets WHERE user_id = :user_id AND work_date = :work_date';
        $params = ['user_id' => $userId, 'work_date' => $workDate];
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $row = $this->fetchOne($sql, $params);
        return $row === null ? 0.0 : (float) $row['total'];
    }

    public function getMissionHoursSummary(array $filters = []): array
    {
        $params = [];
        $where = '';
        if (($filters['mission_id'] ?? '') !== '') {
            $where = ' WHERE m.id = :mission_id';
            $params['mission_id'] = $filters['mission_id'];
        }
        return $this->fetchAll(
            "SELECT m.id, m.title, m.estimated_hours,
                    COALESCE(SUM(t.hours_worked), 0) AS total_saisi,
                    COALESCE(SUM(CASE WHEN t.status = 'VALIDE' THEN t.hours_worked ELSE 0 END), 0) AS total_valide,
                    COALESCE(SUM(CASE WHEN t.status = 'REFUSE' THEN t.hours_worked ELSE 0 END), 0) AS total_refuse
             FROM missions m
             LEFT JOIN timesheets t ON t.mission_id = m.id
             {$where}
             GROUP BY m.id, m.title, m.estimated_hours
             ORDER BY m.title ASC",
            $params
        );
    }

    public function getUserHoursSummary(array $filters = []): float
    {
        $params = [];
        $where = $this->where($filters, $params);
        $row = $this->fetchOne("SELECT COALESCE(SUM(t.hours_worked), 0) AS total FROM timesheets t INNER JOIN missions m ON m.id = t.mission_id INNER JOIN users u ON u.id = t.user_id {$where}", $params);
        return $row === null ? 0.0 : (float) $row['total'];
    }

    public function findAssignedMissions(int $userId): array
    {
        return $this->fetchAll(
            'SELECT m.id, m.title FROM missions m INNER JOIN mission_assignments ma ON ma.mission_id = m.id WHERE ma.user_id = :user_id ORDER BY m.title ASC',
            ['user_id' => $userId]
        );
    }

    public function isUserAssignedToMission(int $userId, int $missionId): bool
    {
        return $this->fetchOne(
            'SELECT id FROM mission_assignments WHERE user_id = :user_id AND mission_id = :mission_id',
            ['user_id' => $userId, 'mission_id' => $missionId]
        ) !== null;
    }

    private function where(array $filters, array &$params): string
    {
        $where = [];
        if (($filters['q'] ?? '') !== '') {
            $where[] = 't.description LIKE :q';
            $params['q'] = '%' . $filters['q'] . '%';
        }
        foreach (['user_id', 'mission_id', 'status'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = "t.{$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 't.work_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 't.work_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }
}
