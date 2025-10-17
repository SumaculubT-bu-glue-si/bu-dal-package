<?php

namespace Bu\Server\GraphQL\Queries;

use Bu\Server\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogQueries
{
    /**
     * Get activity logs with filtering and pagination
     *
     * @param  mixed  $rootValue
     * @param  array  $args
     * @return array
     */
    public function getActivityLogs($rootValue, array $args)
    {
        $query = ActivityLog::with('user');

        // Apply filters
        if (isset($args['user_id']) && $args['user_id']) {
            $query->where('user_id', $args['user_id']);
        }

        if (isset($args['action']) && $args['action']) {
            $query->where('action', 'like', '%' . $args['action'] . '%');
        }

        if (isset($args['date_from']) && $args['date_from']) {
            $query->whereDate('created_at', '>=', $args['date_from']);
        }

        if (isset($args['date_to']) && $args['date_to']) {
            $query->whereDate('created_at', '<=', $args['date_to']);
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Get pagination parameters
        $perPage = $args['first'] ?? 50;
        $page = $args['page'] ?? 1;

        // Get total count before pagination
        $total = $query->count();

        // Apply pagination
        $logs = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Calculate pagination info
        $lastPage = ceil($total / $perPage);
        $hasMorePages = $page < $lastPage;

        return [
            'data' => $logs,
            'paginatorInfo' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'total' => $total,
                'hasMorePages' => $hasMorePages,
            ]
        ];
    }
}