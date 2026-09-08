<?php
// Run with: php tests/permissions-regression.php (uses an in-memory database).
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
spl_autoload_register(function ($class) use ($root) {
    if (str_starts_with($class, 'App\\')) {
        $file = $root.'/app/'.str_replace('\\', '/', substr($class, 4)).'.php';
        if (is_file($file)) require $file;
    }
}, true, true);
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('CACHE_DRIVER=array');
putenv('APP_ENV=testing');
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function checkPermissionTest($condition, $message) {
    if (! $condition) throw new RuntimeException($message);
}

try {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
    app('db')->purge('sqlite');
    Schema::create('users', function ($table) { $table->id(); $table->string('email'); $table->timestamps(); });
    require $root.'/database/migrations/2020_07_24_184706_create_permission_tables.php';
    (new CreatePermissionTables())->up();
    $names = collect((new App\Services\PermissionService())->getAllPermissions())->pluck('permissions')->flatten()->all();
    foreach ($names as $name) Permission::create(['name' => $name, 'guard_name' => 'web']);
    foreach (['Admin', 'Superadmin', 'Agent', 'support', 'Custom'] as $roleName) {
        $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
        $user = User::create(['email' => strtolower($roleName).'@test.invalid']);
        $user->assignRole($role);
        foreach ($names as $permission) {
            checkPermissionTest(! $user->fresh()->can($permission), "$roleName bypasses $permission");
            $role->syncPermissions([$permission]);
            checkPermissionTest($user->fresh()->can($permission), "$roleName cannot use assigned $permission");
            $role->syncPermissions([]);
            checkPermissionTest(! $user->fresh()->can($permission), "$roleName retains revoked $permission");
        }
    }
    echo 'PASS: grant and revoke '.count($names)." permissions across five roles\n";

    $user = User::first();
    auth()->setUser($user);
    $request = Illuminate\Http\Request::create('/');
    $request->setUserResolver(fn() => auth()->user());
    $contactController = new App\Http\Controllers\Admin\ContactCenterController();
    $method = new ReflectionMethod($contactController, 'authorizeContactPermission');
    foreach (['contacts.view', 'contacts.create', 'contacts.edit'] as $permission) {
        try { $method->invoke($contactController, $request, $permission); throw new RuntimeException('Contact bypass'); }
        catch (Symfony\Component\HttpKernel\Exception\HttpException $e) { checkPermissionTest($e->getStatusCode() === 403, 'Contact denial'); }
        $user->givePermissionTo($permission);
        $method->invoke($contactController, $request, $permission);
        $user->revokePermissionTo($permission);
    }
    echo "PASS: contacts deny Admin without permissions and allow explicit grants\n";

    $router = app('router');
    $router->aliasMiddleware('can', Illuminate\Auth\Middleware\Authorize::class);
    foreach (['index' => 'campaign.play', 'status' => 'campaign.add', 'store' => 'campaign.add', 'edit' => 'campaign.edit', 'update' => 'campaign.edit', 'destroy' => 'campaign.delete'] as $action => $permission) {
        $route = $router->getRoutes()->getByName('admin.campaigns.'.$action);
        $middlewares = array_filter($route->gatherMiddleware(), fn($item) => $item instanceof Closure || (is_string($item) && str_starts_with($item, 'can:')));
        $run = fn() => (new Illuminate\Pipeline\Pipeline($app))->send($request)->through($router->resolveMiddleware($middlewares))->then(fn() => true);
        try { $run(); throw new RuntimeException("Campaign $action allowed without permission"); }
        catch (Illuminate\Auth\Access\AuthorizationException|Symfony\Component\HttpKernel\Exception\HttpException $e) {}
        $user->givePermissionTo($permission);
        checkPermissionTest($run(), "Campaign $action denied with $permission");
        $user->revokePermissionTo($permission);
    }
    echo "PASS: campaign routes use the corresponding action permissions\n";

    $rules = (new App\Http\Requests\Role\UpdateRoleRequest())->rules();
    unset($rules['name']);
    checkPermissionTest(validator(['permissions' => []], $rules)->passes(), 'Cannot clear all role permissions');
    checkPermissionTest(validator([], $rules)->passes(), 'Unchecked permission form rejected');
    echo "PASS: empty role permission selections can be saved\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n".$error->getTraceAsString()."\n");
    exit(1);
}
