<?php

namespace Bu\Server\Database\Repositories;

use Bu\Server\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository extends BaseRepository
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    /**
     * Find project by name.
     */
    public function findByName(string $name): ?Project
    {
        return $this->model->where('name', $name)->first();
    }

    /**
     * Get visible projects.
     */
    public function getVisible(): Collection
    {
        return $this->model->where('visible', true)->orderBy('order')->get();
    }

    /**
     * Search projects by name.
     */
    public function searchByName(string $name): Collection
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    /**
     * Upsert project by name.
     */
    public function upsertByName(array $data): Project
    {
        return $this->model->updateOrCreate(
            ['name' => $data['name']],
            $data
        );
    }

    /**
     * Bulk upsert projects.
     */
    public function bulkUpsert(array $projects): Collection
    {
        $results = new Collection();

        foreach ($projects as $projectData) {
            $project = $this->upsertByName($projectData);
            $results->push($project);
        }

        return $results;
    }
}