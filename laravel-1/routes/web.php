<?php

declare(strict_types=1);

use App\Http\Controllers\Backend\ActionLogController;
use App\Http\Controllers\Backend\DashboardController as BackendDashboardController;
use App\Http\Controllers\Backend\EditorController;
use App\Http\Controllers\Backend\LocaleController;
use App\Http\Controllers\Backend\ModulesController;
use App\Http\Controllers\Backend\PermissionsController;
use App\Http\Controllers\Backend\PostsController;
use App\Http\Controllers\Backend\ProfilesController;
use App\Http\Controllers\Backend\RolesController;
use App\Http\Controllers\Backend\SettingsController;
use App\Http\Controllers\Backend\TermsController;
use App\Http\Controllers\Backend\TranslationController;
use App\Http\Controllers\Backend\UserLoginAsController;
use App\Http\Controllers\Backend\UsersController;

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\UserAuthController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CarrierController;

use App\Http\Controllers\DialerController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('user.login');
})->name('index');

Route::get('/home', 'HomeController@index')->name('home');

/**
 * Existing "backend" admin area (kept as-is).
 */
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth']], function () {
    Route::get('/', [BackendDashboardController::class, 'index'])->name('dashboard');

    Route::resource('roles', RolesController::class);
    Route::delete('roles/delete/bulk-delete', [RolesController::class, 'bulkDelete'])->name('roles.bulk-delete');

    // Permissions
    Route::get('/permissions', [PermissionsController::class, 'index'])->name('permissions.index');
    Route::get('/permissions/{id}', [PermissionsController::class, 'show'])->name('permissions.show');

    // Modules
    Route::get('/modules', [ModulesController::class, 'index'])->name('modules.index');
    Route::post('/modules/toggle-status/{module}', [ModulesController::class, 'toggleStatus'])->name('modules.toggle-status');
    Route::post('/modules/upload', [ModulesController::class, 'store'])->name('modules.store');
    Route::delete('/modules/{module}', [ModulesController::class, 'destroy'])->name('modules.delete');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');

    // Translations
    Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/translations', [TranslationController::class, 'update'])->name('translations.update');
    Route::post('/translations/create', [TranslationController::class, 'create'])->name('translations.create');

    // Users + Login as
    Route::resource('users', UsersController::class);
    Route::delete('users/delete/bulk-delete', [UsersController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::get('users/{id}/login-as', [UserLoginAsController::class, 'loginAs'])->name('users.login-as');
    Route::post('users/switch-back', [UserLoginAsController::class, 'switchBack'])->name('users.switch-back');

    // Action Log
    Route::get('/action-log', [ActionLogController::class, 'index'])->name('actionlog.index');

    // Posts/Pages
    Route::get('/posts/{postType?}', [PostsController::class, 'index'])->name('posts.index');
    Route::get('/posts/{postType}/create', [PostsController::class, 'create'])->name('posts.create');
    Route::post('/posts/{postType}', [PostsController::class, 'store'])->name('posts.store');
    Route::get('/posts/{postType}/{id}', [PostsController::class, 'show'])->name('posts.show');
    Route::get('/posts/{postType}/{id}/edit', [PostsController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{postType}/{id}', [PostsController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{postType}/{id}', [PostsController::class, 'destroy'])->name('posts.destroy');
    Route::delete('/posts/{postType}/delete/bulk-delete', [PostsController::class, 'bulkDelete'])->name('posts.bulk-delete');

    // Terms
    Route::get('/terms/{taxonomy}', [TermsController::class, 'index'])->name('terms.index');
    Route::get('/terms/{taxonomy}/{term}/edit', [TermsController::class, 'edit'])->name('terms.edit');
    Route::post('/terms/{taxonomy}', [TermsController::class, 'store'])->name('terms.store');
    Route::put('/terms/{taxonomy}/{id}', [TermsController::class, 'update'])->name('terms.update');
    Route::delete('/terms/{taxonomy}/{id}', [TermsController::class, 'destroy'])->name('terms.destroy');
    Route::delete('/terms/{taxonomy}/delete/bulk-delete', [TermsController::class, 'bulkDelete'])->name('terms.bulk-delete');

    // Editor Upload
    Route::post('/editor/upload', [EditorController::class, 'upload'])->name('editor.upload');
});

/**
 * Profile routes.
 */
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => ['auth']], function () {
    Route::get('/edit', [ProfilesController::class, 'edit'])->name('edit');
    Route::put('/update', [ProfilesController::class, 'update'])->name('update');
});

Route::get('/locale/{lang}', [LocaleController::class, 'switch'])->name('locale.switch');


/**
 * ✅ Removed:
 * - admin/agent-time-logs
 * - admin/agent-leads
 */


/**
 * ✅ NEW AUTH + ADMIN PANEL (as per your provided routes)
 */
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

Route::get('/login', [UserAuthController::class, 'showLogin'])->name('user.login');
Route::post('/login', [UserAuthController::class, 'login'])->name('user.login.submit');

Route::middleware('admin.auth')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/admin/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/admin/carriers', [CarrierController::class, 'index'])->name('admin.carriers.index');
    Route::post('/admin/carriers', [CarrierController::class, 'store'])->name('admin.carriers.store');
    Route::get('/admin/carriers/{carrier}/edit', [CarrierController::class, 'edit'])->name('admin.carriers.edit');
    Route::put('/admin/carriers/{carrier}', [CarrierController::class, 'update'])->name('admin.carriers.update');
    Route::delete('/admin/carriers/{carrier}', [CarrierController::class, 'destroy'])->name('admin.carriers.destroy');

    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

Route::middleware('user.auth')->group(function () {
    Route::get('/dialer', [DialerController::class, 'index'])->name('dialer.index');
    Route::post('/dialer', [DialerController::class, 'dial'])->name('dialer.dial');

    // keep session route if your controller still uses it,
    // even though UI won't open a popup
    Route::get('/dialer/session/{uuid}', [DialerController::class, 'session'])->name('dialer.session');

    Route::get('/dialer/calls/{uuid}/status', [DialerController::class, 'callStatus'])->name('dialer.status');
    Route::post('/dialer/calls/{uuid}/dtmf', [DialerController::class, 'dtmf'])->name('dialer.dtmf');
    Route::post('/dialer/calls/{uuid}/{action}', [DialerController::class, 'control'])->name('dialer.control');

    Route::post('/logout', [UserAuthController::class, 'logout'])->name('user.logout');
});
