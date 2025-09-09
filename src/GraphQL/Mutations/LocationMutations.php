<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\Location;
use Illuminate\Support\Facades\Validator;

class LocationMutations
{
    /**
     * Create a new location.
     */
    public function create($rootValue, array $args)
    {
        $input = $args['location'];
        
        $validator = Validator::make($input, [
            'name' => 'required|string|max:255|unique:locations,name',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'visible' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $location = Location::create($input);
        
        return $location;
    }

    /**
     * Update an existing location.
     */
    public function update($rootValue, array $args)
    {
        $id = $args['id'];
        $input = $args['location'];
        
        $location = Location::find($id);
        
        if (!$location) {
            throw new \Exception('Location not found');
        }

        $validator = Validator::make($input, [
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'manager' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'visible' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->first());
        }

        $location->update($input);
        
        return $location;
    }

    /**
     * Delete a location.
     */
    public function delete($rootValue, array $args)
    {
        $id = $args['id'];
        
        $location = Location::find($id);
        
        if (!$location) {
            throw new \Exception('Location not found');
        }

        // Check if location is being used by any assets
        if ($location->assets()->count() > 0) {
            throw new \Exception('Cannot delete location that has assets assigned to it');
        }

        $location->delete();
        
        return true;
    }

    /**
     * Create or update a location (upsert).
     */
    public function upsertLocation($rootValue, array $args)
    {
        $input = $args['location'];
        
        if (isset($input['id'])) {
            return $this->update($rootValue, ['id' => $input['id'], 'location' => $input]);
        } else {
            return $this->create($rootValue, ['location' => $input]);
        }
    }
}
