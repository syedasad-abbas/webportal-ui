<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\PermissionService;
use App\Services\RolesService;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Class RolePermissionSeeder.
 *
 * @see https://spatie.be/docs/laravel-permission/v5/basic-usage/multiple-guards
 */
class RolePermissionSeeder extends Seeder
{
    public function __construct(
        private readonly PermissionService $permissionService,
        private readonly RolesService $rolesService
    ) {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $this->command->info('Creating permissions...');
        $this->permissionService->createPermissions();

        // Create predefined roles with their permissions
        $this->command->info('Creating predefined roles...');
        $roles = $this->rolesService->createPredefinedRoles();

        // Superadmin must retain every permission, including permissions added by migrations.
        $roles['superadmin']->syncPermissions(
            \Spatie\Permission\Models\Permission::query()->where('guard_name', 'web')->get()
        );

        // Ensure Admin role can access recordings.
        if (isset($roles['Admin'])) {
            $roles['Admin']->givePermissionTo([
                'recording.view',
                'recording.download',
                'recording.delete',
            ]);
        }

        // Assign superadmin role to superadmin user if exists
        $user = User::where('internal_name', 'superadmin')->first();
        if ($user) {
            $this->command->info('Assigning Superadmin role to superadmin user...');
            $user->assignRole($roles['superadmin']);
        }

        // Assign random roles to other users
        $this->command->info('Assigning random roles to other users...');
        $availableRoles = ['Admin', 'Editor', 'Subscriber']; // Exclude Superadmin from random assignment
        $users = User::all();

        foreach ($users as $user) {
            if (! $user->hasRole('Superadmin')) {
                // Get a random role from the available roles
                $randomRole = $availableRoles[array_rand($availableRoles)];
                $user->assignRole($randomRole);
            }
        }

        $this->command->info('Roles and Permissions created successfully!');
    }
}
