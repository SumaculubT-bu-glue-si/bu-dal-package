<?php

namespace YourCompany\GraphQLDAL\Database;

use YourCompany\GraphQLDAL\Exceptions\DatabaseException;

class TransactionManager
{
    protected DatabaseManager $dbManager;
    protected array $transactionStack = [];
    protected int $transactionLevel = 0;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Execute a callback within a database transaction.
     */
    public function transaction(callable $callback, int $attempts = 1)
    {
        return $this->dbManager->transaction($callback, $attempts);
    }

    /**
     * Begin a new transaction with savepoint support.
     */
    public function beginTransaction(?string $savepoint = null): void
    {
        if ($savepoint) {
            $this->transactionStack[] = $savepoint;
            $this->dbManager->getConnection()->statement("SAVEPOINT {$savepoint}");
        } else {
            $this->dbManager->beginTransaction();
        }

        $this->transactionLevel++;
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        if ($this->transactionLevel <= 0) {
            throw new DatabaseException('No active transaction to commit');
        }

        if (!empty($this->transactionStack)) {
            $savepoint = array_pop($this->transactionStack);
            $this->dbManager->getConnection()->statement("RELEASE SAVEPOINT {$savepoint}");
        } else {
            $this->dbManager->commit();
        }

        $this->transactionLevel--;
    }

    /**
     * Rollback the current transaction.
     */
    public function rollback(?string $savepoint = null): void
    {
        if ($this->transactionLevel <= 0) {
            throw new DatabaseException('No active transaction to rollback');
        }

        if ($savepoint) {
            $this->dbManager->getConnection()->statement("ROLLBACK TO SAVEPOINT {$savepoint}");
        } elseif (!empty($this->transactionStack)) {
            $savepoint = array_pop($this->transactionStack);
            $this->dbManager->getConnection()->statement("ROLLBACK TO SAVEPOINT {$savepoint}");
        } else {
            $this->dbManager->rollback();
        }

        $this->transactionLevel--;
    }

    /**
     * Get the current transaction level.
     */
    public function getTransactionLevel(): int
    {
        return $this->transactionLevel;
    }

    /**
     * Check if we're currently in a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->transactionLevel > 0;
    }

    /**
     * Execute multiple operations in a single transaction.
     */
    public function batch(array $operations): array
    {
        return $this->transaction(function () use ($operations) {
            $results = [];

            foreach ($operations as $operation) {
                if (is_callable($operation)) {
                    $results[] = $operation();
                } else {
                    throw new DatabaseException('Batch operation must be callable');
                }
            }

            return $results;
        });
    }

    /**
     * Execute operations with automatic rollback on any failure.
     */
    public function atomic(callable $callback)
    {
        return $this->transaction(function () use ($callback) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $this->rollback();
                throw $e;
            }
        });
    }
}
