<?php

namespace YourCompany\GraphQLDAL\Database\Repositories;

use YourCompany\GraphQLDAL\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    protected string $modelClass = User::class;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    /**
     * Search users by name.
     */
    public function searchByName(string $name): Collection
    {
        return $this->newQuery()
            ->where('name', 'like', "%{$name}%")
            ->get();
    }

    /**
     * Get verified users.
     */
    public function getVerified(): Collection
    {
        return $this->newQuery()
            ->whereNotNull('email_verified_at')
            ->get();
    }

    /**
     * Get user statistics.
     */
    public function getStatistics(): array
    {
        $total = $this->count();
        $verified = $this->getVerified()->count();

        return [
            'total' => $total,
            'verified' => $verified,
        ];
    }
}
