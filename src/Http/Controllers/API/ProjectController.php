<?php

namespace Bu\Server\Http\Controllers\API;

use Bu\Server\Models\Project;
use Bu\Server\Http\Requests\ProjectRequest;
use Illuminate\Http\Request;

class ProjectController extends ApiController
{
    /**
     * Display a listing of projects
     */
    public function index(Request $request)
    {
        $query = Project::query();

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->has('location_id')) {
            $query->whereHas('locations', function($q) use ($request) {
                $q->where('locations.id', $request->location_id);
            });
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $projects = $query->with(['manager', 'locations'])
                         ->paginate($request->per_page ?? 15);

        return $this->successResponse($projects);
    }

    /**
     * Store a new project
     */
    public function store(ProjectRequest $request)
    {
        $project = Project::create($request->except('location_ids'));
        $project->locations()->sync($request->location_ids);

        $project->load(['manager', 'locations']);
        return $this->successResponse($project, 'Project created successfully', 201);
    }

    /**
     * Display the specified project
     */
    public function show(Project $project)
    {
        $project->load(['manager', 'locations', 'assets']);
        
        return $this->successResponse($project);
    }

    /**
     * Update the specified project
     */
    public function update(ProjectRequest $request, Project $project)
    {
        $project->update($request->except('location_ids'));
        $project->locations()->sync($request->location_ids);

        $project->load(['manager', 'locations']);
        return $this->successResponse($project, 'Project updated successfully');
    }

    /**
     * Remove the specified project
     */
    public function destroy(Project $project)
    {
        // Check for associated assets
        if ($project->assets()->exists()) {
            return $this->errorResponse('Cannot delete project with assigned assets. Please reassign assets first.', 422);
        }

        $project->locations()->detach();
        $project->delete();

        return $this->successResponse(null, 'Project deleted successfully');
    }

    /**
     * Get project statistics
     */
    public function statistics()
    {
        $stats = [
            'total' => Project::count(),
            'by_status' => Project::selectRaw('status, count(*) as count')
                                 ->groupBy('status')
                                 ->get(),
            'by_priority' => Project::selectRaw('priority, count(*) as count')
                                   ->groupBy('priority')
                                   ->get(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * Get project assets
     */
    public function assets(Project $project)
    {
        $assets = $project->assets()
                         ->with(['location', 'employee'])
                         ->paginate(15);

        return $this->successResponse($assets);
    }
}
