<?php

namespace Bu\DAL\Database\Repositories;

use Bu\DAL\Models\Location;
use Illuminate\Database\Eloquent\Collection;

class LocationRepository extends BaseRepository
{
    public function __construct(Location $model)
    {
        parent::__construct($model);
    }

    /**
     * Find location by name.
     */
    public function findByName(string $name): ?Location
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Get visible locations.
     */
    public function getVisible(): Collection
    {
        return $this->model->where('visible', true)->orderBy('order')->get();
    }

    /**
     * Get locations by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get locations by city.
     */
    public function getByCity(string $city): Collection
    {
        return $this->model->where('city', 'like', "%{$city}%")->get();
    }

    /**
     * Search locations by name.
     */
    public function searchByName(string $name): Collection
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    /**
     * Get location names by IDs.
     */
    public function getNamesByIds(array $ids): array
    {
        return $this->model->whereIn('id', $ids)->pluck('name')->toArray();
    }

    /**
     * Upsert location by name.
     */
    public function upsertByName(array $data): Location
    {
        return $this->model->updateOrCreate(
            ['name' => $data['name']],
            $data
        );
    }
}
