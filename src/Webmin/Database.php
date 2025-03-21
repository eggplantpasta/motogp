<?php

namespace Webmin;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    /**
     * Constructor to initialize the SQLite database connection.
     *
     * @param string $dsn The Data Source Name (e.g., "sqlite:/path/to/database.db").
     * @throws PDOException If the connection fails.
     */
    public function __construct(string $dsn)
    {
        try {
            $this->connection = new PDO($dsn);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException("Failed to connect to the SQLite database: " . $e->getMessage());
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
            throw new PDOException("Query failed: " . $e->getMessage());
        }
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