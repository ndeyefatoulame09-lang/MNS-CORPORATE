<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class Document extends BaseModel
{
    public const STATUSES = ['NOUVEAU', 'CONSULTE', 'VALIDE', 'REJETE'];
    public const CATEGORIES = ['FACTURE', 'RELEVE_BANCAIRE', 'CONTRAT', 'DECLARATION', 'AUTRE'];

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM documents WHERE id = :id', ['id' => $id]);
    }

    public function getDocumentWithRelations(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT d.*, c.company_name, m.title AS mission_title, u.full_name AS uploader_name
             FROM documents d
             INNER JOIN clients c ON c.id = d.client_id
             LEFT JOIN missions m ON m.id = d.mission_id
             INNER JOIN users u ON u.id = d.uploaded_by
             WHERE d.id = :id',
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
            "SELECT d.*, c.company_name, m.title AS mission_title, u.full_name AS uploader_name
             FROM documents d
             INNER JOIN clients c ON c.id = d.client_id
             LEFT JOIN missions m ON m.id = d.mission_id
             INNER JOIN users u ON u.id = d.uploaded_by
             {$where}
             ORDER BY d.uploaded_at DESC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function countAll(array $filters = []): int
    {
        $params = [];
        $where = $this->where($filters, $params);
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total FROM documents d
             INNER JOIN clients c ON c.id = d.client_id
             LEFT JOIN missions m ON m.id = d.mission_id {$where}",
            $params
        );
        return $row === null ? 0 : (int) $row['total'];
    }

    public function create(array $data): int
    {
        return $this->insert('documents', [
            'client_id' => (int) $data['client_id'],
            'mission_id' => ($data['mission_id'] ?? '') === '' ? null : (int) $data['mission_id'],
            'uploaded_by' => (int) $data['uploaded_by'],
            'title' => $data['title'] ?? '',
            'original_filename' => $data['original_filename'] ?? '',
            'stored_filename' => $data['stored_filename'] ?? '',
            'file_path' => $data['file_path'] ?? '',
            'file_type' => $data['file_type'] ?? null,
            'file_size' => $data['file_size'] ?? null,
            'document_category' => $data['document_category'] ?? 'AUTRE',
            'status' => $data['status'] ?? 'NOUVEAU',
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->updateRecord('documents', $id, ['status' => $status]);
    }

    public function updateMetadata(int $id, array $data): bool
    {
        return $this->updateRecord('documents', $id, [
            'title' => $data['title'] ?? '',
            'mission_id' => ($data['mission_id'] ?? '') === '' ? null : (int) $data['mission_id'],
            'document_category' => $data['document_category'] ?? 'AUTRE',
        ]);
    }

    public function replaceFile(int $id, array $fileData): bool
    {
        return $this->updateRecord('documents', $id, [
            'original_filename' => $fileData['original_filename'] ?? '',
            'stored_filename' => $fileData['stored_filename'] ?? '',
            'file_path' => $fileData['file_path'] ?? '',
            'file_type' => $fileData['file_type'] ?? null,
            'file_size' => $fileData['file_size'] ?? null,
        ]);
    }

    public function archive(int $id, int $userId, string $reason): bool
    {
        return $this->updateRecord('documents', $id, [
            'is_archived' => 1,
            'archived_at' => date('Y-m-d H:i:s'),
            'archived_by' => $userId,
            'archive_reason' => $reason,
        ]);
    }

    public function restore(int $id): bool
    {
        return $this->updateRecord('documents', $id, [
            'is_archived' => 0,
            'archived_at' => null,
            'archived_by' => null,
            'archive_reason' => null,
        ]);
    }

    public function findByClient(int $clientId): array
    {
        return $this->findAll(['client_id' => $clientId], 100, 0);
    }

    public function findByMission(int $missionId): array
    {
        return $this->findAll(['mission_id' => $missionId], 100, 0);
    }

    public function findAccessibleByUser(array $user, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->findAll($this->applyAccessFilters($user, $filters), $limit, $offset);
    }

    public function countAccessibleByUser(array $user, array $filters = []): int
    {
        return $this->countAll($this->applyAccessFilters($user, $filters));
    }

    public function canAccess(array $user, int $documentId, bool $includeArchived = false): bool
    {
        $filters = $this->applyAccessFilters($user, ['document_id' => $documentId]);
        if ($includeArchived) {
            $filters['show_archived'] = '1';
        }
        return $this->countAll($filters) > 0;
    }

    private function applyAccessFilters(array $user, array $filters): array
    {
        if (($user['role'] ?? '') === 'CLIENT') {
            $client = $this->fetchOne('SELECT id FROM clients WHERE user_id = :user_id', ['user_id' => (int) $user['id']]);
            $filters['client_id'] = $client['id'] ?? 0;
        } elseif (in_array(($user['role'] ?? ''), ['COLLABORATEUR', 'STAGIAIRE'], true)) {
            $filters['assigned_user_id'] = (int) $user['id'];
        }
        return $filters;
    }

    private function where(array $filters, array &$params): string
    {
        $where = [];
        if (($filters['show_archived'] ?? '') !== '1') {
            $where[] = 'd.is_archived = 0';
        } elseif (array_key_exists('is_archived', $filters)) {
            $where[] = 'd.is_archived = :is_archived';
            $params['is_archived'] = $filters['is_archived'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(d.title LIKE :q OR d.original_filename LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if (($filters['document_id'] ?? '') !== '') {
            $where[] = 'd.id = :document_id';
            $params['document_id'] = $filters['document_id'];
        }
        foreach (['client_id', 'mission_id', 'status', 'document_category'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = "d.{$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }
        if (($filters['assigned_user_id'] ?? '') !== '') {
            $where[] = 'd.mission_id IN (SELECT mission_id FROM mission_assignments WHERE user_id = :assigned_user_id)';
            $params['assigned_user_id'] = $filters['assigned_user_id'];
        }
        return $where ? ' WHERE ' . implode(' AND ', $where) : '';
    }
}
