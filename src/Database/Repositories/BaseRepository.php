<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use YourCompany\GraphQLDAL\Database\DatabaseManager;
use YourCompany\GraphQLDAL\Exceptions\RepositoryException;

abstract class BaseRepository
{
    protected DatabaseManager $dbManager;
    protected string $modelClass;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    /**
     * Get the model instance.
     */
    protected function getModel(): Model
    {
        if (!class_exists($this->modelClass)) {
            throw new RepositoryException("Model class {$this->modelClass} does not exist");
        }

        return new $this->modelClass;
    }

    /**
     * Get a new query builder instance.
     */
    protected function newQuery()
    {
        return $this->getModel()->newQuery();
    }

    /**
     * Find a model by its primary key.
     */
    public function find(int|string $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    /**
     * Find a model by its primary key or throw an exception.
     */
    public function findOrFail(int|string $id): Model
    {
        return $this->newQuery()->findOrFail($id);
    }

    /**
     * Find models by an array of primary keys.
     */
    public function findMany(array $ids): Collection
    {
        return $this->newQuery()->whereIn($this->getModel()->getKeyName(), $ids)->get();
    }

    /**
     * Find the first model matching the given criteria.
     */
    public function findBy(array $criteria): ?Model
    {
        $query = $this->newQuery();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->first();
    }

    /**
     * Find all models matching the given criteria.
     */
    public function findAllBy(array $criteria): Collection
    {
        $query = $this->newQuery();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->get();
    }

    /**
     * Create a new model instance.
     */
    public function create(array $data): Model
    {
        return $this->dbManager->transaction(function () use ($data) {
            return $this->getModel()->create($data);
        });
    }

    /**
     * Update a model by its primary key.
     */
    public function update(int|string $id, array $data): bool
    {
        return $this->dbManager->transaction(function () use ($id, $data) {
            $model = $this->findOrFail($id);
            return $model->update($data);
        });
    }

    /**
     * Update or create a model.
     */
    public function updateOrCreate(array $criteria, array $data): Model
    {
        return $this->dbManager->transaction(function () use ($criteria, $data) {
            return $this->getModel()->updateOrCreate($criteria, $data);
        });
    }

    /**
     * Delete a model by its primary key.
     */
    public function delete(int|string $id): bool
    {
        return $this->dbManager->transaction(function () use ($id) {
            $model = $this->findOrFail($id);
            return $model->delete();
        });
    }

    /**
     * Delete models by criteria.
     */
    public function deleteBy(array $criteria): int
    {
        return $this->dbManager->transaction(function () use ($criteria) {
            $query = $this->newQuery();

            foreach ($criteria as $field => $value) {
                $query->where($field, $value);
            }

            return $query->delete();
        });
    }

    /**
     * Get all models.
     */
    public function all(): Collection
    {
        return $this->newQuery()->get();
    }

    /**
     * Get paginated results.
     */
    public function paginate(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->newQuery()->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Count models matching criteria.
     */
    public function count(array $criteria = []): int
    {
        $query = $this->newQuery();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->count();
    }

    /**
     * Check if a model exists.
     */
    public function exists(array $criteria): bool
    {
        $query = $this->newQuery();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->exists();
    }

    /**
     * Get the first model or create it.
     */
    public function firstOrCreate(array $criteria, array $data = []): Model
    {
        return $this->dbManager->transaction(function () use ($criteria, $data) {
            return $this->getModel()->firstOrCreate($criteria, $data);
        });
    }

    /**
     * Bulk create models.
     */
    public function bulkCreate(array $data): Collection
    {
        return $this->dbManager->transaction(function () use ($data) {
            $models = collect();

            foreach ($data as $item) {
                $models->push($this->getModel()->create($item));
            }

            return $models;
        });
    }

    /**
     * Bulk update models.
     */
    public function bulkUpdate(array $criteria, array $data): int
    {
        return $this->dbManager->transaction(function () use ($criteria, $data) {
            $query = $this->newQuery();

            foreach ($criteria as $field => $value) {
                $query->where($field, $value);
            }

            return $query->update($data);
        });
    }
}
