<?php

namespace Bu\Server\Database\Repositories;

use Bu\Server\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Search users by name.
     */
    public function searchByName(string $name): Collection
    {
        return $this->model->where('name', 'like', "%{$name}%")->get();
    }

    /**
     * Get verified users.
     */
    public function getVerified(): Collection
    {
        return $this->model->whereNotNull('email_verified_at')->get();
    }

    /**
     * Get unverified users.
     */
    public function getUnverified(): Collection
    {
        return $this->model->whereNull('email_verified_at')->get();
    }
}