<?php

namespace Bu\Server\GraphQL\Queries;

use Bu\Server\Models\Project;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ProjectQueries
{
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
