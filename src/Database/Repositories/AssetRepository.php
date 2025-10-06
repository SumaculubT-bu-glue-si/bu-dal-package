<?php

namespace Bu\Server\Database\Repositories;

use Bu\Server\Models\Asset;
use Illuminate\Database\Eloquent\Collection;

class AssetRepository extends BaseRepository
{
    public function __construct(Asset $model)
    {
        parent::__construct($model);
    }

    /**
     * Find asset by asset_id.
     */
    public function findByAssetId(string $assetId): ?Asset
    {
        return $this->model->where('asset_id', $assetId)->first();
    }

    /**
     * Upsert asset by asset_id.
     */
    public function upsertByAssetId(array $data): Asset
    {
        return $this->model->updateOrCreate(
            ['asset_id' => $data['asset_id']],
            $data
        );
    }

    /**
     * Get assets by type.
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Get assets by location.
     */
    public function getByLocation(string $location): Collection
    {
        return $this->model->where('location', $location)->get();
    }

    /**
     * Get assets by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get assets by user ID.
     */
    public function getByUserId(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * Get available assets.
     */
    public function getAvailable(): Collection
    {
        return $this->model->whereIn('status', ['保管中', '保管(使用無)', '返却済'])->get();
    }

    /**
     * Get assigned assets.
     */
    public function getAssigned(): Collection
    {
        return $this->model->whereIn('status', ['利用中', '貸出中', '利用予約'])->get();
    }

    /**
     * Search assets with multiple criteria.
     */
    public function search(array $criteria): Collection
    {
        $query = $this->model->newQuery();

        if (isset($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        if (isset($criteria['statuses']) && is_array($criteria['statuses'])) {
            $query->whereIn('status', $criteria['statuses']);
        }

        if (isset($criteria['locations']) && is_array($criteria['locations'])) {
            $query->whereIn('location', $criteria['locations']);
        }

        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (isset($criteria['global'])) {
            $global = $criteria['global'];
            $query->where(function ($q) use ($global) {
                $q->where('asset_id', 'like', "%{$global}%")
                    ->orWhere('hostname', 'like', "%{$global}%")
                    ->orWhere('manufacturer', 'like', "%{$global}%")
                    ->orWhere('model', 'like', "%{$global}%")
                    ->orWhere('serial_number', 'like', "%{$global}%");
            });
        }

        return $query->get();
    }

    /**
     * Bulk upsert assets.
     */
    public function bulkUpsert(array $assets): Collection
    {
        $results = new Collection();

        foreach ($assets as $assetData) {
            $asset = $this->upsertByAssetId($assetData);
            $results->push($asset);
        }

        return $results;
    }

    /**
     * Delete asset by asset_id.
     */
    public function deleteByAssetId(string $assetId): bool
    {
        $asset = $this->findByAssetId($assetId);
        if (!$asset) {
            return true; // Already doesn't exist
        }
        return $asset->delete();
    }
}