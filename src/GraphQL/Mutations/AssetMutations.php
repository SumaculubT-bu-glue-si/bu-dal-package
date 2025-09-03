<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\Asset;
use Bu\DAL\Database\Repositories\AssetRepository;
use Bu\DAL\Database\DatabaseManager;
use Illuminate\Support\Arr;

class AssetMutations
{
    public function __construct(
        private AssetRepository $assetRepository,
        private DatabaseManager $databaseManager
    ) {}

    /**
     * Upsert a single asset by asset_id.
     */
    public function upsertAsset($_, array $args): Asset
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['asset'];
            return $this->assetRepository->upsertByAssetId($input);
        });
    }

    /**
     * Upsert many assets at once. Returns the list of upserted assets.
     */
    public function bulkUpsertAssets($_, array $args): array
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $inputs = $args['assets'];
            $results = $this->assetRepository->bulkUpsert($inputs);
            return $results->all();
        });
    }

    /**
     * Delete an asset by asset_id.
     */
    public function deleteAsset($_, array $args): bool
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $assetId = $args['asset_id'];
            return $this->assetRepository->deleteByAssetId($assetId);
        });
    }
}
