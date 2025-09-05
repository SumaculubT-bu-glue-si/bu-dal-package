<?php

namespace Bu\DAL\GraphQL\Mutations;

use Bu\DAL\Models\User;
use Bu\DAL\Database\Repositories\UserRepository;
use Bu\DAL\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserMutations
{
    public function __construct(
        private UserRepository $userRepository,
        private DatabaseManager $databaseManager
    ) {}

    /**
     * Create a new user.
     */
    public function create($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $userData = $args['user'];

            // Validate input
            $validated = validator($userData, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ])->validate();

            // Create user with hashed password
            return $this->userRepository->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        });
    }

    /**
     * Update an existing user.
     */
    public function update($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $id = $args['id'];
            $userData = $args['user'];

            $user = $this->userRepository->findOrFail($id);

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
            $updateData = [];
            if (isset($validated['name'])) {
                $updateData['name'] = $validated['name'];
            }

            if (isset($validated['email'])) {
                $updateData['email'] = $validated['email'];
            }

            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);
            return $user;
        });
    }

    /**
     * Delete a user.
     */
    public function delete($rootValue, array $args)
    {
        return $this->databaseManager->transaction(function () use ($args) {
            $id = $args['id'];
            $this->userRepository->delete($id);
            return true;
        });
    }
}
