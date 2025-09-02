<?php

namespace YourCompany\GraphQLDAL\Database;

use Illuminate\Database\DatabaseManager as LaravelDatabaseManager;
use Illuminate\Database\ConnectionInterface;
use YourCompany\GraphQLDAL\Exceptions\DatabaseException;

class DatabaseManager
{
    protected LaravelDatabaseManager $dbManager;
    protected array $connections = [];
    protected ?string $defaultConnection = null;

    public function __construct(LaravelDatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
        $this->defaultConnection = config('database.default');
    }

    /**
     * Get a database connection instance.
     */
    public function getConnection(?string $name = null): ConnectionInterface
    {
        $name = $name ?: $this->defaultConnection;

        if (!isset($this->connections[$name])) {
            try {
                $this->connections[$name] = $this->dbManager->connection($name);
            } catch (\Exception $e) {
                throw new DatabaseException("Failed to connect to database '{$name}': " . $e->getMessage());
            }
        }

        return $this->connections[$name];
    }

    /**
     * Execute a transaction with automatic rollback on failure.
     */
    public function transaction(callable $callback, int $attempts = 1)
    {
        return $this->getConnection()->transaction($callback, $attempts);
    }

    /**
     * Begin a new database transaction.
     */
    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Commit the active database transaction.
     */
    public function commit(): void
    {
        $this->getConnection()->commit();
    }

    /**
     * Rollback the active database transaction.
     */
    public function rollback(): void
    {
        $this->getConnection()->rollback();
    }

    /**
     * Execute a query with automatic retry on connection failure.
     */
    public function executeWithRetry(callable $callback, int $maxRetries = 3)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxRetries) {
            try {
                return $callback($this->getConnection());
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                // Clear the connection cache on failure
                unset($this->connections[$this->defaultConnection]);

                if ($attempts < $maxRetries) {
                    sleep(1); // Wait 1 second before retry
                }
            }
        }

        throw new DatabaseException("Database operation failed after {$maxRetries} attempts: " . $lastException->getMessage());
    }

    /**
     * Get connection health status.
     */
    public function isHealthy(?string $connection = null): bool
    {
        try {
            $conn = $this->getConnection($connection);
            // Use a simple query to test the connection
            $conn->select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all available connections.
     */
    public function getAvailableConnections(): array
    {
        return array_keys(config('database.connections', []));
    }

    /**
     * Set the default connection.
     */
    public function setDefaultConnection(string $connection): void
    {
        $this->defaultConnection = $connection;
    }

    /**
     * Get the default connection name.
     */
    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }
}
