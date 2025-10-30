<?php

namespace Bu\Server\GraphQL\Queries;

use Bu\Server\Models\Asset;

class AssetDistributionQueries
{
    /**
     * Returns asset counts grouped by location and type.
     * Example output:
     * [
     *   ['location' => 'Tokyo', 'pcs' => 10, 'monitors' => 5, 'phones' => 2, 'others' => 1, 'total' => 18],
     *   ...
     * ]
     */
    public function assetCountsByLocation(): array
    {
        $locations = Asset::query()
            ->select('location')
            ->distinct()
            ->pluck('location')
            ->filter()
            ->values();

        $result = [];
        foreach ($locations as $location) {
            $pcs = Asset::where('location', $location)->where('type', 'pc')->count();
            $monitors = Asset::where('location', $location)->where('type', 'monitor')->count();
            $phones = Asset::where('location', $location)->where('type', 'smartphones')->count();
            $others = Asset::where('location', $location)->whereNotIn('type', ['pc', 'monitor', 'smartphones'])->count();
            $total = $pcs + $monitors + $phones + $others;
            $result[] = [
                'location' => $location,
                'pcs' => $pcs,
                'monitors' => $monitors,
                'phones' => $phones,
                'others' => $others,
                'total' => $total,
            ];
        }
        return $result;
    }
}
