<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\Location;
use Bu\DAL\Database\Repositories\LocationRepository;

class LocationQueries
{
    public function __construct(
        private LocationRepository $locationRepository
    ) {}

    /**
     * Get all locations with optional filtering.
     */
    public function locations($rootValue, array $args)
    {
        $query = Location::query();

        // Apply filters if provided
        if (isset($args['name']) && $args['name']) {
            $query->where('name', 'like', '%' . $args['name'] . '%');
        }

        if (isset($args['status']) && $args['status']) {
            $query->where('status', $args['status']);
        }

        if (isset($args['city']) && $args['city']) {
            $query->where('city', 'like', '%' . $args['city'] . '%');
        }

        // Order by name by default
        $query->orderBy('name');

        return $query->get();
    }

    /**
     * Get a single location by ID.
     */
    public function location($rootValue, array $args)
    {
        $id = $args['id'];

        return Location::find($id);
    }
}
