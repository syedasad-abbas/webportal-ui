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

use App\Http\Controllers\Auth\UserAuthController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\CarrierController;

use App\Http\Controllers\Admin\DialerController;
use App\Http\Controllers\Admin\CampaignController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', 'HomeController@redirectAdmin')->name('index');
Route::get('/home', 'HomeController@index')->name('home');

/**
 * Public auth routes for portal login.
 */
Route::get('/login', [UserAuthController::class, 'showLogin'])->name('user.login');
Route::post('/login', [UserAuthController::class, 'login'])->name('user.login.submit');

/**
 * Admin routes.
 */
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth']], function () {
    Route::get('/', [BackendDashboardController::class, 'index'])->name('dashboard');
    Route::post('/ping', function () {
        return response()->json(['ok' => true]);
    })->name('ping');

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
    Route::get('users/active', [UsersController::class, 'active'])->name('users.active');
    Route::get('users/offline', [UsersController::class, 'offline'])->name('users.offline');
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


    // Carrier
Route::get('/carrier', [CarrierController::class, 'index'])->name('carrier.index');
Route::get('/carrier/create', [CarrierController::class, 'create'])->name('carrier.create');
Route::post('/carrier', [CarrierController::class, 'store'])->name('carrier.store');
Route::get('/carrier/{carrierId}/edit', [CarrierController::class, 'edit'])->name('carrier.edit');
Route::put('/carrier/{carrierId}', [CarrierController::class, 'update'])->name('carrier.update');
Route::delete('/carrier/{carrierId}', [CarrierController::class, 'destroy'])->name('carrier.destroy');
 Route::get('/dialer', [DialerController::class, 'index'])->name('dialer.index');
 Route::get('/calls/dialing', [DialerController::class, 'index'])->name('calls.dialing');
 Route::get('/calls/in-call', [DialerController::class, 'index'])->name('calls.in_call');

        Route::post('/dialer/dial', [DialerController::class, 'dial'])->name('dialer.dial');

        Route::get('/dialer/calls/{uuid}/status', [DialerController::class, 'status'])->name('dialer.status');

        Route::post('/dialer/calls/{uuid}/mute', [DialerController::class, 'mute'])->name('dialer.mute');
        Route::post('/dialer/calls/{uuid}/unmute', [DialerController::class, 'unmute'])->name('dialer.unmute');
        Route::post('/dialer/calls/{uuid}/hangup', [DialerController::class, 'hangup'])->name('dialer.hangup');

        Route::post('/dialer/calls/{uuid}/dtmf', [DialerController::class, 'dtmf'])->name('dialer.dtmf');

        Route::middleware('can:campaign.play')->group(function () {
            Route::post('/dialer/campaign/start', [DialerController::class, 'startCampaign'])->name('dialer.campaign.start');
            Route::post('/dialer/campaign/stop', [DialerController::class, 'stopCampaign'])->name('dialer.campaign.stop');
            Route::get('/dialer/campaign/next', [DialerController::class, 'nextLead'])->name('dialer.campaign.next');
        });

        Route::middleware('can:campaign.add')->group(function () {
            Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
            Route::post('/campaigns/store', [CampaignController::class, 'store'])->name('campaigns.store');
            Route::get('/campaigns/{campaign}/status', [CampaignController::class, 'status'])->name('campaigns.status');
            Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
            Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
            Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
        });

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




        // Dummy endpoint so the Blade form action works for now
     
Route::post('/logout', function () {
    request()->session()->forget(['admin_token', 'admin_user']);

    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');
})->name('logout');
