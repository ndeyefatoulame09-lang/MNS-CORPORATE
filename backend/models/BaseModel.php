<?php
declare(strict_types=1);

use PDO;

/**
 * Classe de base pour tous les modèles.
 */
abstract class BaseModel
{
    protected PDO $db;

    /**
     * BaseModel constructor.
     *
     * @param PDO $db Objet PDO pour la connexion à la base.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result === false ? null : $result;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function insert(string $table, array $data): int
    {
        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($data as $key => $value) {
            $column = $this->toSnakeCase($key);
            $columns[] = "`$column`";
            $placeholder = ":$column";
            $placeholders[] = $placeholder;
            $params[$column] = $value;
        }

        if (empty($columns)) {
            return 0;
        }

        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $this->db->lastInsertId();
    }

    protected function updateRecord(string $table, int $id, array $data): bool
    {
        $sets = [];
        $params = [];

        foreach ($data as $key => $value) {
            $column = $this->toSnakeCase($key);
            $sets[] = "`$column` = :$column";
            $params[$column] = $value;
        }

        if (empty($sets)) {
            return false;
        }

        $params['id'] = $id;
        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `id` = :id',
            $table,
            implode(', ', $sets)
        );

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    protected function deleteRecord(string $table, int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `$table` WHERE `id` = :id");

        return $stmt->execute(['id' => $id]);
    }

    protected function buildFilterClause(array $filters, array &$params): string
    {
        $clauses = [];

        foreach ($filters as $key => $value) {
            $column = $this->toSnakeCase($key);
            $placeholder = ':' . $column;

            if ($value === null) {
                $clauses[] = "`$column` IS NULL";
                continue;
            }

            $clauses[] = "`$column` = $placeholder";
            $params[$column] = $value;
        }

        return $clauses ? ' WHERE ' . implode(' AND ', $clauses) : '';
    }

    protected function toSnakeCase(string $input): string
    {
        $snake = strtolower(preg_replace('/[A-Z]/', '_$0', $input));
        $snake = preg_replace('/[^a-z0-9_]/', '', $snake);
        return ltrim($snake, '_');
    }
}
