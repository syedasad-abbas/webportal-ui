<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\RolesService;
use App\Services\UserService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RolesService $rolesService
    ) {
    }

    public function index(): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.view']);

        $sort = (string) request('sort', '');
        $sortField = null;
        $sortDirection = null;

        if (!empty($sort)) {
            $sortDirection = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $sortField = ltrim($sort, '-');

            $allowed = ['external_name', 'email', 'user_id', 'is_active', 'created_at'];
            if (!in_array($sortField, $allowed, true)) {
                $sortField = null;
                $sortDirection = null;
            }
        }

        $filters = [
            'search' => request('search'),
            'role' => request('role'),
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ];

        $currentUser = Auth::user();

        return view('backend.pages.users.index', [
            'users' => $this->userService->getUsers($filters, $currentUser),
            'roles' => $this->rolesService->getRolesDropdown(),
            'breadcrumbs' => [
                'title' => __('Users'),
            ],
        ]);
    }

    public function create(): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.create']);

        ld_do_action('user_create_page_before');

        $carriers = method_exists($this->userService, 'getCarriersDropdown')
            ? $this->userService->getCarriersDropdown()
            : collect();

        return view('backend.pages.users.create', [
            'roles' => $this->rolesService->getRolesDropdown(),
            'carriers' => $carriers,
            'breadcrumbs' => [
                'title' => __('New User'),
                'items' => [
                    [
                        'label' => __('Users'),
                        'url' => route('admin.users.index'),
                    ],
                ],
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->checkAuthorization(Auth::user(), ['user.create']);

        $user = new User();

        // Full Name removed - use only external_name
        $user->external_name = $request->input('external_name');
        $user->internal_name = $request->input('internal_name');
        $user->email = $request->input('email');

        // Status
        $user->is_active = $request->boolean('is_active', true);

        // Carrier (optional) - unchanged
        $user->carrier_id = $request->filled('carrierId') ? $request->input('carrierId') : null;

        // Recording (optional) - unchanged
        if ($request->has('recording_enabled')) {
            $user->recording_enabled = $request->boolean('recording_enabled');
        }

        $user->password = Hash::make($request->input('password'));

        $user = ld_apply_filters('user_store_before_save', $user, $request);
        $user->save();
        /** @var User $user */
        $user = ld_apply_filters('user_store_after_save', $user, $request);

        // Roles assignment
        if ($request->filled('roles')) {
            $roles = array_filter((array) $request->input('roles', []));
            if (!empty($roles)) {
                $user->assignRole($roles);
            }
        }

        $this->storeActionLog(ActionType::CREATED, ['user' => $user]);
        session()->flash('success', __('User has been created.'));

        ld_do_action('user_store_after', $user);
        $this->syncBackendUser($request, $user, $request->input('password'));

        return redirect()->route('admin.users.index');
    }

    public function edit(int $id): Renderable
    {
        $this->checkAuthorization(Auth::user(), ['user.edit']);

        $user = User::findOrFail($id);

        ld_do_action('user_edit_page_before');
        $user = ld_apply_filters('user_edit_page_before_with_user', $user);

        $carriers = method_exists($this->userService, 'getCarriersDropdown')
            ? $this->userService->getCarriersDropdown()
            : collect();

        return view('backend.pages.users.edit', [
            'user' => $user,
            'roles' => $this->rolesService->getRolesDropdown(),
            'carriers' => $carriers,
            'breadcrumbs' => [
                'title' => __('Edit User'),
                'items' => [
                    [
                        'label' => __('Users'),
                        'url' => route('admin.users.index'),
                    ],
                ],
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $this->checkAuthorization(Auth::user(), ['user.edit']);

        $user = User::findOrFail($id);

        $this->preventSuperAdminModification($user);

        // Agent/support: password-only updates
        if (auth()->user()->hasRole('Agent') || auth()->user()->hasRole('support')) {
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);

                $user = ld_apply_filters('user_update_before_save', $user, $request);
                $user->save();
                $user = ld_apply_filters('user_update_after_save', $user, $request);

                ld_do_action('user_update_after', $user);

                $this->storeActionLog(ActionType::UPDATED, ['user' => $user]);
                session()->flash('success', __('Password has been updated.'));
                $this->syncBackendUser($request, $user, $request->password);

                return back();
            }

            session()->flash('info', __('No changes were made.'));
            return back();
        }

        // Admin/other roles: update everything
        // Full Name removed - use only external_name
        $user->external_name = $request->input('external_name');
        $user->internal_name = $request->input('internal_name');
        $user->email = $request->input('email');

        // Status
        if ($request->has('is_active')) {
            $user->is_active = $request->boolean('is_active');
        }

        // Carrier - unchanged
        if ($request->has('carrierId')) {
            $user->carrier_id = $request->filled('carrierId') ? $request->input('carrierId') : null;
        }

        // Recording - unchanged
        if ($request->has('recording_enabled')) {
            $user->recording_enabled = $request->boolean('recording_enabled');
        }

        // Password optional
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user = ld_apply_filters('user_update_before_save', $user, $request);
        $user->save();
        $user = ld_apply_filters('user_update_after_save', $user, $request);

        ld_do_action('user_update_after', $user);

        // Role update
        $user->roles()->detach();
        if ($request->filled('roles')) {
            $roles = array_filter((array) $request->input('roles', []));
            if (!empty($roles)) {
                $user->assignRole($roles);
            }
        }

        $this->storeActionLog(ActionType::UPDATED, ['user' => $user]);
        session()->flash('success', __('User has been updated.'));
        $this->syncBackendUser($request, $user, $request->filled('password') ? $request->password : null);

        return back();
    }

    protected function backendClient(Request $request, bool $allowInternal = false)
    {
        $token = $request->session()->get('admin_token');
        if (! $token) {
            if (! $allowInternal) {
                return null;
            }
            $internalToken = config('services.backend.internal_token');
            if (! $internalToken) {
                return null;
            }
            return Http::withHeaders(['x-internal-token' => $internalToken])
                ->baseUrl(config('services.backend.url'));
        }
        return Http::withToken($token)->baseUrl(config('services.backend.url'));
    }

    protected function normalizeBackendRole(?string $role): ?string
    {
        if (! $role) {
            return null;
        }
        $normalized = strtolower(trim($role));
        $normalized = preg_replace('/[^a-z0-9]+/i', '_', $normalized) ?? $normalized;
        return trim($normalized, '_');
    }

    protected function syncBackendUser(Request $request, User $user, ?string $plainPassword): void
    {
        $client = $this->backendClient($request, true);
        if (! $client) {
            Log::warning('Backend user sync skipped: admin token missing.', ['email' => $user->email]);
            return;
        }

        $roleName = $user->getRoleNames()->first();
        $payload = [
            'fullName' => $user->external_name ?: ($user->name ?: $user->email),
            'email' => $user->email,
            'role' => $this->normalizeBackendRole($roleName),
            'carrierId' => $user->carrier_id,
            'recordingEnabled' => $user->recording_enabled,
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];

        if ($plainPassword) {
            $payload['password'] = $plainPassword;
        }

        try {
            $response = $client->post('/admin/users/sync', $payload);
            if ($response->failed()) {
                Log::warning('Backend user sync failed', [
                    'email' => $user->email,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Backend user sync exception: '.$e->getMessage(), ['email' => $user->email]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->checkAuthorization(Auth::user(), ['user.delete']);
        $user = $this->userService->getUserById($id);

        $this->preventSuperAdminModification($user);

        if (Auth::id() === $user->id) {
            session()->flash('error', __('You cannot delete your own account.'));
            return back();
        }

        $user = ld_apply_filters('user_delete_before', $user);
        $user->delete();
        $user = ld_apply_filters('user_delete_after', $user);

        session()->flash('success', __('User has been deleted.'));
        $this->storeActionLog(ActionType::DELETED, ['user' => $user]);
        ld_do_action('user_delete_after', $user);

        return back();
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->checkAuthorization(Auth::user(), ['user.delete']);

        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('admin.users.index')
                ->with('error', __('No users selected for deletion'));
        }

        if (in_array(Auth::id(), $ids)) {
            $ids = array_filter($ids, fn ($id) => $id != Auth::id());
            session()->flash('error', __('You cannot delete your own account. Other selected users will be processed.'));

            if (empty($ids)) {
                return redirect()->route('admin.users.index')
                    ->with('error', __('No users were deleted.'));
            }
        }

        $users = User::whereIn('id', $ids)->get();
        $deletedCount = 0;

        foreach ($users as $user) {
            if ($user->hasRole('superadmin')) {
                continue;
            }

            $user = ld_apply_filters('user_delete_before', $user);
            $user->delete();
            ld_apply_filters('user_delete_after', $user);

            $this->storeActionLog(ActionType::DELETED, ['user' => $user]);
            ld_do_action('user_delete_after', $user);

            $deletedCount++;
        }

        if ($deletedCount > 0) {
            session()->flash('success', __(':count users deleted successfully', ['count' => $deletedCount]));
        } else {
            session()->flash('error', __('No users were deleted. Selected users may include protected accounts.'));
        }

        return redirect()->route('admin.users.index');
    }
}
