<?php

namespace Bu\Server\GraphQL\Mutations;

use Bu\Server\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserMutations
{
    /**
     * Create a new user.
     */
    public function create($rootValue, array $args)
    {
        $userData = $args['user'];
        
        // Validate input
        $validated = validator($userData, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ])->validate();

        // Create user with hashed password
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return $user;
    }

    /**
     * Update an existing user.
     */
    public function update($rootValue, array $args)
    {
        $id = $args['id'];
        $userData = $args['user'];
        
        $user = User::findOrFail($id);
        
        // Validate input
        $validated = validator($userData, [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'password' => 'sometimes|required|string|min:8',
        ])->validate();

        // Update user fields
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        
        return $user;
    }

    /**
     * Delete a user.
     */
    public function delete($rootValue, array $args)
    {
        $id = $args['id'];
        
        $user = User::findOrFail($id);
        
        // You might want to add additional checks here
        // For example, prevent deletion of the last admin user
        
        $user->delete();
        
        return true;
    }
}
