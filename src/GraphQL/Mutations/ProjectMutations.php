<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\Project;
use Bu\DAL\Database\Repositories\ProjectRepository;
use Bu\DAL\Database\DatabaseManager;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ProjectMutations
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private DatabaseManager $databaseManager
    ) {}

    /**
     * Create a new project
     */
    public function create($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['project'];

            return $this->projectRepository->create([
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'visible' => $input['visible'] ?? true,
                'order' => $input['order'] ?? 0,
            ]);
        });
    }

    /**
     * Update an existing project
     */
    public function update($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['project'];
            $id = $args['id'];

            $project = $this->projectRepository->findOrFail($id);

            $project->update([
                'name' => $input['name'] ?? $project->name,
                'description' => $input['description'] ?? $project->description,
                'visible' => $input['visible'] ?? $project->visible,
                'order' => $input['order'] ?? $project->order,
            ]);

            return $project;
        });
    }

    /**
     * Delete a project
     */
    public function delete($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $id = $args['id'];
            $this->projectRepository->delete($id);
            return true;
        });
    }

    /**
     * Upsert a project (create if doesn't exist, update if it does)
     */
    public function upsertProject($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $input = $args['project'];
            return $this->projectRepository->upsertByName($input);
        });
    }

    /**
     * Bulk upsert projects
     */
    public function bulkUpsertProjects($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $projects = $args['projects'];
            $results = $this->projectRepository->bulkUpsert($projects);
            return $results->all();
        });
    }
}
