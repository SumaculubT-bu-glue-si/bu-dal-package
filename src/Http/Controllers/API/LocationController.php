<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Models\Location;
use Bu\Server\Http\Requests\LocationRequest;
use Illuminate\Http\Request;

class LocationController extends ApiController
{
    /**
     * Display a listing of locations
     */
    public function index(Request $request)
    {
        $query = Location::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $locations = $query->with('parent')->paginate($request->per_page ?? 15);

        return $this->successResponse($locations);
    }

    /**
     * Store a new location
     */
    public function store(LocationRequest $request)
    {
        $location = Location::create($request->validated());

        return $this->successResponse($location, 'Location created successfully', 201);
    }

    /**
     * Display the specified location
     */
    public function show(Location $location)
    {
        $location->load(['parent', 'children', 'assets']);
        
        return $this->successResponse($location);
    }

    /**
     * Update the specified location
     */
    public function update(LocationRequest $request, Location $location)
    {
        $location->update($request->validated());

        return $this->successResponse($location, 'Location updated successfully');
    }

    /**
     * Remove the specified location
     */
    public function destroy(Location $location)
    {
        if ($location->children()->exists()) {
            return $this->errorResponse('Cannot delete location with sub-locations', 422);
        }

        if ($location->assets()->exists()) {
            return $this->errorResponse('Cannot delete location with assigned assets', 422);
        }

        $location->delete();

        return $this->successResponse(null, 'Location deleted successfully');
    }

    /**
     * Get location hierarchy
     */
    public function hierarchy()
    {
        $locations = Location::whereNull('parent_id')
            ->with('children.children')
            ->get();

        return $this->successResponse($locations);
    }
}
