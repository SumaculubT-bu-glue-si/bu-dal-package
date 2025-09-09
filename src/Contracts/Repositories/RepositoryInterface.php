<?php

namespace Bu\Server\Contracts\Repositories;

interface RepositoryInterface
{
    /**
     * Find a model by its primary key.
     *
     * @param mixed $id
     * @return mixed
     */
    public function find($id);

    /**
     * Get all records.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $columns = ['*']);

    /**
     * Create a new record.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data);

    /**
     * Update a record.
     *
     * @param mixed $id
     * @param array $data
     * @return mixed
     */
    public function update($id, array $data);

    /**
     * Delete a record.
     *
     * @param mixed $id
     * @return bool
     */
    public function delete($id);

    /**
     * Get paginated results.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, array $columns = ['*']);
}
