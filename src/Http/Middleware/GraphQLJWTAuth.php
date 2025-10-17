<?php

namespace Bu\Server\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;

class GraphQLJWTAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if this is an internal command (like gws:sync-users)
        // Only detect CLI commands, not web requests
        $isInternalCommand = php_sapi_name() === 'cli' ||
            $request->header('User-Agent', '') === 'Laravel Artisan';

        if ($isInternalCommand) {
            // For internal commands, set a system user
            // Find or create a system user for internal operations
            $systemUser = \Bu\Server\Models\User::where('email', 'admin@example.com')->first();
            if (!$systemUser) {
                $systemUser = \Bu\Server\Models\User::create([
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'password' => bcrypt('password'),
                ]);
            }

            // Set the system user as authenticated
            Auth::guard('api')->setUser($systemUser);
            return $next($request);
        }

        try {
            // Get the token from the Authorization header
            $token = $request->bearerToken();

            if ($token) {
                // Set the token and authenticate the user
                JWTAuth::setToken($token);
                $user = JWTAuth::toUser();

                if ($user) {
                    // Set the authenticated user for the API guard
                    Auth::guard('api')->setUser($user);
                }
            }
        } catch (JWTException $e) {
            // Token is invalid, but we don't throw an error here
            // Let individual resolvers handle authentication
        }

        return $next($request);
    }
}