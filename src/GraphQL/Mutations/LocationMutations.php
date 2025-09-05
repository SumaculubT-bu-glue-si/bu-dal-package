<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\Location;
use Bu\DAL\Database\Repositories\LocationRepository;
use Bu\DAL\Database\DatabaseManager;
use Illuminate\Support\Facades\Validator;

class LocationMutations
{
    public function __construct(
        private LocationRepository $locationRepository,
        private DatabaseManager $databaseManager
    ) {}

    /**
     * Create a new location.
     */
    public function create($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
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

            return $this->locationRepository->create($input);
        });
    }

    /**
     * Update an existing location.
     */
    public function update($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $id = $args['id'];
            $input = $args['location'];

            $location = $this->locationRepository->find($id);

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
        });
    }

    /**
     * Delete a location.
     */
    public function delete($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $id = $args['id'];

            $location = $this->locationRepository->find($id);

            if (!$location) {
                throw new \Exception('Location not found');
            }

            // Check if location is being used by any assets
            if ($location->assets()->count() > 0) {
                throw new \Exception('Cannot delete location that has assets assigned to it');
            }

            $this->locationRepository->delete($id);
            return true;
        });
    }

    /**
     * Create or update a location (upsert).
     */
    public function upsertLocation($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['location'];
            return $this->locationRepository->upsertByName($input);
        });
    }
}
