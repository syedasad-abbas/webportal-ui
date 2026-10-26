<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['crm_config.view', 'crm_config.edit', 'crm_dashboard.configure'] as $name) {
            $permission = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['group_name' => 'crm_config']
            );
            foreach (Role::where('guard_name', 'web')->whereIn('name', ['Admin', 'Superadmin'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve permissions and any subsequent operator-managed grants.
    }
};
