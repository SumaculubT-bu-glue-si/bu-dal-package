<?php

namespace Bu\Server\GraphQL\Queries;

use Bu\Server\Models\Location;

class LocationQueries
{
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
