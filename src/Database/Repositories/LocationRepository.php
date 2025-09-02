<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\Location;
use Illuminate\Database\Eloquent\Collection;

class LocationRepository extends BaseRepository
{
    protected string $modelClass = Location::class;

    /**
     * Get locations by name.
     */
    public function getByName(string $name): ?Location
    {
        return $this->newQuery()->where('name', $name)->first();
    }

    /**
     * Get locations by names.
     */
    public function getByNames(array $names): Collection
    {
        return $this->newQuery()->whereIn('name', $names)->get();
    }

    /**
     * Get location names by IDs.
     */
    public function getNamesByIds(array $ids): array
    {
        return $this->newQuery()
            ->whereIn('id', $ids)
            ->pluck('name')
            ->toArray();
    }

    /**
     * Get visible locations.
     */
    public function getVisible(): Collection
    {
        return $this->newQuery()
            ->where('visible', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Get locations by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->newQuery()->where('status', $status)->get();
    }

    /**
     * Get locations by city.
     */
    public function getByCity(string $city): Collection
    {
        return $this->newQuery()
            ->where('city', 'like', "%{$city}%")
            ->get();
    }

    /**
     * Search locations.
     */
    public function search(string $searchTerm): Collection
    {
        return $this->newQuery()
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('address', 'like', "%{$searchTerm}%")
                    ->orWhere('city', 'like', "%{$searchTerm}%")
                    ->orWhere('manager', 'like', "%{$searchTerm}%");
            })
            ->get();
    }

    /**
     * Get locations with assets.
     */
    public function getWithAssets(int $locationId): ?Location
    {
        return $this->newQuery()
            ->with('assets')
            ->find($locationId);
    }

    /**
     * Get locations with audit assignments.
     */
    public function getWithAuditAssignments(int $locationId): ?Location
    {
        return $this->newQuery()
            ->with('auditAssignments')
            ->find($locationId);
    }

    /**
     * Get locations by multiple criteria.
     */
    public function getByCriteria(array $criteria): Collection
    {
        $query = $this->newQuery();

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        return $query->get();
    }

    /**
     * Get location statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $visible = $this->newQuery()->where('visible', true)->count();

        $byStatus = $this->newQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->whereNotNull('status')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $byCity = $this->newQuery()
            ->selectRaw('city, COUNT(*) as count')
            ->whereNotNull('city')
            ->groupBy('city')
            ->pluck('count', 'city')
            ->toArray();

        return [
            'total' => $total,
            'visible' => $visible,
            'by_status' => $byStatus,
            'by_city' => $byCity,
        ];
    }

    /**
     * Get locations ordered by display order.
     */
    public function getOrdered(): Collection
    {
        return $this->newQuery()
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }
}
