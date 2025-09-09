<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Models\Asset;
use Bu\Server\Http\Requests\AssetRequest;
use Illuminate\Http\Request;

class AssetController extends ApiController
{
    /**
     * Display a listing of assets
     */
    public function index(Request $request)
    {
        $query = Asset::query();

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $assets = $query->with(['location', 'employee'])->paginate($request->per_page ?? 15);

        return $this->successResponse($assets);
    }

    /**
     * Store a new asset
     */
    public function store(AssetRequest $request)
    {
        $asset = Asset::create($request->validated());

        return $this->successResponse($asset, 'Asset created successfully', 201);
    }

    /**
     * Display the specified asset
     */
    public function show(Asset $asset)
    {
        $asset->load(['location', 'employee']);
        
        return $this->successResponse($asset);
    }

    /**
     * Update the specified asset
     */
    public function update(AssetRequest $request, Asset $asset)
    {
        $asset->update($request->validated());

        return $this->successResponse($asset, 'Asset updated successfully');
    }

    /**
     * Remove the specified asset
     */
    public function destroy(Asset $asset)
    {
        $asset->delete();

        return $this->successResponse(null, 'Asset deleted successfully');
    }

    /**
     * Get assets by type
     */
    public function getByType($type)
    {
        $assets = Asset::where('type', $type)
            ->with(['location', 'employee'])
            ->paginate(15);

        return $this->successResponse($assets);
    }
}
