<?php

declare(strict_types=1);

final class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $host = $config['host'] ?? 'localhost';
        $name = $config['name'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $user = $config['user'] ?? '';
        $pass = $config['pass'] ?? '';

        $this->pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset={$charset}",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function lastId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }
}

