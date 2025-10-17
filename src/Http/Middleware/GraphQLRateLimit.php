<?php

namespace Bu\Server\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GraphQLRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRequestSignature($request);

        // Get rate limit based on user authentication status
        $maxAttempts = $this->getMaxAttempts($request);
        $decayMinutes = 1; // 1 minute window

        // Check if the request should be rate limited
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'errors' => [
                    [
                        'message' => 'Too many GraphQL requests. Please try again in ' . $seconds . ' seconds.',
                        'extensions' => [
                            'code' => 'RATE_LIMITED',
                            'retry_after' => $seconds
                        ]
                    ]
                ]
            ], 429);
        }

        // Increment the rate limiter
        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }

    /**
     * Resolve the request signature for rate limiting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = Auth::user();
        $ip = $request->ip();

        if ($user) {
            return 'graphql:user:' . $user->id;
        }

        return 'graphql:ip:' . $ip;
    }

    /**
     * Get the maximum number of attempts based on user status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function getMaxAttempts(Request $request): int
    {
        $user = Auth::user();

        if (!$user) {
            // Anonymous users - very restrictive
            return (int) env('GRAPHQL_THROTTLE_ANONYMOUS', 20);
        }

        // Check if user is admin (adjust based on your user model)
        if (isset($user->is_admin) && $user->is_admin) {
            return (int) env('GRAPHQL_THROTTLE_ADMIN', 300);
        }

        // Authenticated users
        return (int) env('GRAPHQL_THROTTLE_AUTHENTICATED', 120);
    }
}