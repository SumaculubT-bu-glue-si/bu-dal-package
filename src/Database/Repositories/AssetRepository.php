<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\Asset;
use YourCompany\GraphQLDAL\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AssetRepository extends BaseRepository
{
    protected string $modelClass = Asset::class;

    /**
     * Find asset by asset_id.
     */
    public function findByAssetId(string $assetId): ?Asset
    {
        return $this->newQuery()->where('asset_id', $assetId)->first();
    }

    /**
     * Find assets by asset_ids.
     */
    public function findByAssetIds(array $assetIds): Collection
    {
        return $this->newQuery()->whereIn('asset_id', $assetIds)->get();
    }

    /**
     * Upsert asset by asset_id.
     */
    public function upsertByAssetId(array $data): Asset
    {
        return $this->dbManager->transaction(function () use ($data) {
            $assetId = $data['asset_id'];
            unset($data['asset_id']);

            return $this->getModel()->updateOrCreate(
                ['asset_id' => $assetId],
                $data
            );
        });
    }

    /**
     * Bulk upsert assets by asset_id.
     */
    public function bulkUpsertByAssetId(array $assetsData): Collection
    {
        return $this->dbManager->transaction(function () use ($assetsData) {
            $results = collect();

            foreach ($assetsData as $assetData) {
                $assetId = $assetData['asset_id'];
                unset($assetData['asset_id']);

                $asset = $this->getModel()->updateOrCreate(
                    ['asset_id' => $assetId],
                    $assetData
                );

                $results->push($asset);
            }

            return $results;
        });
    }

    /**
     * Delete asset by asset_id.
     */
    public function deleteByAssetId(string $assetId): bool
    {
        return $this->dbManager->transaction(function () use ($assetId) {
            $asset = $this->findByAssetId($assetId);
            if (!$asset) {
                return true; // Already deleted or doesn't exist
            }

            return $asset->delete();
        });
    }

    /**
     * Get assets by type.
     */
    public function getByType(string $type): Collection
    {
        return $this->newQuery()->where('type', $type)->get();
    }

    /**
     * Get assets by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->newQuery()->where('status', $status)->get();
    }

    /**
     * Get assets by location.
     */
    public function getByLocation(string $location): Collection
    {
        return $this->newQuery()->where('location', $location)->get();
    }

    /**
     * Get assets by user ID.
     */
    public function getByUserId(int $userId): Collection
    {
        return $this->newQuery()->where('user_id', $userId)->get();
    }

    /**
     * Get assets by multiple criteria.
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
     * Search assets with global search.
     */
    public function search(string $searchTerm): Collection
    {
        return $this->newQuery()
            ->where(function ($query) use ($searchTerm) {
                $query->where('asset_id', 'like', "%{$searchTerm}%")
                    ->orWhere('hostname', 'like', "%{$searchTerm}%")
                    ->orWhere('manufacturer', 'like', "%{$searchTerm}%")
                    ->orWhere('model', 'like', "%{$searchTerm}%")
                    ->orWhere('serial_number', 'like', "%{$searchTerm}%")
                    ->orWhere('notes', 'like', "%{$searchTerm}%");
            })
            ->get();
    }

    /**
     * Get available assets (not assigned).
     */
    public function getAvailable(): Collection
    {
        return $this->newQuery()
            ->whereIn('status', ['保管中', '保管(使用無)', '返却済'])
            ->get();
    }

    /**
     * Get assigned assets.
     */
    public function getAssigned(): Collection
    {
        return $this->newQuery()
            ->whereIn('status', ['利用中', '貸出中', '利用予約'])
            ->get();
    }

    /**
     * Get assets with employee information.
     */
    public function getWithEmployee(int $assetId): ?Asset
    {
        return $this->newQuery()
            ->with('employee')
            ->find($assetId);
    }

    /**
     * Get assets by employee name.
     */
    public function getByEmployeeName(string $employeeName): Collection
    {
        return $this->newQuery()
            ->whereHas('employee', function ($query) use ($employeeName) {
                $query->where('name', 'like', "%{$employeeName}%");
            })
            ->get();
    }

    /**
     * Get assets for audit by location names.
     */
    public function getForAuditByLocations(array $locationNames): Collection
    {
        return $this->newQuery()
            ->whereIn('location', $locationNames)
            ->get();
    }

    /**
     * Get assets with audit information.
     */
    public function getWithAuditAssets(int $assetId): ?Asset
    {
        return $this->newQuery()
            ->with('auditAssets')
            ->find($assetId);
    }

    /**
     * Get paginated assets with filtering.
     */
    public function getPaginated(array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->newQuery();

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }

        if (isset($filters['locations']) && is_array($filters['locations'])) {
            $query->whereIn('location', $filters['locations']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['employee_name'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['employee_name']}%");
            });
        }

        if (isset($filters['global'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('asset_id', 'like', "%{$filters['global']}%")
                    ->orWhere('hostname', 'like', "%{$filters['global']}%")
                    ->orWhere('manufacturer', 'like', "%{$filters['global']}%")
                    ->orWhere('model', 'like', "%{$filters['global']}%")
                    ->orWhere('serial_number', 'like', "%{$filters['global']}%");
            });
        }

        // Apply sorting
        if (isset($filters['sort_field']) && isset($filters['sort_direction'])) {
            $query->orderBy($filters['sort_field'], $filters['sort_direction']);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get asset statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $byType = $this->newQuery()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $byStatus = $this->newQuery()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $assigned = $this->getAssigned()->count();
        $available = $this->getAvailable()->count();

        return [
            'total' => $total,
            'assigned' => $assigned,
            'available' => $available,
            'by_type' => $byType,
            'by_status' => $byStatus,
        ];
    }
}
