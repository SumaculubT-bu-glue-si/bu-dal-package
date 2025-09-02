<?php

namespace YourCompany\GraphQLDAL\GraphQL\Mutations;

use YourCompany\GraphQLDAL\Models\Asset;
use YourCompany\GraphQLDAL\Database\Repositories\AssetRepository;
use Illuminate\Support\Arr;

class AssetMutations
{
    public function __construct(
        private AssetRepository $assetRepository
    ) {}

    /**
     * Upsert a single asset by asset_id.
     */
    public function upsertAsset($_, array $args): Asset
    {
        $input = $args['asset'];

        return $this->assetRepository->upsertByAssetId($input);
    }

    /**
     * Upsert many assets at once. Returns the list of upserted assets.
     */
    public function bulkUpsertAssets($_, array $args): array
    {
        $inputs = $args['assets'];

        $assets = $this->assetRepository->bulkUpsertByAssetId($inputs);

        return $assets->all();
    }

    /**
     * Delete an asset by asset_id.
     */
    public function deleteAsset($_, array $args): bool
    {
        $assetId = $args['asset_id'];

        return $this->assetRepository->deleteByAssetId($assetId);
    }
}
