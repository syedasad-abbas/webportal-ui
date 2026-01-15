<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Define permissions
        $createLog = Permission::firstOrCreate(['name' => 'agent_time_logs.create']);
        $viewLog = Permission::firstOrCreate(['name' => 'agent_time_logs.view']);

        // Assign permissions to roles
        Role::firstOrCreate(['name' => 'Superadmin'])
            ->syncPermissions([$createLog, $viewLog]);

        Role::firstOrCreate(['name' => 'Admin'])
            ->syncPermissions([$createLog, $viewLog]);

        Role::firstOrCreate(['name' => 'Support'])
            ->syncPermissions([$createLog, $viewLog]);

        Role::firstOrCreate(['name' => 'Role'])
            ->syncPermissions([$viewLog]); // Only view permission
        //
    }
}
