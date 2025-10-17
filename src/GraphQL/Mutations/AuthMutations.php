<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\User;
use Bu\Server\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use GraphQL\Error\Error;

class AuthMutations
{
    /**
     * Login mutation resolver
     *
     * @param  mixed  $rootValue
     * @param  array  $args
     * @return array
     */
    public function login($rootValue, array $args)
    {
        $email = $args['email'];
        $password = $args['password'];

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new Error('Invalid credentials');
        }

        // Verify password
        if (!Hash::check($password, $user->password)) {
            throw new Error('Invalid credentials');
        }

        try {
            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            // Get token expiration time (default to 1 hour if not available)
            $expiresIn = 3600; // 1 hour in seconds

            // Log successful login
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'description' => 'User logged in successfully',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'login_method' => 'email_password',
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn,
                'user' => $user
            ];
        } catch (JWTException $e) {
            throw new Error('Could not create token: ' . $e->getMessage());
        }
    }

    /**
     * Logout mutation resolver
     *
     * @param  mixed  $rootValue
     * @param  array  $args
     * @return bool
     */
    public function logout($rootValue, array $args)
    {
        try {
            $user = Auth::guard('api')->user();

            // Log logout activity
            if ($user) {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'action' => 'logout',
                    'description' => 'User logged out successfully',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'logout_method' => 'manual',
                        'timestamp' => now()->toISOString(),
                    ],
                ]);
            }

            // Invalidate the current token
            JWTAuth::invalidate(JWTAuth::getToken());
            return true;
        } catch (JWTException $e) {
            return false;
        }
    }

    /**
     * Get current authenticated user
     *
     * @param  mixed  $rootValue
     * @param  array  $args
     * @return User|null
     */
    public function me($rootValue, array $args)
    {
        return Auth::guard('api')->user();
    }
}