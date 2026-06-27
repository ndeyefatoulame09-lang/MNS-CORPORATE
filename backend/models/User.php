<?php
declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
/**
 * Modèle pour les utilisateurs.
 */
class User extends BaseModel
{
    public const ROLE_EXPERT = 'EXPERT';
    public const ROLE_COLLABORATEUR = 'COLLABORATEUR';
    public const ROLE_STAGIAIRE = 'STAGIAIRE';
    public const ROLE_CLIENT = 'CLIENT';

    protected ?int $id = null;
    protected string $fullName = '';
    protected string $email = '';
    protected string $phone = '';
    protected string $passwordHash = '';
    protected string $role = self::ROLE_CLIENT;
    protected bool $isActive = true;
    protected ?string $lastLoginAt = null;
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
        $this->fullName = $data['full_name'] ?? $data['fullName'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->passwordHash = $data['password_hash'] ?? $data['passwordHash'] ?? '';
        $this->role = $data['role'] ?? self::ROLE_CLIENT;
        $this->isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;
        $this->lastLoginAt = $data['last_login_at'] ?? $data['lastLoginAt'] ?? null;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(bool $includePasswordHash = false): array
    {
        $result = [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->isActive,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        if ($includePasswordHash) {
            $result['password_hash'] = $this->passwordHash;
        }

        return $result;
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM `users` WHERE `id` = :id', ['id' => $id]);
    }

    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
        $filterClause = $this->buildFilterClause($filters, $params);

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->fetchAll(
            "SELECT * FROM `users`{$filterClause} ORDER BY `id` ASC LIMIT :limit OFFSET :offset",
            $params
        );
    }

    public function create(array $data): int
    {
        $insertData = [
            'full_name' => $data['full_name'] ?? $data['fullName'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'password_hash' => $data['password_hash'] ?? $data['passwordHash'] ?? $data['password'] ?? '',
            'role' => $data['role'] ?? self::ROLE_CLIENT,
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'last_login_at' => $data['last_login_at'] ?? $data['lastLoginAt'] ?? null,
        ];

        return $this->insert('users', $insertData);
    }

    public function createWithHashedPassword(array $data): int
    {
        $password = $data['password'] ?? '';
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        unset($data['password']);

        return $this->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $updateData = [];

        if (isset($data['full_name']) || isset($data['fullName'])) {
            $updateData['full_name'] = $data['full_name'] ?? $data['fullName'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }

        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = (int) $data['is_active'];
        }

        if (isset($data['last_login_at']) || isset($data['lastLoginAt'])) {
            $updateData['last_login_at'] = $data['last_login_at'] ?? $data['lastLoginAt'];
        }

        if (isset($data['password'])) {
            $updateData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } elseif (isset($data['password_hash'])) {
            $updateData['password_hash'] = $data['password_hash'];
        } elseif (isset($data['passwordHash'])) {
            $updateData['password_hash'] = $data['passwordHash'];
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->updateRecord('users', $id, $updateData);
    }

    public function delete(int $id): bool
    {
        return $this->deleteRecord('users', $id);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM `users` WHERE `email` = :email', ['email' => $email]);
    }

    public function verifyPassword(string $email, string $password): bool
    {
        $user = $this->findByEmail($email);

        if ($user === null || empty($user['password_hash'])) {
            return false;
        }

        return password_verify($password, $user['password_hash']);
    }

    public function getMissions(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll(
            'SELECT m.* FROM `missions` m
             INNER JOIN `mission_assignments` ma ON ma.mission_id = m.id
             WHERE ma.user_id = :user_id
             ORDER BY m.id ASC',
            ['user_id' => $this->id]
        );
    }

    public function getComments(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `comments` WHERE `user_id` = :user_id ORDER BY `id` ASC', ['user_id' => $this->id]);
    }

    public function getTimesheets(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `timesheets` WHERE `user_id` = :user_id ORDER BY `work_date` DESC', ['user_id' => $this->id]);
    }

    public function getNotifications(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `notifications` WHERE `user_id` = :user_id ORDER BY `created_at` DESC', ['user_id' => $this->id]);
    }

    public function getAuditLogs(): array
    {
        if ($this->id === null) {
            return [];
        }

        return $this->fetchAll('SELECT * FROM `audit_logs` WHERE `user_id` = :user_id ORDER BY `created_at` DESC', ['user_id' => $this->id]);
    }
}
