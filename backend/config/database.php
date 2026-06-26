<?php
declare(strict_types=1);



/**
 * Retourne une instance PDO unique pour toute l'application.
 *
 * @return PDO
 * @throws RuntimeException Si la connexion à la base échoue.
 */
function getDatabaseConnection(): PDO
{
    static $connection = null;

    if ($connection !== null) {
        return $connection;
    }

    $host = 'localhost';
    $port = 3306;
    $dbname = 'mns_corporate_db';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $port,
        $dbname,
        $charset
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $connection = new PDO($dsn, $username, $password, $options);
        return $connection;
    } catch (PDOException $exception) {
        // Ne pas exposer les détails techniques ni les identifiants au navigateur.
        throw new RuntimeException('Impossible de se connecter à la base de données.');
    }
}
