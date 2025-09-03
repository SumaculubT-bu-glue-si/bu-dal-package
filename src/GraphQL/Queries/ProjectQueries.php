<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\Project;
use Bu\DAL\Database\Repositories\ProjectRepository;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ProjectQueries
{
    public function __construct(
        private ProjectRepository $projectRepository
    ) {}

    /**
     * Find a single project by ID
     */
    public function project($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        return Project::find($args['id']);
    }

    /**
     * List multiple projects with optional filtering
     */
    public function projects($rootValue, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $query = Project::query();

        if (isset($args['name'])) {
            $query->where('name', 'like', '%' . $args['name'] . '%');
        }

        $perPage = $args['first'] ?? 20;
        $page = request()->get('page', 1);

        return $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
    }
}
