<?php

namespace Database\Seeders;
use Spatie\Permission\Models\Role;
use App\Models\Permission;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class leadsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $permissions = [
            'agent_leads.view',
            'agent_leads.create',
            'agent_leads.edit',
            'agent_leads.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superadmin = Role::firstOrCreate(['name' => 'Superadmin']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $support = Role::firstOrCreate(['name' => 'Support']);
        $agent = Role::firstOrCreate(['name' => 'Agent']);

        // Give all permissions to superadmin
        $superadmin->syncPermissions(Permission::all());

        // Give basic permissions to admin and support
        $admin->syncPermissions([
            'agent_leads.view',
            'agent_leads.create',
            'agent_leads.edit',
        ]);

        $support->syncPermissions([
            'agent_leads.view',
            'agent_leads.create',
            'agent_leads.edit',
        ]);

        // Give limited permissions to agent
        $agent->syncPermissions([
            'agent_leads.view',
            'agent_leads.create',
            'agent_leads.edit',
        ]);
    }
}
