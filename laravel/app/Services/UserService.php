<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Get users with filters
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getUsers(array $filters = [], User $currentUser)
{
    // If the user has 'Agent' role, return only their own record
    if ($currentUser->hasRole('Agent')) {
        return User::where('id', $currentUser->id)
            ->with('roles') // include roles for consistency
            ->paginateData([
                'per_page' => config('settings.default_pagination') ?? 10,
            ]);
    }

    // For other roles, apply normal filters
    $query = User::applyFilters($filters);

    return $query->paginateData([
        'per_page' => config('settings.default_pagination') ?? 10,
    ]);
}

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
        ]);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user;
    }

    public function getUserById(int $id): ?User
    {
        return User::findOrFail($id);
    }
}
