<?php

namespace Webmin;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class Database
{
    private PDO $connection;
    private ?LoggerInterface $logger;

    public function __construct(string $dsn, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        try {
            $this->connection = new PDO($dsn);
            $this->connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $this->connection->exec('PRAGMA foreign_keys = ON');

        } catch (PDOException $e) {
            $this->logger?->error("Failed to connect to the SQLite database: " . $e->getMessage());
            throw $e;
        }
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        if ($this->connection->inTransaction()) {
            $this->connection->rollBack();
        }
    }

    public function execute(string $query, array $params = []): int
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logger?->error("Execution failed: " . $e->getMessage(), ['query' => $query]);
            throw $e;
        }
    }

    public function query(string $query, array $params = []): array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger?->error("Query failed: " . $e->getMessage(), ['query' => $query]);
            throw $e;
        }
    }

    public function queryOne(string $query, array $params = []): ?array
    {
        $results = $this->query($query, $params);
        return !empty($results) ? $results[0] : null;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
