<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository extends BaseRepository
{
    protected string $modelClass = Project::class;

    /**
     * Get visible projects.
     */
    public function getVisible(): Collection
    {
        return $this->newQuery()
            ->where('visible', true)
            ->orderBy('order')
            ->get();
    }

    /**
     * Search projects by name.
     */
    public function searchByName(string $name): Collection
    {
        return $this->newQuery()
            ->where('name', 'like', "%{$name}%")
            ->get();
    }

    /**
     * Get projects ordered by display order.
     */
    public function getOrdered(): Collection
    {
        return $this->newQuery()
            ->orderBy('order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get project statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $visible = $this->newQuery()->where('visible', true)->count();

        return [
            'total' => $total,
            'visible' => $visible,
        ];
    }
}
