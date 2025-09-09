<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\Asset;
use Bu\Server\Models\AuditLog;
use Illuminate\Support\Arr;

class AssetMutations
{
    /**
     * Upsert a single asset by asset_id.
     */
    public function upsertAsset($_, array $args): Asset
    {
        $input = $args['asset'];
        $existingAsset = Asset::where('asset_id', $input['asset_id'])->first();
        $oldValues = $existingAsset ? $existingAsset->toArray() : [];

        $asset = Asset::updateOrCreate(
            ['asset_id' => $input['asset_id']],
            Arr::except($input, ['asset_id'])
        );

        // Create audit log
        AuditLog::create([
            'asset_id' => $asset->id,
            'audit_plan_id' => null,  // explicitly set to null since it's a direct asset operation
            'action' => $existingAsset ? 'updated' : 'created',
            'old_values' => $oldValues,
            'new_values' => $asset->toArray(),
            'performed_by' => 'system',  // temporarily hardcoded until auth is implemented
        ]);

        return $asset;
    }

    /**
     * Upsert many assets at once. Returns the list of upserted assets.
     */
    public function bulkUpsertAssets($_, array $args): array
    {
        $inputs = $args['assets'];
        $assetIds = [];

        foreach ($inputs as $input) {
            $existingAsset = Asset::where('asset_id', $input['asset_id'])->first();
            $oldValues = $existingAsset ? $existingAsset->toArray() : [];

            $asset = Asset::updateOrCreate(
                ['asset_id' => $input['asset_id']],
                Arr::except($input, ['asset_id'])
            );

            // Create audit log
            AuditLog::create([
                'asset_id' => $asset->id,
                'audit_plan_id' => null,  // explicitly set to null since it's a direct asset operation
                'action' => $existingAsset ? 'updated' : 'created',
                'old_values' => $oldValues,
                'new_values' => $asset->toArray(),
                'performed_by' => 'system',  // temporarily hardcoded until auth is implemented
            ]);

            $assetIds[] = $input['asset_id'];
        }

        return Asset::whereIn('asset_id', $assetIds)->get()->all();
    }

    /**
     * Delete an asset by asset_id.
     */
    public function deleteAsset($_, array $args): bool
    {
        $assetId = $args['asset_id'];
        $asset = Asset::where('asset_id', $assetId)->first();
        if (!$asset) {
            return true;
        }
        return (bool) $asset->delete();
    }
}
