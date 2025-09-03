<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\Asset;
use Bu\DAL\Database\Repositories\AssetRepository;
use Illuminate\Database\Eloquent\Builder;

class AssetQueries
{
    public function __construct(
        private AssetRepository $assetRepository
    ) {}

    /**
     * Resolve the employee field for an asset.
     */
    public function employee(Asset $asset)
    {
        return $asset->employee;
    }

    /**
     * Builder for assets pagination with rich filters.
     * This is referenced by schema.graphql via @paginate(builder: ...).
     */
    public function assetsBuilder($root, array $args): Builder
    {
        $query = Asset::query();

        // Exact type
        if (!empty($args['type'])) {
            $query->where('type', $args['type']);
        }

        // Exclude types (for "others" category)
        if (!empty($args['exclude_types']) && is_array($args['exclude_types'])) {
            $query->whereNotIn('type', $args['exclude_types']);
        }

        // Statuses (multi)
        if (!empty($args['statuses']) && is_array($args['statuses'])) {
            $query->whereIn('status', $args['statuses']);
        }

        // Locations (multi)
        if (!empty($args['locations']) && is_array($args['locations'])) {
            $query->whereIn('location', $args['locations']);
        }

        // User ID exact
        if (!empty($args['user_id'])) {
            $query->where('user_id', $args['user_id']);
        }

        // Employee name like via relation
        if (!empty($args['employee_name'])) {
            $name = $args['employee_name'];
            $query->whereHas('employee', function (Builder $q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            });
        }

        // Column LIKE helpers
        $likeColumns = [
            'asset_id',
            'hostname',
            'manufacturer',
            'model',
            'part_number',
            'serial_number',
            'form_factor',
            'os',
            'os_bit',
            'office_suite',
            'software_license_key',
            'wired_mac_address',
            'wired_ip_address',
            'wireless_mac_address',
            'wireless_ip_address',
            'previous_user',
            'project',
            'notes',
            'notes1',
            'notes2',
            'notes3',
            'notes4',
            'notes5',
            'cpu',
            'memory',
        ];
        foreach ($likeColumns as $col) {
            if (!empty($args[$col])) {
                $query->where($col, 'like', '%' . $args[$col] . '%');
            }
        }

        // Global text search across common textual columns
        if (!empty($args['global'])) {
            $global = $args['global'];
            $query->where(function (Builder $q) use ($global) {
                $q->orWhere('asset_id', 'like', "%{$global}%")
                    ->orWhere('hostname', 'like', "%{$global}%")
                    ->orWhere('manufacturer', 'like', "%{$global}%")
                    ->orWhere('model', 'like', "%{$global}%")
                    ->orWhere('serial_number', 'like', "%{$global}%")
                    ->orWhere('location', 'like', "%{$global}%")
                    ->orWhere('status', 'like', "%{$global}%")
                    ->orWhere('previous_user', 'like', "%{$global}%")
                    ->orWhere('project', 'like', "%{$global}%")
                    ->orWhere('notes', 'like', "%{$global}%");
            });
        }

        // Sorting
        $allowedSortFields = [
            'asset_id',
            'type',
            'hostname',
            'manufacturer',
            'model',
            'serial_number',
            'location',
            'status',
            'user_id',
            'project',
            'last_updated',
            'created_at',
            'updated_at'
        ];
        $sortField = in_array($args['sort_field'] ?? '', $allowedSortFields, true) ? $args['sort_field'] : 'asset_id';
        $sortDirection = strtolower($args['sort_direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $sortDirection);

        return $query;
    }
}
