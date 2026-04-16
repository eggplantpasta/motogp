<?php

namespace Webmin;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class Database
{
    private PDO $connection;
    private ?LoggerInterface $logger;

    /**
     * Constructor to initialize the SQLite database connection.
     *
     * @param string $dsn The Data Source Name (e.g., "sqlite:/path/to/database.db").
     * @param LoggerInterface|null $logger Optional logger instance.
     * @throws PDOException If the connection fails.
     */
    public function __construct(string $dsn, ?LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        try {
            $this->connection = new PDO($dsn);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->logger?->error("Failed to connect to the SQLite database: " . $e->getMessage());
            throw new PDOException("Failed to connect to the SQLite database: " . $e->getMessage());
        }
    }

    /**
     * Begin a database transaction.
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Execute a query without fetching results (useful for INSERT/UPDATE/DELETE).
     * Returns the number of affected rows.
     */
    public function execute(string $query, array $params = []): int
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logger?->error("Execution failed: " . $e->getMessage(), ['query' => $query]);
            throw new PDOException("Execution failed: " . $e->getMessage());
        }
    }

    /**
     * Execute a query and return the results.
     *
     * @param string $query The SQL query to execute.
     * @param array $params Optional parameters for prepared statements.
     * @return array The query results.
     */
    public function query(string $query, array $params = []): array
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger?->error("Query failed: " . $e->getMessage(), ['query' => $query]);
            throw new PDOException("Query failed: " . $e->getMessage());
        }
    }

    public function queryOne(string $query, array $params = []): ?array
    {
        $results = $this->query($query, $params);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Get the PDO connection instance.
     *
     * @return PDO The PDO connection.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
