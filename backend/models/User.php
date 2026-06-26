<?php
declare(strict_types=1);

/**
 * Modèle pour les utilisateurs.
 */
class User extends BaseModel
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_ACCOUNTANT = 'accountant';
    public const ROLE_CLIENT = 'client';

    protected ?int $id = null;
    protected string $firstName = '';
    protected string $lastName = '';
    protected string $email = '';
    protected string $passwordHash = '';
    protected string $role = self::ROLE_CLIENT;
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
        $this->firstName = $data['first_name'] ?? $data['firstName'] ?? '';
        $this->lastName = $data['last_name'] ?? $data['lastName'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->passwordHash = $data['password_hash'] ?? $data['passwordHash'] ?? '';
        $this->role = $data['role'] ?? self::ROLE_CLIENT;
        $this->createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
    }

    public function toArray(bool $includePasswordHash = false): array
    {
        $result = [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'role' => $this->role,
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
            'first_name' => $data['first_name'] ?? $data['firstName'] ?? '',
            'last_name' => $data['last_name'] ?? $data['lastName'] ?? '',
            'email' => $data['email'] ?? '',
            'password_hash' => $data['password_hash'] ?? $data['passwordHash'] ?? $data['password'] ?? '',
            'role' => $data['role'] ?? self::ROLE_CLIENT,
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

        if (isset($data['first_name']) || isset($data['firstName'])) {
            $updateData['first_name'] = $data['first_name'] ?? $data['firstName'];
        }

        if (isset($data['last_name']) || isset($data['lastName'])) {
            $updateData['last_name'] = $data['last_name'] ?? $data['lastName'];
        }

        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
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
}
