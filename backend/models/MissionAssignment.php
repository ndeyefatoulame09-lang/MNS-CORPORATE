<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class MissionAssignment extends BaseModel
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM mission_assignments WHERE id = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $where = $this->buildFilterClause($filters, $params);
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        return $this->fetchAll("SELECT * FROM mission_assignments{$where} ORDER BY assigned_at DESC LIMIT :limit OFFSET :offset", $params);
    }

    public function create(array $data): int
    {
        $this->assertValidAssignmentDates($data, true);

        return $this->insert('mission_assignments', [
            'mission_id' => (int) $data['mission_id'],
            'user_id' => (int) $data['user_id'],
            'assigned_by' => (int) $data['assigned_by'],
            'planned_start_date' => $this->nullableDate($data['planned_start_date'] ?? null),
            'planned_end_date' => $this->nullableDate($data['planned_end_date'] ?? null),
            'assignment_role' => $data['assignment_role'] ?? null,
            'status' => $data['status'] ?? 'ASSIGNEE',
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $this->assertValidAssignmentDates($data, false);

        $updateData = [];
        foreach (['mission_id', 'user_id', 'assigned_by', 'planned_start_date', 'planned_end_date', 'assignment_role', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = in_array($field, ['planned_start_date', 'planned_end_date'], true) ? $this->nullableDate($data[$field]) : $data[$field];
            }
        }
        return $updateData === [] ? false : $this->updateRecord('mission_assignments', $id, $updateData);
    }

    public function findByMission(int $missionId): array
    {
        return $this->fetchAll('SELECT * FROM mission_assignments WHERE mission_id = :mission_id ORDER BY id ASC', ['mission_id' => $missionId]);
    }

    public function findByUser(int $userId): array
    {
        return $this->fetchAll('SELECT * FROM mission_assignments WHERE user_id = :user_id ORDER BY id ASC', ['user_id' => $userId]);
    }

    private function assertValidAssignmentDates(array $data, bool $isCreate): void
    {
        $start = trim((string) ($data['planned_start_date'] ?? ''));
        $end = trim((string) ($data['planned_end_date'] ?? ''));

        if ($isCreate && $start !== '' && $start < date('Y-m-d')) {
            throw new InvalidArgumentException('La date de debut affectee ne peut pas etre dans le passe.');
        }

        if ($start !== '' && $end !== '' && $end < $start) {
            throw new InvalidArgumentException('La date de fin affectee ne peut pas etre avant la date de debut.');
        }
    }

    private function nullableDate(mixed $value): ?string
    {
        $date = trim((string) ($value ?? ''));
        return $date === '' ? null : $date;
    }
}