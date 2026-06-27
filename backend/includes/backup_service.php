<?php
declare(strict_types=1);

function streamSqlBackup(PDO $pdo, string $databaseName): void
{
    header('Content-Type: application/sql; charset=UTF-8');
    header('Content-Disposition: attachment; filename="mns_corporate_backup_' . date('Ymd_His') . '.sql"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "-- MNS CORPORATE\n";
    echo "-- Sauvegarde generee depuis l'interface administration\n";
    echo "-- Date de creation : " . date('Y-m-d H:i:s') . "\n\n";
    echo "CREATE DATABASE IF NOT EXISTS `" . backupEscapeIdentifier($databaseName) . "` DEFAULT CHARACTER SET utf8mb4;\n";
    echo "USE `" . backupEscapeIdentifier($databaseName) . "`;\n\n";

    $tables = backupTables($pdo);
    foreach ($tables as $table) {
        $safeTable = backupEscapeIdentifier($table);
        $create = backupCreateTable($pdo, $table);
        if ($create === null) {
            continue;
        }
        echo "DROP TABLE IF EXISTS `" . $safeTable . "`;\n";
        echo $create . ";\n\n";
        backupStreamTableData($pdo, $table);
        echo "\n";
    }
}

function backupTables(PDO $pdo): array
{
    $stmt = $pdo->prepare('SHOW TABLES');
    $stmt->execute();
    return array_map(static fn(array $row): string => (string) array_values($row)[0], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function backupCreateTable(PDO $pdo, string $table): ?string
{
    $stmt = $pdo->prepare('SHOW CREATE TABLE `' . backupEscapeIdentifier($table) . '`');
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) {
        return null;
    }
    return (string) ($row['Create Table'] ?? array_values($row)[1] ?? '');
}

function backupStreamTableData(PDO $pdo, string $table): void
{
    $safeTable = backupEscapeIdentifier($table);
    $stmt = $pdo->prepare('SELECT * FROM `' . $safeTable . '`');
    $stmt->execute();
    $columns = [];
    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $meta = $stmt->getColumnMeta($i);
        $columns[] = '`' . backupEscapeIdentifier((string) $meta['name']) . '`';
    }

    while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
        $values = [];
        foreach ($row as $value) {
            if ($value === null) {
                $values[] = 'NULL';
            } elseif (is_int($value) || is_float($value)) {
                $values[] = (string) $value;
            } else {
                $values[] = $pdo->quote((string) $value);
            }
        }
        echo 'INSERT INTO `' . $safeTable . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
    }
}

function backupEscapeIdentifier(string $identifier): string
{
    return str_replace('`', '``', $identifier);
}
