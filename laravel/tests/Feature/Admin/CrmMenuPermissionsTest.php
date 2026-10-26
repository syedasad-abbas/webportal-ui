<?php
namespace Tests\Feature\Admin;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class CrmMenuPermissionsTest extends TestCase {
    public function test_migration_is_additive_idempotent_and_admin_only(): void {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        require_once database_path('migrations/2020_07_24_184706_create_permission_tables.php');
        (new \CreatePermissionTables)->up();
        $admin=Role::create(['name'=>'Admin','guard_name'=>'web']);
        $super=Role::create(['name'=>'Superadmin','guard_name'=>'web']);
        $regular=Role::create(['name'=>'user','guard_name'=>'web']);
        $existing=Permission::create(['name'=>'user.view','guard_name'=>'web']);
        $admin->givePermissionTo($existing);
        $migration=require database_path('migrations/2026_10_24_000002_add_crm_configuration_permissions.php');
        $migration->up(); $migration->up();
        $this->assertSame(4,Permission::count());
        foreach (['crm_config.view','crm_config.edit','crm_dashboard.configure'] as $name) {
            $this->assertTrue($admin->fresh()->hasPermissionTo($name));
            $this->assertTrue($super->fresh()->hasPermissionTo($name));
            $this->assertFalse($regular->fresh()->hasPermissionTo($name));
        }
        $this->assertTrue($admin->fresh()->hasPermissionTo('user.view'));
    }
}
