<?php

namespace Bu\DAL\GraphQL\Queries;

use Bu\DAL\Models\User;
use Bu\DAL\Database\Repositories\UserRepository;

class UserQueries
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Find a single user by ID or email
     */
    public function user($rootValue, array $args)
    {
        if (isset($args['id'])) {
            return User::find($args['id']);
        }

        if (isset($args['email'])) {
            return User::where('email', $args['email'])->first();
        }

        return null;
    }

    /**
     * List multiple users with optional filtering
     */
    public function users($rootValue, array $args)
    {
        $query = User::query();

        if (isset($args['name'])) {
            $query->where('name', 'like', '%' . $args['name'] . '%');
        }

        $perPage = $args['first'] ?? 10;
        $page = request()->get('page', 1);

        return $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
    }
}
