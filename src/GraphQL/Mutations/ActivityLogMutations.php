<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use GraphQL\Error\Error;

class ActivityLogMutations
{
    /**
     * Create a new activity log entry.
     */
    public function create($rootValue, array $args, $context)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            throw new Error('Unauthenticated');
        }

        $input = $args['input'];

        // Get client IP and user agent
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        $activityLog = ActivityLog::create([
            'user_id' => $user->id,
            'action' => $input['action'],
            'description' => $input['description'],
            'ip_address' => $input['ip_address'] ?? $ipAddress,
            'user_agent' => $input['user_agent'] ?? $userAgent,
            'metadata' => $input['metadata'] ? json_decode($input['metadata'], true) : null,
        ]);

        return $activityLog->load('user');
    }

    /**
     * Log user activity (convenience method).
     */
    public function logActivity($rootValue, array $args, $context)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            throw new Error('Unauthenticated');
        }

        // Get client IP and user agent
        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        $activityLog = ActivityLog::create([
            'user_id' => $user->id,
            'action' => $args['action'],
            'description' => $args['description'],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'metadata' => $args['metadata'] ? json_decode($args['metadata'], true) : null,
        ]);

        return $activityLog->load('user');
    }
}