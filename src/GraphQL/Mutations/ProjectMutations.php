<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\Project;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ProjectMutations
{
    /**
     * Create a new project
     */
    public function create($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['project'];
        
        $project = Project::create([
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'visible' => $input['visible'] ?? true,
            'order' => $input['order'] ?? 0,
        ]);

        return $project;
    }

    /**
     * Update an existing project
     */
    public function update($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['project'];
        $id = $args['id'];
        
        $project = Project::findOrFail($id);
        
        $project->update([
            'name' => $input['name'] ?? $project->name,
            'description' => $input['description'] ?? $project->description,
            'visible' => $input['visible'] ?? $project->visible,
            'order' => $input['order'] ?? $project->order,
        ]);

        return $project;
    }

    /**
     * Delete a project
     */
    public function delete($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $id = $args['id'];
        
        $project = Project::findOrFail($id);
        $project->delete();
        
        return true;
    }

    /**
     * Upsert a project (create if doesn't exist, update if it does)
     */
    public function upsertProject($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $input = $args['project'];
        
        $project = Project::updateOrCreate(
            ['name' => $input['name']],
            [
                'description' => $input['description'] ?? null,
                'visible' => $input['visible'] ?? true,
                'order' => $input['order'] ?? 0,
            ]
        );

        return $project;
    }

    /**
     * Bulk upsert projects
     */
    public function bulkUpsertProjects($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $projects = $args['projects'];
        $result = [];
        
        foreach ($projects as $projectData) {
            $project = Project::updateOrCreate(
                ['name' => $projectData['name']],
                [
                    'description' => $projectData['description'] ?? null,
                    'visible' => $projectData['visible'] ?? true,
                    'order' => $projectData['order'] ?? 0,
                ]
            );
            
            $result[] = $project;
        }
        
        return $result;
    }
}
