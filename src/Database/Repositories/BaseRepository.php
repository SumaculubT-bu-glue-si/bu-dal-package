<?php

namespace Bu\DAL\Database\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Find a record by ID.
     */
    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /**
     * Find a record by ID or fail.
     */
    public function findOrFail(int $id): Model
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Create a new record.
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update a record by ID.
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        return $model->update($data);
    }

    /**
     * Delete a record by ID.
     */
    public function delete(int $id): bool
    {
        $model = $this->find($id);
        if (!$model) {
            return false;
        }
        return $model->delete();
    }

    /**
     * Get paginated results.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Get records with conditions.
     */
    public function where(string $column, $operator, $value = null): Collection
    {
        if ($value === null) {
            return $this->model->where($column, $operator)->get();
        }
        return $this->model->where($column, $operator, $value)->get();
    }

    /**
     * Get first record with conditions.
     */
    public function whereFirst(string $column, $operator, $value = null): ?Model
    {
        if ($value === null) {
            return $this->model->where($column, $operator)->first();
        }
        return $this->model->where($column, $operator, $value)->first();
    }

    /**
     * Count records with conditions.
     */
    public function count(?string $column = null, $operator = null, $value = null): int
    {
        if ($column === null) {
            return $this->model->count();
        }

        if ($value === null) {
            return $this->model->where($column, $operator)->count();
        }
        return $this->model->where($column, $operator, $value)->count();
    }

    /**
     * Get the model instance.
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
