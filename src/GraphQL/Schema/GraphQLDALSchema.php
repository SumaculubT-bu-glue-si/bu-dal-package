<?php

namespace YourCompany\GraphQLDAL\GraphQL\Schema;

use YourCompany\GraphQLDAL\GraphQL\Queries\AssetQueries;
use YourCompany\GraphQLDAL\GraphQL\Queries\LocationQueries;
use YourCompany\GraphQLDAL\GraphQL\Queries\ProjectQueries;
use YourCompany\GraphQLDAL\GraphQL\Mutations\AssetMutations;

/**
 * GraphQL Schema for the DAL Package
 * 
 * This class defines the GraphQL schema structure for the package.
 * It will be registered with the rebing/graphql-laravel package.
 */
class GraphQLDALSchema
{
    /**
     * The schema query.
     *
     * @var array
     */
    protected $query = [
        AssetQueries::class,
        LocationQueries::class,
        ProjectQueries::class,
    ];

    /**
     * The schema mutation.
     *
     * @var array
     */
    protected $mutation = [
        AssetMutations::class,
    ];

    /**
     * The schema subscription.
     *
     * @var array
     */
    protected $subscription = [
        // Add subscriptions here if needed
    ];

    /**
     * The schema types.
     *
     * @var array
     */
    protected $types = [
        // Types are auto-discovered from the models
    ];

    /**
     * The schema directives.
     *
     * @var array
     */
    protected $directives = [
        // Add custom directives here if needed
    ];

    /**
     * The schema middleware.
     *
     * @var array
     */
    protected $middleware = [
        // Add middleware here if needed
    ];

    /**
     * The schema guards.
     *
     * @var array
     */
    protected $guards = [
        // Add guards here if needed
    ];
}
