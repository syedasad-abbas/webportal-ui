<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Carrier;
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
    $query = User::applyFilters($filters);

    if (array_key_exists('is_active', $filters)) {
        $query->where('is_active', (bool) $filters['is_active']);
    }

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

    /**
     * Return carriers for dropdowns (id + name).
     */
    public function getCarriersDropdown()
    {
        return Carrier::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ]);
    }
}
