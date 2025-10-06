<?php

namespace Bu\Server\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Connection;
use Bu\Server\Exceptions\DatabaseException;
use Bu\Server\Exceptions\TransactionException;

class DatabaseManager
{
    protected string $defaultConnection;
    protected array $connections = [];

    public function __construct()
    {
        $this->defaultConnection = Config::get('database.default', 'mysql');
        $this->connections = Config::get('database.connections', []);
    }

    /**
     * Get a database connection.
     */
    public function getConnection(?string $name = null): Connection
    {
        $connectionName = $name ?? $this->defaultConnection;

        if (!isset($this->connections[$connectionName])) {
            throw new DatabaseException("Database connection '{$connectionName}' not configured.");
        }

        return DB::connection($connectionName);
    }

    /**
     * Execute a transaction with automatic rollback on failure.
     */
    public function transaction(callable $callback, ?string $connection = null)
    {
        $connection = $this->getConnection($connection);

        try {
            return $connection->transaction($callback);
        } catch (\Exception $e) {
            throw new TransactionException("Transaction failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(?string $connection = null): void
    {
        $connection = $this->getConnection($connection);
        $connection->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    public function commit(?string $connection = null): void
    {
        $connection = $this->getConnection($connection);
        $connection->commit();
    }

    /**
     * Rollback a transaction.
     */
    public function rollback(?string $connection = null): void
    {
        $connection = $this->getConnection($connection);
        $connection->rollback();
    }

    /**
     * Check if currently in a transaction.
     */
    public function inTransaction(?string $connection = null): bool
    {
        $connection = $this->getConnection($connection);
        return $connection->transactionLevel() > 0;
    }

    /**
     * Execute a query with retry logic.
     */
    public function queryWithRetry(callable $callback, int $maxRetries = 3, ?string $connection = null)
    {
        $connection = $this->getConnection($connection);
        $attempts = 0;

        while ($attempts < $maxRetries) {
            try {
                return $callback($connection);
            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxRetries) {
                    throw new DatabaseException("Query failed after {$maxRetries} attempts: " . $e->getMessage(), 0, $e);
                }

                // Wait before retry (exponential backoff)
                usleep(pow(2, $attempts) * 100000); // 0.1s, 0.2s, 0.4s
            }
        }
    }

    /**
     * Test database connection.
     */
    public function testConnection(?string $connection = null): bool
    {
        try {
            $connection = $this->getConnection($connection);
            $connection->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get connection information.
     */
    public function getConnectionInfo(?string $connection = null): array
    {
        $connectionName = $connection ?? $this->defaultConnection;
        $config = $this->connections[$connectionName] ?? [];

        return [
            'name' => $connectionName,
            'driver' => $config['driver'] ?? 'unknown',
            'host' => $config['host'] ?? 'unknown',
            'database' => $config['database'] ?? 'unknown',
            'port' => $config['port'] ?? 'unknown',
        ];
    }

    /**
     * Get all available connections.
     */
    public function getAvailableConnections(): array
    {
        return array_keys($this->connections);
    }

    /**
     * Set default connection.
     */
    public function setDefaultConnection(string $connection): void
    {
        if (!isset($this->connections[$connection])) {
            throw new DatabaseException("Database connection '{$connection}' not configured.");
        }

        $this->defaultConnection = $connection;
        Config::set('database.default', $connection);
    }

    /**
     * Execute raw SQL query.
     */
    public function raw(string $sql, array $bindings = [], ?string $connection = null)
    {
        $connection = $this->getConnection($connection);
        return $connection->select($sql, $bindings);
    }

    /**
     * Get table information.
     */
    public function getTableInfo(string $table, ?string $connection = null): array
    {
        $connection = $this->getConnection($connection);

        try {
            $columns = $connection->getSchemaBuilder()->getColumnListing($table);
            $indexes = $connection->getSchemaBuilder()->getIndexes($table);

            return [
                'table' => $table,
                'columns' => $columns,
                'indexes' => $indexes,
            ];
        } catch (\Exception $e) {
            throw new DatabaseException("Failed to get table info for '{$table}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if table exists.
     */
    public function tableExists(string $table, ?string $connection = null): bool
    {
        $connection = $this->getConnection($connection);
        return $connection->getSchemaBuilder()->hasTable($table);
    }

    /**
     * Get database size (MySQL specific).
     */
    public function getDatabaseSize(?string $connection = null): ?string
    {
        /** @var Connection $dbConnection */
        $dbConnection = $this->getConnection($connection);

        if ($dbConnection->getDriverName() !== 'mysql') {
            return null;
        }

        try {
            $result = $dbConnection->selectOne("
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbConnection->getDatabaseName()]);

            return $result ? $result->size_mb . ' MB' : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}