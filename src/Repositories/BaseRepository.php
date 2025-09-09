<?php

namespace Bu\Server\Repositories;

use Bu\Server\Contracts\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*'])
    {
        return $this->model->all($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update($id, array $data)
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($data);
            return $record;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate($perPage = 15, array $columns = ['*'])
    {
        return $this->model->paginate($perPage, $columns);
    }
}
